<?php namespace App\Http\Controllers;

use CRUDBooster;
use DB;
use App\Http\Controllers\Concerns\CekPeran;
use App\Http\Controllers\Concerns\UnduhLampiran;

/** Kotak masuk pengumuman dari BSI untuk BSU. Hanya pengumuman yang ditujukan ke BSU tersebut. */
class AdminPesanBsiController extends \crocodicstudio\crudbooster\controllers\CBController
{
    use CekPeran, UnduhLampiran;

    public function cbInit()
    {
        $this->title_field = "judul";
        $this->global_privilege = true;
        $this->button_add = false;
        $this->button_edit = false;
        $this->button_delete = false;
        $this->button_detail = false;
        $this->button_show = false;
        $this->button_filter = false;
        $this->button_import = false;
        $this->button_export = false;
        $this->table = "bsi_pengumuman_tujuan";
        $this->col = [];
        $this->form = [];
    }

    public function getIndex()
    {
        $this->wajibPeran([4]);
        $this->cbLoader();
        $rows = DB::table('bsi_pengumuman_tujuan as t')->join('bsi_pengumuman as p', 'p.id', '=', 't.pengumuman_id')
            ->leftJoin('cms_users as u', 'u.id', '=', 'p.created_by')
            ->where('t.bsu_id', CRUDBooster::myId())->orderByDesc('p.id')
            ->selectRaw('p.id, p.judul, p.created_at, p.dihapus_at, u.name as pengirim, t.dibaca_at, (SELECT COUNT(*) FROM bsi_pengumuman_file f WHERE f.pengumuman_id = p.id) AS lampiran')->paginate(20);
        return view('admin.pesan_bsi_index', ['page_title' => 'Pengumuman dari BSI', 'rows' => $rows]);
    }

    public function getBaca($id)
    {
        $this->wajibPeran([4]);
        $this->cbLoader();
        $uid = (int) CRUDBooster::myId();
        $t = DB::table('bsi_pengumuman_tujuan')->where('pengumuman_id', (int) $id)->where('bsu_id', $uid)->first();
        if (!$t) { CRUDBooster::redirect(CRUDBooster::mainpath(), 'Pengumuman tidak ditemukan.', 'warning'); }

        if (!$t->dibaca_at) {
            DB::table('bsi_pengumuman_tujuan')->where('id', $t->id)->update(['dibaca_at' => now()]);
        }
        DB::table('cms_notifications')->where('id_cms_users', $uid)->where('url', 'like', '%/pesan_bsi/baca/' . (int) $id)->update(['is_read' => 1]);

        $p = DB::table('bsi_pengumuman as p')->leftJoin('cms_users as u', 'u.id', '=', 'p.created_by')
            ->where('p.id', (int) $id)->first(['p.*', 'u.name as pengirim']);
        return view('admin.pesan_bsi_baca', [
            'page_title' => 'Pengumuman dari BSI', 'p' => $p, 'dihapus' => (bool) $p->dihapus_at, 'harga' => ($p->lampiran_harga && !$p->dihapus_at) ? json_decode($p->lampiran_harga, true) : [],
            'berkas' => $p->dihapus_at ? collect() : DB::table('bsi_pengumuman_file')->where('pengumuman_id', (int) $id)->orderBy('id')->get(),
        ]);
    }

    /** Unduh lampiran: hanya BSU yang menjadi tujuan pengumuman tersebut. */
    public function getUnduh($id)
    {
        $this->wajibPeran([4]);
        $f = DB::table('bsi_pengumuman_file as f')
            ->join('bsi_pengumuman_tujuan as t', 't.pengumuman_id', '=', 'f.pengumuman_id')
            ->join('bsi_pengumuman as p', 'p.id', '=', 'f.pengumuman_id')
            ->whereNull('p.dihapus_at')   // pengumuman yang dihapus: lampiran tidak bisa diunduh BSU
            ->where('f.id', (int) $id)->where('t.bsu_id', CRUDBooster::myId())->first(['f.*']);
        return $this->kirimLampiran($f);
    }
}
