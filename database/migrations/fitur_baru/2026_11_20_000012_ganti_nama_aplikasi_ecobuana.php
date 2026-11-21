<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengganti nama aplikasi "Bank Imam" menjadi "EcoBuana" pada pengaturan CMS (cms_settings.appname),
 * yang dipakai judul/header hasil export, sidebar, dan footer. Idempotent.
 */
class GantiNamaAplikasiEcobuana extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('cms_settings')) {
            return;
        }
        DB::table('cms_settings')->where('name', 'appname')->update(['content' => 'EcoBuana']);
        Cache::flush();
    }

    public function down()
    {
    }
}
