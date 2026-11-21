<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Hapus lunak pengumuman BSI: ditandai dihapus (kapan & oleh siapa), isi tetap tersimpan. Hanya MENAMBAH kolom. */
class HapusLunakPengumumanBsi extends Migration
{
    public function up()
    {
        if (Schema::hasTable('bsi_pengumuman') && !Schema::hasColumn('bsi_pengumuman', 'dihapus_at')) {
            Schema::table('bsi_pengumuman', function (Blueprint $t) {
                $t->timestamp('dihapus_at')->nullable();
                $t->unsignedInteger('dihapus_oleh')->nullable();   // cms_users.id
            });
        }
    }

    public function down()
    {
        // Kolom penanda sengaja tidak dihapus.
    }
}
