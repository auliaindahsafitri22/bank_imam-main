<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToTransactions extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'price_per_kg')) {
                $table->unsignedInteger('price_per_kg')->default(0);
            }
            if (!Schema::hasColumn('transactions', 'periode')) {
                $table->string('periode', 20)->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'price_per_kg')) {
                $table->dropColumn('price_per_kg');
            }
            if (Schema::hasColumn('transactions', 'periode')) {
                $table->dropColumn('periode');
            }
        });
    }
}