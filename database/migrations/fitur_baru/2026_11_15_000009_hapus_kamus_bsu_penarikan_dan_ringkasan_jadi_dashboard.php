<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Menghapus menu/modul CMS: Kamus Jenis Sampah, Istilah Kamus, Manajemen BSU, Penarikan Saldo.
 * 2. Menjadikan "Ringkasan" sebagai halaman awal (dashboard) Super Admin, Admin, BSI, dan BSU.
 *
 * Hanya menyentuh tabel CMS (menu, modul, hak akses). TIDAK menghapus data:
 * tabel kamus_istilah, withdrawals, dan saldo_mutasi dibiarkan utuh.
 * Aman dijalankan ulang.
 */
class HapusKamusBsuPenarikanDanRingkasanJadiDashboard extends Migration
{
    private $controllerDihapus = [
        'AdminKamusController',
        'AdminKamusIstilahController',
        'AdminBsuUnitController',
        'AdminWithdrawalsController',
    ];

    public function up()
    {
        if (!Schema::hasTable('cms_menus') || !Schema::hasTable('cms_moduls')) {
            return; // mis. unit test dengan database kosong
        }

        // ---- 1. Hapus modul, hak akses, dan menu ----
        $modul = DB::table('cms_moduls')->whereIn('controller', $this->controllerDihapus)->get(['id', 'path']);
        $modulId = $modul->pluck('id')->all();
        $path = $modul->pluck('path')->all();

        $menuId = DB::table('cms_menus')->where(function ($q) use ($path) {
            foreach ($this->controllerDihapus as $c) {
                $q->orWhere('path', $c . 'GetIndex');
            }
            $q->orWhereIn('path', $path ?: ['-']);
        })->pluck('id')->all();

        if ($menuId) {
            DB::table('cms_menus_privileges')->whereIn('id_cms_menus', $menuId)->delete();
            DB::table('cms_menus')->whereIn('id', $menuId)->delete();
        }
        if ($modulId) {
            DB::table('cms_privileges_roles')->whereIn('id_cms_moduls', $modulId)->delete();
            DB::table('cms_moduls')->whereIn('id', $modulId)->delete();
        }

        // ---- 2. Ringkasan menjadi dashboard ----
        $ringkasan = DB::table('cms_menus')->whereIn('path', ['AdminRingkasanControllerGetIndex', '/admin/ringkasan_bsu'])->first();
        if (!$ringkasan) {
            return; // modul Ringkasan belum terpasang
        }

        $bsi = DB::table('cms_privileges')->where('name', 'BSI')->value('id');
        $peran = array_values(array_filter([1, 2, 4, $bsi ? (int) $bsi : null]));

        // Pastikan setiap peran punya menu Ringkasan.
        foreach ($peran as $p) {
            $ada = DB::table('cms_menus_privileges')
                ->where('id_cms_menus', $ringkasan->id)->where('id_cms_privileges', $p)->exists();
            if (!$ada) {
                DB::table('cms_menus_privileges')->insert(['id_cms_menus' => $ringkasan->id, 'id_cms_privileges' => $p]);
            }
        }

        // Lepas dashboard lama (statistik) dari peran-peran tersebut.
        $lama = DB::table('cms_menus')->where('is_dashboard', 1)->where('id', '!=', $ringkasan->id)->pluck('id')->all();
        foreach ($lama as $id) {
            DB::table('cms_menus_privileges')->where('id_cms_menus', $id)->whereIn('id_cms_privileges', $peran)->delete();
            if (!DB::table('cms_menus_privileges')->where('id_cms_menus', $id)->exists()) {
                DB::table('cms_menus')->where('id', $id)->update(['is_dashboard' => 0, 'is_active' => 0]);
            }
        }

        DB::table('cms_menus')->where('id', $ringkasan->id)->update([
            'name' => 'Dashboard', 'type' => 'URL', 'path' => '/admin/ringkasan_bsu', 'is_dashboard' => 1, 'is_active' => 1, 'parent_id' => 0,
            'icon' => 'fa fa-dashboard', 'sorting' => 1,
        ]);

        // Segarkan cache sidebar CRUDBooster.
        foreach (array_merge([1, 2, 4], $bsi ? [(int) $bsi] : []) as $p) {
            \Illuminate\Support\Facades\Cache::forget('sidebarDashboard' . $p);
        }
    }

    public function down()
    {
        // Sengaja kosong: menu/modul yang dihapus tidak dipulihkan otomatis.
    }
}
