<?php namespace App\Http\Controllers;

use CRUDBooster;

/**
 * Harga & Margin kini bagian dari Master Sampah (AdminSampahController: /sampah/harga, /sampah/harga-riwayat).
 * Controller ini dipertahankan hanya agar alamat lama (bookmark, notifikasi lama di lonceng CMS) tetap berfungsi.
 */
class AdminHargaBsuController extends \crocodicstudio\crudbooster\controllers\CBController
{
    public function cbInit()
    {
        $this->title_field = "name";
        $this->button_add = false;
        $this->button_edit = false;
        $this->button_delete = false;
        $this->button_detail = false;
        $this->button_show = false;
        $this->button_filter = false;
        $this->button_import = false;
        $this->button_export = false;
        $this->table = "sampah";
        $this->col = [];
        $this->form = [];
    }

    public function getIndex()
    {
        return redirect(CRUDBooster::adminPath('sampah/harga'));
    }

    public function getRiwayat()
    {
        return redirect(CRUDBooster::adminPath('sampah/harga-riwayat'));
    }
}
