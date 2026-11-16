<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('kecamatans')) {
            Schema::create('kecamatans', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama_kecamatan', 100);
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('kecamatans');
    }
};
