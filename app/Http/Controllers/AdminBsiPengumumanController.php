<?php namespace App\Http\Controllers;

use CRUDBooster;
use DB;
use App\Http\Controllers\Concerns\CekPeran;
use App\Http\Controllers\Concerns\UnduhLampiran;
use App\Services\PengumumanBsiService;
use App\Services\WilayahBsiService;

/** BSI/Admin mengirim pengumuman (mis. harga sampah terbaru) ke satu atau beberapa BSU. */
class AdminBsiPengumumanController extends \crocodicstudio\crudbooster\controllers\CBController
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
        $this->table = "bsi_pengumuman";
        $this->col = [];
        $this->form = [];
    }

    private function aman() { $this->wajibPeran([1, 2, 'bsi']); }

    /** Akun BSI dibatasi ke BSU di kelurahannya; Super Admin/Admin tidak dibatasi (null). */
    private function batasBsi() { return $this->adalahBsi() ? (int) CRUDBooster::myId() : null; }

    public function getIndex()
    {
        $this->aman();
        $this->cbLoader();
        $q = DB::table('bsi_pengumuman as p')
            ->leftJoin('cms_users as u', 'u.id', '=', 'p.created_by')
            ->leftJoin('cms_users as d', 'd.id', '=', 'p.dihapus_oleh');
        if ($this->adalahBsi()) {
            $q->where('p.created_by', (int) CRUDBooster::myId());   // BSI hanya melihat pengumumannya sendiri
        }
        $rows = $q
            ->selectRaw('p.id, p.judul, p.created_at, p.dihapus_at, u.name as pengirim, d.name as penghapus,
                (SELECT COUNT(*) FROM bsi_pengumuman_tujuan t WHERE t.pengumuman_id = p.id) AS tujuan,
                (SELECT COUNT(*) FROM bsi_pengumuman_tujuan t WHERE t.pengumuman_id = p.id AND t.dibaca_at IS NOT NULL) AS dibaca,
                (SELECT COUNT(*) FROM bsi_pengumuman_file f WHERE f.pengumuman_id = p.id) AS lampiran')
            ->orderByDesc('p.id')->paginate(20);
        return view('admin.bsi_pengumuman_index', ['page_title' => 'Pengumuman ke BSU', 'rows' => $rows]);
    }

    private function pengumumanMilik($id)
    {
        $p = DB::table('bsi_pengumuman')->where('id', (int) $id)->first();
        if (!$p || ($this->adalahBsi() && (int) $p->created_by !== (int) CRUDBooster::myId())) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'Pengumuman tidak ditemukan.', 'warning');
        }
        return $p;
    }

    /** Hapus langsung (lunak): pengumuman ditandai DIHAPUS di daftar BSI maupun di kotak masuk BSU. */
    public function postHapus()
    {
        $this->aman();
        $p = $this->pengumumanMilik(request('id'));
        if ($p->dihapus_at) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'Pengumuman ini sudah dihapus.', 'warning');
        }
        DB::table('bsi_pengumuman')->where('id', $p->id)->update(['dihapus_at' => now(), 'dihapus_oleh' => CRUDBooster::myId(), 'updated_at' => now()]);
        CRUDBooster::insertLog('Menghapus pengumuman BSI #' . $p->id);
        CRUDBooster::redirect(CRUDBooster::mainpath(), 'Pengumuman dihapus. BSU tujuan melihatnya bertanda DIHAPUS tanpa isi.', 'success');
    }

    public function postPulihkan()
    {
        $this->aman();
        $p = $this->pengumumanMilik(request('id'));
        if (!$p->dihapus_at) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'Pengumuman ini tidak dalam keadaan dihapus.', 'warning');
        }
        DB::table('bsi_pengumuman')->where('id', $p->id)->update(['dihapus_at' => null, 'dihapus_oleh' => null, 'updated_at' => now()]);
        CRUDBooster::insertLog('Memulihkan pengumuman BSI #' . $p->id);
        CRUDBooster::redirect(CRUDBooster::mainpath(), 'Pengumuman dipulihkan.', 'success');
    }

    public function getKirim()
    {
        $this->aman();
        $this->cbLoader();
        $svc = app(PengumumanBsiService::class);
        $bsu = $svc->daftarBsu($this->batasBsi());
        return view('admin.bsi_pengumuman_kirim', [
            'page_title' => 'Kirim Pengumuman ke BSU', 'bsu' => $bsu, 'bannerBsi' => $this->bannerPemilikHtml(),
            'kecamatan' => $bsu->pluck('kecamatan')->filter()->unique()->sort()->values(),
            'jmlHarga' => WilayahBsiService::batasiPemilik(DB::table('bsi_harga')->where('is_active', 1)->whereNull('dihapus_at'), $this->pemilikHargaAktif())->count(),
            'batasUnggah' => min($this->keByte(ini_get('upload_max_filesize')), $this->keByte(ini_get('post_max_size'))),
        ]);
    }

    public function postKirim()
    {
        $this->aman();
        try {
            $r = app(PengumumanBsiService::class)->kirim(
                (int) CRUDBooster::myId(), request('judul'), request('isi'), (array) request('bsu', []), (bool) request('sertakan_harga'),
                $this->berkas(), (bool) request('lampirkan_excel'), $this->batasBsi(), $this->pemilikHargaAktif()
            );
        } catch (\InvalidArgumentException $e) {
            return redirect(CRUDBooster::mainpath('kirim'))->withInput()->with(['message' => $e->getMessage(), 'message_type' => 'warning']);
        }
        CRUDBooster::insertLog('Mengirim pengumuman BSI #' . $r['id'] . ' ke ' . $r['jumlah'] . ' BSU');
        CRUDBooster::redirect(CRUDBooster::mainpath('rincian/' . $r['id']), 'Pengumuman terkirim ke ' . $r['jumlah'] . ' BSU' . ($r['lampiran'] ? ' dengan ' . $r['lampiran'] . ' lampiran' : '') . '.', 'success');
    }

    public function getRincian($id)
    {
        $this->aman();
        $this->cbLoader();
        $p = DB::table('bsi_pengumuman')->where('id', (int) $id)->first();
        if (!$p || ($this->adalahBsi() && (int) $p->created_by !== (int) CRUDBooster::myId())) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'Pengumuman tidak ditemukan.', 'warning');
        }
        $tujuan = DB::table('bsi_pengumuman_tujuan as t')->join('cms_users as u', 'u.id', '=', 't.bsu_id')
            ->where('t.pengumuman_id', $p->id)->orderBy('u.name')->get(['u.name', 'u.kecamatan', 'u.kelurahan', 't.dibaca_at']);
        return view('admin.bsi_pengumuman_rincian', [
            'page_title' => 'Rincian Pengumuman', 'p' => $p, 'tujuan' => $tujuan,
            'harga' => $p->lampiran_harga ? json_decode($p->lampiran_harga, true) : [],
            'penghapus' => $p->dihapus_oleh ? DB::table('cms_users')->where('id', $p->dihapus_oleh)->value('name') : null,
            'berkas' => DB::table('bsi_pengumuman_file')->where('pengumuman_id', $p->id)->orderBy('id')->get(),
        ]);
    }

    /** Unduh lampiran (BSI/Admin/Super Admin). */
    public function getUnduh($id)
    {
        $this->aman();
        $f = DB::table('bsi_pengumuman_file')->where('id', (int) $id)->first();
        if ($f && $this->adalahBsi()
            && (int) DB::table('bsi_pengumuman')->where('id', $f->pengumuman_id)->value('created_by') !== (int) CRUDBooster::myId()) {
            $f = null;   // lampiran milik BSI lain: diperlakukan sebagai tidak ditemukan
        }
        return $this->kirimLampiran($f);
    }

    private function berkas()
    {
        $f = request()->file('lampiran');
        return $f ? array_values(is_array($f) ? $f : [$f]) : [];
    }

    private function keByte($v)
    {
        $v = trim((string) $v);
        $n = (float) $v;
        switch (strtolower(substr($v, -1))) {
            case 'g': $n *= 1024;   // no break
            case 'm': $n *= 1024;   // no break
            case 'k': $n *= 1024;
        }
        return (int) $n;
    }
}
