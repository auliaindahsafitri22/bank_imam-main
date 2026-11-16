<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cms_users', function (Blueprint $table) {
            if (!Schema::hasColumn('cms_users', 'kecamatan_id')) {
                $table->unsignedInteger('kecamatan_id')->nullable()->after('id_cms_privileges');
                $table->foreign('kecamatan_id')
                      ->references('id')->on('kecamatans')
                      ->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('cms_users', function (Blueprint $table) {
            $table->dropForeign(['kecamatan_id']);
            $table->dropColumn('kecamatan_id');
        });
    }
};
