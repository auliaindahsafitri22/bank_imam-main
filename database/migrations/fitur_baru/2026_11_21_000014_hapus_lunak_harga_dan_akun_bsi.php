<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penghapusan "lunak" harga acuan BSI dan akun BSI: data tidak dibuang, hanya ditandai dihapus
 * (kapan dan oleh siapa) sehingga riwayat tetap utuh dan bisa dipulihkan.
 * Nama kolom sengaja bukan "deleted_at" agar tidak bentrok dengan perilaku otomatis CRUDBooster.
 * Hanya MENAMBAH kolom. Idempotent.
 */
class HapusLunakHargaDanAkunBsi extends Migration
{
    public function up()
    {
        if (Schema::hasTable('bsi_harga') && !Schema::hasColumn('bsi_harga', 'dihapus_at')) {
            Schema::table('bsi_harga', function (Blueprint $t) {
                $t->timestamp('dihapus_at')->nullable();
                $t->unsignedInteger('dihapus_oleh')->nullable();   // cms_users.id
                $t->index('dihapus_at', 'bsi_harga_dihapus_idx');
            });
        }
        if (Schema::hasTable('cms_users') && !Schema::hasColumn('cms_users', 'dihapus_at')) {
            Schema::table('cms_users', function (Blueprint $t) {
                $t->timestamp('dihapus_at')->nullable();
                $t->unsignedInteger('dihapus_oleh')->nullable();
                $t->unsignedInteger('dihapus_kelurahan_id')->nullable();   // kelurahan yang dipegang sebelum dihapus
            });
        }
    }

    public function down()
    {
        // Kolom penanda sengaja tidak dihapus agar riwayat penghapusan tidak hilang.
    }
}
