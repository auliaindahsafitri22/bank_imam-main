<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan: menu dashboard bertipe "Route" membuat /admin error
 * ("Action AdminRingkasanController@getIndex not defined"). Diganti tipe URL.
 * Idempotent.
 */
class PerbaikiDashboardRingkasanTipeUrl extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('cms_menus')) {
            return;
        }
        DB::table('cms_menus')
            ->whereIn('path', ['AdminRingkasanControllerGetIndex', '/admin/ringkasan_bsu'])
            ->where('is_dashboard', 1)
            ->update(['type' => 'URL', 'path' => '/admin/ringkasan_bsu']);

        $bsi = DB::table('cms_privileges')->where('name', 'BSI')->value('id');
        foreach (array_filter([1, 2, 4, $bsi ? (int) $bsi : null]) as $p) {
            Cache::forget('sidebarDashboard' . $p);
        }
    }

    public function down()
    {
    }
}
