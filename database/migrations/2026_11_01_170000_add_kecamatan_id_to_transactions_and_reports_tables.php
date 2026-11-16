<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Add kecamatan_id to transactions (Tabungan Nasabah)
        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'kecamatan_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->unsignedInteger('kecamatan_id')->nullable()->after('admin_id');
                $table->foreign('kecamatan_id')
                      ->references('id')->on('kecamatans')
                      ->onDelete('set null');
            });
        }

        // 2. Add kecamatan_id to transactions_bsi (Transaksi BSI)
        if (Schema::hasTable('transactions_bsi') && !Schema::hasColumn('transactions_bsi', 'kecamatan_id')) {
            Schema::table('transactions_bsi', function (Blueprint $table) {
                $table->unsignedInteger('kecamatan_id')->nullable()->after('admin_id');
                $table->foreign('kecamatan_id')
                      ->references('id')->on('kecamatans')
                      ->onDelete('set null');
            });
        }

        // 3. Add kecamatan_id to tukar_poins (Tukar Poin)
        if (Schema::hasTable('tukar_poins') && !Schema::hasColumn('tukar_poins', 'kecamatan_id')) {
            Schema::table('tukar_poins', function (Blueprint $table) {
                $table->unsignedInteger('kecamatan_id')->nullable()->after('admin_id');
                $table->foreign('kecamatan_id')
                      ->references('id')->on('kecamatans')
                      ->onDelete('set null');
            });
        }

        // 4. Add kecamatan_id to tukar_saldo (Tukar Saldo)
        if (Schema::hasTable('tukar_saldo') && !Schema::hasColumn('tukar_saldo', 'kecamatan_id')) {
            Schema::table('tukar_saldo', function (Blueprint $table) {
                $table->unsignedInteger('kecamatan_id')->nullable()->after('admin_id');
                $table->foreign('kecamatan_id')
                      ->references('id')->on('kecamatans')
                      ->onDelete('set null');
            });
        }

        // 5. Add kecamatan_id to laporan_keuangan (Laporan Keuangan)
        if (Schema::hasTable('laporan_keuangan') && !Schema::hasColumn('laporan_keuangan', 'kecamatan_id')) {
            Schema::table('laporan_keuangan', function (Blueprint $table) {
                $table->unsignedInteger('kecamatan_id')->nullable()->after('admin_id');
                $table->foreign('kecamatan_id')
                      ->references('id')->on('kecamatans')
                      ->onDelete('set null');
            });
        }

        // Backfill existing data based on admin_id / cms_users.kecamatan_id
        DB::statement("
            UPDATE transactions t 
            JOIN cms_users u ON t.admin_id = u.id 
            SET t.kecamatan_id = u.kecamatan_id 
            WHERE t.kecamatan_id IS NULL AND u.kecamatan_id IS NOT NULL
        ");

        DB::statement("
            UPDATE transactions_bsi t 
            JOIN cms_users u ON t.admin_id = u.id 
            SET t.kecamatan_id = u.kecamatan_id 
            WHERE t.kecamatan_id IS NULL AND u.kecamatan_id IS NOT NULL
        ");

        DB::statement("
            UPDATE tukar_poins tp 
            LEFT JOIN cms_users u ON tp.admin_id = u.id 
            LEFT JOIN users nasabah ON tp.user_id = nasabah.id 
            LEFT JOIN cms_users u_nasabah ON nasabah.cms_user_id = u_nasabah.id 
            SET tp.admin_id = COALESCE(tp.admin_id, u_nasabah.id),
                tp.kecamatan_id = COALESCE(u.kecamatan_id, u_nasabah.kecamatan_id) 
            WHERE tp.kecamatan_id IS NULL
        ");

        DB::statement("
            UPDATE tukar_saldo ts 
            LEFT JOIN cms_users u ON ts.admin_id = u.id 
            LEFT JOIN users nasabah ON ts.user_id = nasabah.id 
            LEFT JOIN cms_users u_nasabah ON nasabah.cms_user_id = u_nasabah.id 
            SET ts.admin_id = COALESCE(ts.admin_id, u_nasabah.id),
                ts.kecamatan_id = COALESCE(u.kecamatan_id, u_nasabah.kecamatan_id) 
            WHERE ts.kecamatan_id IS NULL
        ");

        DB::statement("
            UPDATE laporan_keuangan l 
            JOIN cms_users u ON l.admin_id = u.id 
            SET l.kecamatan_id = u.kecamatan_id 
            WHERE l.kecamatan_id IS NULL AND u.kecamatan_id IS NOT NULL
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'kecamatan_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign(['kecamatan_id']);
                $table->dropColumn('kecamatan_id');
            });
        }

        if (Schema::hasTable('transactions_bsi') && Schema::hasColumn('transactions_bsi', 'kecamatan_id')) {
            Schema::table('transactions_bsi', function (Blueprint $table) {
                $table->dropForeign(['kecamatan_id']);
                $table->dropColumn('kecamatan_id');
            });
        }

        if (Schema::hasTable('tukar_poins') && Schema::hasColumn('tukar_poins', 'kecamatan_id')) {
            Schema::table('tukar_poins', function (Blueprint $table) {
                $table->dropForeign(['kecamatan_id']);
                $table->dropColumn('kecamatan_id');
            });
        }

        if (Schema::hasTable('tukar_saldo') && Schema::hasColumn('tukar_saldo', 'kecamatan_id')) {
            Schema::table('tukar_saldo', function (Blueprint $table) {
                $table->dropForeign(['kecamatan_id']);
                $table->dropColumn('kecamatan_id');
            });
        }

        if (Schema::hasTable('laporan_keuangan') && Schema::hasColumn('laporan_keuangan', 'kecamatan_id')) {
            Schema::table('laporan_keuangan', function (Blueprint $table) {
                $table->dropForeign(['kecamatan_id']);
                $table->dropColumn('kecamatan_id');
            });
        }
    }
};
