<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 1. Mengembalikan dashboard statistik lama sebagai halaman awal (Ringkasan kini ditampilkan DI ATAS-nya,
 *    lewat view override resources/views/vendor/crudbooster/statistic_builder/show.blade.php).
 *    Menu "Ringkasan" terpisah disembunyikan dari sidebar (halamannya tetap bisa dibuka lewat URL).
 * 2. Menghapus menu/modul CMS "Jadwal Pengumpulan" (tabel jadwal_pengumpulan dibiarkan utuh).
 * 3. Memindahkan menu "Setoran Massal" ke dalam grup menu "Setoran Sampah".
 * Idempotent.
 */
class DashboardLamaKembaliHapusJadwalSetoranMassal extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('cms_menus') || !Schema::hasTable('cms_moduls')) {
            return;
        }
        $bsi = (int) DB::table('cms_privileges')->where('name', 'BSI')->value('id');

        // ---- 1. Dashboard lama kembali ----
        $dashboard = [
            'statistic_builder/show/dashboard-super-admin' => [1, 2],
            'statistic_builder/show/dashboard'             => [4],
            'statistic_builder/show/dashboard-bsi'         => $bsi ? [$bsi] : [],
        ];
        foreach ($dashboard as $path => $peran) {
            $menu = DB::table('cms_menus')->where('path', $path)->first();
            if (!$menu) {
                continue;
            }
            DB::table('cms_menus')->where('id', $menu->id)->update(['is_dashboard' => 1, 'is_active' => 1]);
            foreach ($peran as $p) {
                $ada = DB::table('cms_menus_privileges')->where('id_cms_menus', $menu->id)->where('id_cms_privileges', $p)->exists();
                if (!$ada) {
                    DB::table('cms_menus_privileges')->insert(['id_cms_menus' => $menu->id, 'id_cms_privileges' => $p]);
                }
            }
        }
        // Ringkasan bukan dashboard lagi dan tidak muncul sebagai menu sendiri.
        DB::table('cms_menus')->whereIn('path', ['AdminRingkasanControllerGetIndex', '/admin/ringkasan_bsu'])
            ->update(['name' => 'Ringkasan', 'type' => 'Route', 'path' => 'AdminRingkasanControllerGetIndex', 'is_dashboard' => 0, 'is_active' => 0]);

        // ---- 2. Hapus Jadwal Pengumpulan ----
        $modul = DB::table('cms_moduls')->where('controller', 'AdminJadwalController')->get(['id', 'path']);
        $menuId = DB::table('cms_menus')->where('path', 'AdminJadwalControllerGetIndex')
            ->orWhere('path', 'AdminJadwalControllerGetKalender')
            ->orWhereIn('path', $modul->pluck('path')->all() ?: ['-'])->pluck('id')->all();
        if ($menuId) {
            DB::table('cms_menus_privileges')->whereIn('id_cms_menus', $menuId)->delete();
            DB::table('cms_menus')->whereIn('id', $menuId)->delete();
        }
        if ($modul->count()) {
            DB::table('cms_privileges_roles')->whereIn('id_cms_moduls', $modul->pluck('id')->all())->delete();
            DB::table('cms_moduls')->whereIn('id', $modul->pluck('id')->all())->delete();
        }

        // ---- 3. Setoran Massal masuk ke grup "Setoran Sampah" ----
        $induk = DB::table('cms_menus')->where('name', 'Setoran Sampah')->where('parent_id', 0)->value('id');
        if ($induk) {
            $sorting = (int) DB::table('cms_menus')->where('parent_id', $induk)->max('sorting') + 1;
            DB::table('cms_menus')->where('path', 'AdminSetoranMassalControllerGetIndex')
                ->update(['parent_id' => $induk, 'sorting' => $sorting]);
        }

        foreach (array_filter([1, 2, 4, $bsi]) as $p) {
            Cache::forget('sidebarDashboard' . $p);
        }
    }

    public function down()
    {
    }
}
