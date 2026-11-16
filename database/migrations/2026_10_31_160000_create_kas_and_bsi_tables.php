<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateKasAndBsiTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('transactions_bsi')) {
            Schema::create('transactions_bsi', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('admin_id')->nullable()->index();
                $t->unsignedBigInteger('sampah_id')->nullable()->index();
                $t->decimal('total_weight', 12, 2)->default(0);
                $t->decimal('total_price', 15, 2)->default(0);
                $t->string('periode', 50)->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('kas_kategori')) {
            Schema::create('kas_kategori', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('admin_id')->nullable()->index();
                $t->enum('tipe', ['masuk', 'keluar']);
                $t->string('nama', 150);
                $t->timestamps();
            });

            DB::table('kas_kategori')->insert([
                'admin_id' => null, 'tipe' => 'masuk',
                'nama' => 'Penjualan sampah ke BSI',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        if (!Schema::hasTable('kas_umum')) {
            Schema::create('kas_umum', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->unsignedBigInteger('admin_id')->index();
                $t->date('tanggal')->index();
                $t->enum('tipe', ['masuk', 'keluar']);
                $t->unsignedBigInteger('kategori_id')->nullable();
                $t->string('uraian', 255);
                $t->bigInteger('jumlah')->default(0);
                $t->string('bukti')->nullable();
                $t->string('ref_type', 50)->nullable();
                $t->string('ref_id', 50)->nullable();
                $t->unsignedBigInteger('created_by')->nullable();
                $t->timestamps();
                $t->softDeletes();
                $t->unique(['admin_id', 'ref_type', 'ref_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('kas_umum');
        Schema::dropIfExists('kas_kategori');
        Schema::dropIfExists('transactions_bsi');
    }
}