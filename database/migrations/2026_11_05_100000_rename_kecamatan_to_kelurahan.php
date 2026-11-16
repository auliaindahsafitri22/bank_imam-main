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
        // 1. Rename or create kelurahans table
        if (Schema::hasTable('kecamatans') && !Schema::hasTable('kelurahans')) {
            Schema::rename('kecamatans', 'kelurahans');
        } elseif (!Schema::hasTable('kelurahans')) {
            Schema::create('kelurahans', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nama_kelurahan', 100);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Rename column nama_kecamatan to nama_kelurahan if needed
        if (Schema::hasTable('kelurahans') && Schema::hasColumn('kelurahans', 'nama_kecamatan') && !Schema::hasColumn('kelurahans', 'nama_kelurahan')) {
            DB::statement("ALTER TABLE `kelurahans` CHANGE COLUMN `nama_kecamatan` `nama_kelurahan` VARCHAR(100)");
        }

        // 3. Update related tables: rename kecamatan_id to kelurahan_id and update foreign keys
        $tables = [
            'cms_users'        => 'id_cms_privileges',
            'transactions'     => 'admin_id',
            'transactions_bsi' => 'admin_id',
            'tukar_poins'      => 'admin_id',
            'tukar_saldo'      => 'admin_id',
            'laporan_keuangan' => 'admin_id',
        ];

        foreach ($tables as $table => $afterColumn) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            // Drop old foreign key if exists
            try {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                    $tableBlueprint->dropForeign("{$table}_kecamatan_id_foreign");
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropForeign(['kecamatan_id']);
                });
            } catch (\Throwable $e) {}

            // Rename column if kecamatan_id exists and kelurahan_id does not
            if (Schema::hasColumn($table, 'kecamatan_id') && !Schema::hasColumn($table, 'kelurahan_id')) {
                DB::statement("ALTER TABLE `{$table}` CHANGE COLUMN `kecamatan_id` `kelurahan_id` INT UNSIGNED NULL");
            } elseif (!Schema::hasColumn($table, 'kelurahan_id')) {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($afterColumn) {
                    $tableBlueprint->unsignedInteger('kelurahan_id')->nullable()->after($afterColumn);
                });
            }

            // Re-add foreign key on kelurahan_id referencing kelurahans(id)
            if (Schema::hasTable('kelurahans') && Schema::hasColumn($table, 'kelurahan_id')) {
                try {
                    Schema::table($table, function (Blueprint $tableBlueprint) {
                        $tableBlueprint->foreign('kelurahan_id')
                                      ->references('id')->on('kelurahans')
                                      ->onDelete('set null');
                    });
                } catch (\Throwable $e) {}
            }
        }

        // 4. Update cms_moduls
        if (Schema::hasTable('cms_moduls')) {
            $existing = DB::table('cms_moduls')
                ->where('controller', 'AdminKecamatansController')
                ->orWhere('path', 'kecamatan')
                ->orWhere('table_name', 'kecamatans')
                ->first();

            if ($existing) {
                DB::table('cms_moduls')->where('id', $existing->id)->update([
                    'name'       => 'Data Kelurahan',
                    'icon'       => 'fa fa-map-marker',
                    'path'       => 'kelurahan',
                    'table_name' => 'kelurahans',
                    'controller' => 'AdminKelurahansController',
                    'updated_at' => now(),
                ]);
            } else {
                $hasKelurahanModul = DB::table('cms_moduls')->where('controller', 'AdminKelurahansController')->exists();
                if (!$hasKelurahanModul) {
                    $modulId = DB::table('cms_moduls')->insertGetId([
                        'name'         => 'Data Kelurahan',
                        'icon'         => 'fa fa-map-marker',
                        'path'         => 'kelurahan',
                        'table_name'   => 'kelurahans',
                        'controller'   => 'AdminKelurahansController',
                        'is_protected' => 0,
                        'is_active'    => 1,
                        'created_at'   => now(),
                        'updated_at'   => now(),
                    ]);

                    if (Schema::hasTable('cms_privileges_roles')) {
                        $privilegeExists = DB::table('cms_privileges_roles')
                            ->where('id_cms_moduls', $modulId)
                            ->where('id_cms_privileges', 1)
                            ->exists();

                        if (!$privilegeExists) {
                            DB::table('cms_privileges_roles')->insert([
                                'id_cms_moduls'     => $modulId,
                                'id_cms_privileges' => 1,
                                'is_visible'        => 1,
                                'is_create'         => 1,
                                'is_read'           => 1,
                                'is_edit'           => 1,
                                'is_delete'         => 1,
                                'created_at'        => now(),
                                'updated_at'        => now(),
                            ]);
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tables = [
            'cms_users',
            'transactions',
            'transactions_bsi',
            'tukar_poins',
            'tukar_saldo',
            'laporan_keuangan',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($table) {
                    $tableBlueprint->dropForeign("{$table}_kelurahan_id_foreign");
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table($table, function (Blueprint $tableBlueprint) {
                    $tableBlueprint->dropForeign(['kelurahan_id']);
                });
            } catch (\Throwable $e) {}

            if (Schema::hasColumn($table, 'kelurahan_id') && !Schema::hasColumn($table, 'kecamatan_id')) {
                DB::statement("ALTER TABLE `{$table}` CHANGE COLUMN `kelurahan_id` `kecamatan_id` INT UNSIGNED NULL");
            }
        }

        if (Schema::hasTable('kelurahans') && Schema::hasColumn('kelurahans', 'nama_kelurahan') && !Schema::hasColumn('kelurahans', 'nama_kecamatan')) {
            DB::statement("ALTER TABLE `kelurahans` CHANGE COLUMN `nama_kelurahan` `nama_kecamatan` VARCHAR(100)");
        }

        if (Schema::hasTable('kelurahans') && !Schema::hasTable('kecamatans')) {
            Schema::rename('kelurahans', 'kecamatans');
        }

        if (Schema::hasTable('cms_moduls')) {
            DB::table('cms_moduls')->where('controller', 'AdminKelurahansController')->update([
                'name'       => 'Data Kecamatan',
                'icon'       => 'fa fa-map-marker',
                'path'       => 'kecamatan',
                'table_name' => 'kecamatans',
                'controller' => 'AdminKecamatansController',
                'updated_at' => now(),
            ]);
        }
    }
};

