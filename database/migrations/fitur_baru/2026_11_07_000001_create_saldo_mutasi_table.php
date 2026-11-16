<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSaldoMutasiTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('saldo_mutasi')) {
            return;
        }

        Schema::create('saldo_mutasi', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();
            $table->string('type', 10)->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->decimal('saldo_sebelum', 15, 2)->nullable();
            $table->decimal('saldo_sesudah', 15, 2)->nullable();
            $table->string('keterangan')->nullable();
            $table->string('ref_type', 50)->nullable();
            $table->unsignedBigInteger('ref_id')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('saldo_mutasi');
    }
}