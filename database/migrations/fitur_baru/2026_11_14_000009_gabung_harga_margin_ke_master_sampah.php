<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Harga & Margin digabung ke Master Sampah (menu "Sampah", AdminSampahController).
 *
 * - Menu "Harga & Margin" disembunyikan (is_active = 0), tidak dihapus, sehingga bisa dikembalikan lewat down().
 * - Modul harga_bsu tetap terdaftar: AdminHargaBsuController hanya mengalihkan alamat lama (/admin/harga_bsu)
 *   ke /admin/sampah/harga, supaya bookmark dan notifikasi lama di lonceng CMS tetap berfungsi.
 * - Notifikasi lama yang menunjuk ke /harga_bsu diarahkan langsung ke alamat baru.
 */
class GabungHargaMarginKeMasterSampah extends Migration
{
    private const PATH_MENU = 'AdminHargaBsuControllerGetIndex';

    public function up()
    {
        if (Schema::hasTable('cms_menus')) {
            DB::table('cms_menus')->where('path', self::PATH_MENU)->update(['is_active' => 0]);
        }
        if (Schema::hasTable('cms_notifications')) {
            DB::table('cms_notifications')->where('url', 'like', '%/harga_bsu')
                ->update(['url' => DB::raw("REPLACE(url, '/harga_bsu', '/sampah/harga')")]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('cms_menus')) {
            DB::table('cms_menus')->where('path', self::PATH_MENU)->update(['is_active' => 1]);
        }
    }
}
