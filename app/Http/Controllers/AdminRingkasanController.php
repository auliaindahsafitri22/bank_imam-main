<?php namespace App\Http\Controllers;

use CRUDBooster;
use DB;
use App\Http\Controllers\Concerns\CekPeran;
use App\Services\KasService;

/** Dashboard ringkasan: BSU (kas, setoran, saldo nasabah, harga, jadwal), BSI, dan pusat. */
class AdminRingkasanController extends \crocodicstudio\crudbooster\controllers\CBController
{
    use CekPeran;

    public function cbInit()
    {
        $this->title_field = "name";
        $this->global_privilege = true;
        $this->button_add = false;
        $this->button_edit = false;
        $this->button_delete = false;
        $this->button_detail = false;
        $this->button_show = false;
        $this->button_filter = false;
        $this->button_import = false;
        $this->button_export = false;
        $this->table = "cms_users";
        $this->col = [];
        $this->form = [];
    }

    public function getIndex()
    {
        $this->wajibPeran([1, 2, 4, 'bsi']);
        $this->cbLoader();
        $b = \App\Support\RingkasanDashboard::bagian();
        $v = $b[0] === 'admin.partials.ringkasan_bsu_isi' ? 'admin.ringkasan_bsu' : 'admin.ringkasan_pusat';
        return view($v, $b[1] + ['page_title' => 'Ringkasan']);
    }
}
