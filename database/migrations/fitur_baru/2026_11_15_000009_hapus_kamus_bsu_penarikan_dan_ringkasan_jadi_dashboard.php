<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Menghapus menu/modul CMS: Kamus Jenis Sampah, Istilah Kamus, Manajemen BSU, Penarikan Saldo.
 * 2. Menghapus menu "Ringkasan" (isinya tampil di halaman awal dashboard; dashboard bawaan tidak diubah).
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

        // ---- 2. Menu Ringkasan dihapus dari sidebar: isinya kini tampil di halaman awal dashboard ----
        $menuRingkasan = DB::table('cms_menus')
            ->whereIn('path', ['AdminRingkasanControllerGetIndex', '/admin/ringkasan_bsu'])->pluck('id')->all();
        if ($menuRingkasan) {
            DB::table('cms_menus_privileges')->whereIn('id_cms_menus', $menuRingkasan)->delete();
            DB::table('cms_menus')->whereIn('id', $menuRingkasan)->delete();
        }
    }

    public function down()
    {
        // Sengaja kosong: menu/modul yang dihapus tidak dipulihkan otomatis.
    }
}
