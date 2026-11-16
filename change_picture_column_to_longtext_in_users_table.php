<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ChangePictureColumnToLongtextInUsersTable extends Migration
{
    /**
     * Ubah kolom users.picture menjadi LONGTEXT agar muat data gambar base64.
     */
    public function up()
    {
        DB::statement('ALTER TABLE users MODIFY picture LONGTEXT NULL');
    }

    /**
     * Kembalikan ke VARCHAR(255).
     * Catatan: rollback bisa gagal kalau sudah ada data picture yang lebih panjang dari 255 karakter.
     */
    public function down()
    {
        DB::statement('ALTER TABLE users MODIFY picture VARCHAR(255) NULL');
    }
}