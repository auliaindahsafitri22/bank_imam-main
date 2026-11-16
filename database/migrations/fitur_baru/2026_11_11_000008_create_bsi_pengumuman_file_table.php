<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lampiran file pada pengumuman BSI (disimpan privat di storage/app/bsi_lampiran). */
class CreateBsiPengumumanFileTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('bsi_pengumuman_file')) {
            Schema::create('bsi_pengumuman_file', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('pengumuman_id');
                $t->string('nama_asli', 150);
                $t->string('path', 255);              // relatif terhadap disk "local" (storage/app)
                $t->string('mime', 100)->nullable();
                $t->unsignedInteger('ukuran')->default(0);
                $t->timestamp('created_at')->useCurrent();
                $t->index('pengumuman_id', 'bsi_file_pengumuman_idx');
                $t->foreign('pengumuman_id')->references('id')->on('bsi_pengumuman')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('bsi_pengumuman_file');
    }
}
