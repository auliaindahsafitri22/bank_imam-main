<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('cms_moduls')) {
            $modul = DB::table('cms_moduls')->where('controller', 'AdminKecamatansController')->first();

            if (!$modul) {
                $modulId = DB::table('cms_moduls')->insertGetId([
                    'name'         => 'Data Kecamatan',
                    'icon'         => 'fa fa-map-marker',
                    'path'         => 'kecamatan',
                    'table_name'   => 'kecamatans',
                    'controller'   => 'AdminKecamatansController',
                    'is_protected' => 0,
                    'is_active'    => 1,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            } else {
                $modulId = $modul->id;
            }

            if (Schema::hasTable('cms_privileges_roles')) {
                // Grant full permissions to Super Admin (id_cms_privileges = 1)
                $privilegeExists = DB::table('cms_privileges_roles')
                    ->where('id_cms_moduls', $modulId)
                    ->where('id_cms_privileges', 1)
                    ->exists();

                if (!$privilegeExists) {
                    DB::table('cms_privileges_roles')->insert([
                        'id_cms_moduls'     => $modulId,
                        'id_cms_privileges' => 1,
                        'is_visible'        => 1,
                        'is_create'         => 1,
                        'is_read'           => 1,
                        'is_edit'           => 1,
                        'is_delete'         => 1,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('cms_moduls')) {
            $modul = DB::table('cms_moduls')->where('controller', 'AdminKecamatansController')->first();
            if ($modul) {
                if (Schema::hasTable('cms_privileges_roles')) {
                    DB::table('cms_privileges_roles')->where('id_cms_moduls', $modul->id)->delete();
                }
                DB::table('cms_moduls')->where('id', $modul->id)->delete();
            }
        }
    }
};
