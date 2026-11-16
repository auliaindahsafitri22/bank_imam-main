<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Menghapus modul, hak akses, dan menu CMS "Kalkulator Setoran". Data lain tidak disentuh. Idempotent. */
class HapusKalkulatorSetoran extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('cms_menus') || !Schema::hasTable('cms_moduls')) {
            return;
        }
        $menuId = DB::table('cms_menus')->where('path', 'AdminKalkulatorControllerGetIndex')->pluck('id')->all();
        if ($menuId) {
            DB::table('cms_menus_privileges')->whereIn('id_cms_menus', $menuId)->delete();
            DB::table('cms_menus')->whereIn('id', $menuId)->delete();
        }
        $modulId = DB::table('cms_moduls')->where('controller', 'AdminKalkulatorController')->pluck('id')->all();
        if ($modulId) {
            DB::table('cms_privileges_roles')->whereIn('id_cms_moduls', $modulId)->delete();
            DB::table('cms_moduls')->whereIn('id', $modulId)->delete();
        }
    }

    public function down()
    {
    }
}
