<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * BSI per Kelurahan.
 *
 *  - Satu akun BSI hanya memegang SATU kelurahan, dan satu kelurahan hanya punya SATU BSI.
 *    Kelurahan BSI disimpan di cms_users.kelurahan_id (kolom yang sama dengan BSU); keunikannya dijaga
 *    oleh App\Services\WilayahBsiService.
 *  - Setiap baris harga acuan (bsi_harga) kini punya pemilik: bsi_harga.bsi_id (cms_users.id akun BSI).
 *    bsi_id = NULL berarti "Harga Bersama" (daftar lama sebelum fitur ini). Baris lama TIDAK diubah,
 *    jadi semua BSU yang sudah terhubung tetap berjalan. Super Admin menyerahkan daftar lama ke BSI
 *    lewat menu "Pantau BSI".
 *  - Menu "Pantau BSI" (khusus Super Admin): BSI mana memegang kelurahan mana, BSU mana yang tercakup,
 *    dan dari BSI mana harga acuan setiap BSU berasal.
 *
 * Aman dijalankan pada database berisi data: hanya MENAMBAH kolom/menu. Idempotent.
 */
class BsiPerKelurahan extends Migration
{
    public function up()
    {
        if (Schema::hasTable('bsi_harga') && !Schema::hasColumn('bsi_harga', 'bsi_id')) {
            Schema::table('bsi_harga', function (Blueprint $t) {
                $t->unsignedInteger('bsi_id')->nullable();   // cms_users.id akun BSI pemilik; NULL = Harga Bersama
                $t->index('bsi_id', 'bsi_harga_bsi_idx');
            });
        }

        if (Schema::hasTable('cms_users') && !Schema::hasColumn('cms_users', 'kelurahan_id')) {
            // Normalnya sudah ada dari migrasi rename_kecamatan_to_kelurahan; jaga-jaga untuk database lama.
            Schema::table('cms_users', function (Blueprint $t) {
                $t->unsignedInteger('kelurahan_id')->nullable();
            });
        }

        $this->daftarMenu();
    }

    private function daftarMenu()
    {
        if (!Schema::hasTable('cms_moduls') || !Schema::hasTable('cms_menus')) {
            return;
        }
        $now = now();
        $nama = 'Pantau BSI';
        $icon = 'fa fa-sitemap';
        $path = 'pantau_bsi';
        $controller = 'AdminPantauBsiController';

        $moduleId = DB::table('cms_moduls')->where('path', $path)->value('id');
        if (!$moduleId) {
            $moduleId = DB::table('cms_moduls')->insertGetId([
                'name' => $nama, 'icon' => $icon, 'path' => $path, 'table_name' => 'cms_users',
                'controller' => $controller, 'is_protected' => 0, 'is_active' => 0, 'created_at' => $now,
            ]);
        }
        if (!DB::table('cms_privileges_roles')->where('id_cms_moduls', $moduleId)->where('id_cms_privileges', 1)->exists()) {
            DB::table('cms_privileges_roles')->insert([
                'is_visible' => 1, 'is_create' => 0, 'is_read' => 1, 'is_edit' => 0, 'is_delete' => 0,
                'id_cms_privileges' => 1, 'id_cms_moduls' => $moduleId, 'created_at' => $now,
            ]);
        }

        $menuPath = $controller . 'GetIndex';
        $menuId = DB::table('cms_menus')->where('path', $menuPath)->value('id');
        if (!$menuId) {
            $menuId = DB::table('cms_menus')->insertGetId([
                'name' => $nama, 'type' => 'Route', 'path' => $menuPath, 'color' => 'normal', 'icon' => $icon,
                'parent_id' => 0, 'is_active' => 1, 'is_dashboard' => 0, 'id_cms_privileges' => 1,
                'sorting' => (int) DB::table('cms_menus')->max('sorting') + 1, 'created_at' => $now,
            ]);
        }
        if (!DB::table('cms_menus_privileges')->where('id_cms_menus', $menuId)->where('id_cms_privileges', 1)->exists()) {
            DB::table('cms_menus_privileges')->insert(['id_cms_menus' => $menuId, 'id_cms_privileges' => 1]);
        }
    }

    public function down()
    {
        // Kolom bsi_id dan menu sengaja tidak dihapus agar kepemilikan harga dan hak akses tidak hilang.
    }
}
