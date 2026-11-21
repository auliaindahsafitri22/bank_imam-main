<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengembalikan dashboard bawaan (statistik) yang sempat diganti Ringkasan oleh migrasi 000009/000010:
 *   dashboard-super-admin -> Super Admin & Admin
 *   dashboard             -> BSU
 *   dashboard-bsi         -> BSI
 * Menu Ringkasan dihapus dari sidebar (isinya tampil di halaman awal dashboard).
 * Idempotent; aman dijalankan pada database yang tidak pernah terkena 000009/000010.
 */
class KembalikanDashboardAwal extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('cms_menus')) {
            return;
        }
        $bsi = DB::table('cms_privileges')->where('name', 'BSI')->value('id');

        $peta = [
            'statistic_builder/show/dashboard-super-admin' => [1, 2],
            'statistic_builder/show/dashboard'             => [4],
            'statistic_builder/show/dashboard-bsi'         => $bsi ? [(int) $bsi] : [],
        ];

        foreach ($peta as $path => $peran) {
            $id = DB::table('cms_menus')->where('path', $path)->value('id');
            if (!$id) {
                continue;
            }
            DB::table('cms_menus')->where('id', $id)->update(['is_active' => 1, 'is_dashboard' => 1]);
            foreach ($peran as $p) {
                $ada = DB::table('cms_menus_privileges')
                    ->where('id_cms_menus', $id)->where('id_cms_privileges', $p)->exists();
                if (!$ada) {
                    DB::table('cms_menus_privileges')->insert(['id_cms_menus' => $id, 'id_cms_privileges' => $p]);
                }
            }
        }

        // Menu Ringkasan (versi dashboard sementara maupun versi sidebar) dihapus.
        $ring = DB::table('cms_menus')
            ->whereIn('path', ['AdminRingkasanControllerGetIndex', '/admin/ringkasan_bsu'])->pluck('id')->all();
        if ($ring) {
            DB::table('cms_menus_privileges')->whereIn('id_cms_menus', $ring)->delete();
            DB::table('cms_menus')->whereIn('id', $ring)->delete();
        }

        foreach (array_filter([1, 2, 4, $bsi ? (int) $bsi : null]) as $p) {
            Cache::forget('sidebarDashboard' . $p);
        }
    }

    public function down()
    {
    }
}
