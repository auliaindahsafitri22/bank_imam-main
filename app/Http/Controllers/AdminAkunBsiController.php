<?php namespace App\Http\Controllers;

use CRUDBooster;
use DB;
use App\Http\Controllers\Concerns\CekPeran;
use App\Services\WilayahBsiService;
use App\Services\HapusBsiService;

/**
 * Super Admin membuat dan mengelola akun BSI (Bank Sampah Induk). Akun tidak dihapus, cukup dinonaktifkan.
 * Satu akun BSI memegang SATU kelurahan, dan satu kelurahan hanya punya SATU BSI; BSU di kelurahan itu
 * memakai harga acuan BSI tersebut (lihat WilayahBsiService dan menu Pantau BSI).
 */
class AdminAkunBsiController extends \crocodicstudio\crudbooster\controllers\CBController
{
    use CekPeran;

    public function cbInit()
    {
        $this->title_field = "name";
        $this->limit = "20";
        $this->orderby = "name,asc";
        $this->global_privilege = false;
        $this->button_table_action = true;
        $this->button_bulk_action = false;
        $this->button_action_style = "button_icon";
        $this->button_add = true;
        $this->button_edit = true;
        $this->button_delete = false;
        $this->button_detail = true;
        $this->button_show = true;
        $this->button_filter = false;
        $this->button_import = false;
        $this->button_export = false;
        $this->table = "cms_users";

        $this->col = [];
        $this->col[] = ["label" => "Nama", "name" => "name"];
        $this->col[] = ["label" => "Email (login)", "name" => "email"];
        $this->col[] = ["label" => "Kelurahan", "name" => "kelurahan_id", "join" => "kelurahans,nama_kelurahan"];
        $this->col[] = ["label" => "BSU Tercakup", "name" => "id", 'callback_php' => 'count(app(\\App\\Services\\WilayahBsiService::class)->bsuIdsBsi($row->id))'];
        $this->col[] = ["label" => "No. WhatsApp", "name" => "no_whatsapp"];
        $this->col[] = ["label" => "Status", "name" => "status", 'callback_php' => '$row->dihapus_at ? "<span class=\"label label-danger\">DIHAPUS ".date("d/m/Y", strtotime($row->dihapus_at))."</span>" : $row->status'];
        $this->col[] = ["label" => "Dibuat", "name" => "created_at", 'callback_php' => '$row->created_at ? date("d/m/Y", strtotime($row->created_at)) : "-"'];

        $this->form = [];
        $this->form[] = ['label' => 'Nama Akun', 'name' => 'name', 'type' => 'text', 'validation' => 'required|string|min:3|max:150', 'width' => 'col-sm-8', 'help' => 'Contoh: BSI Jakarta Barat'];
        $this->form[] = ['label' => 'Email (untuk login)', 'name' => 'email', 'type' => 'email', 'validation' => 'required|email|unique:cms_users,email', 'width' => 'col-sm-8'];
        $this->form[] = ['label' => 'Kelurahan (wilayah BSI)', 'name' => 'kelurahan_id', 'type' => 'select', 'datatable' => 'kelurahans,nama_kelurahan',
            'datatable_where' => 'deleted_at IS NULL', 'validation' => 'required|integer', 'width' => 'col-sm-8',
            'help' => 'Satu BSI hanya memegang satu kelurahan, dan satu kelurahan hanya punya satu BSI. Semua BSU di kelurahan ini memakai harga acuan akun ini. Mengubah kelurahan memindahkan cakupan BSU; cek hasilnya di menu Pantau BSI.'];
        $this->form[] = ['label' => 'No. WhatsApp', 'name' => 'no_whatsapp', 'type' => 'text', 'validation' => 'nullable|string|max:30', 'width' => 'col-sm-4'];
        $this->form[] = ['label' => 'Password', 'name' => 'password', 'type' => 'password', 'validation' => 'min:8|max:100', 'width' => 'col-sm-8', 'help' => 'Wajib saat membuat akun (min. 8 karakter). Saat edit, kosongkan jika tidak ingin mengganti password.'];
        $this->form[] = ['label' => 'Status', 'name' => 'status', 'type' => 'select', 'dataenum' => 'Active;Inactive', 'validation' => 'required|in:Active,Inactive', 'width' => 'col-sm-3', 'help' => 'Akun nonaktif otomatis dikeluarkan dan tidak bisa masuk ke CMS.'];

        $this->addaction = [];
        $this->addaction[] = ['label' => 'Hapus', 'url' => CRUDBooster::mainpath('hapus/[id]'), 'icon' => 'fa fa-trash', 'color' => 'danger', 'showIf' => "'[dihapus_at]' == ''"];
        $this->addaction[] = ['label' => 'Pulihkan', 'url' => CRUDBooster::mainpath('pulihkan/[id]'), 'icon' => 'fa fa-undo', 'color' => 'success', 'showIf' => "'[dihapus_at]' != ''"];
        $this->index_button = [];
        $this->button_selected = [];
        $this->alert = [];
        $this->table_row_color = [];
        $this->index_statistic = [];
        $this->script_js = $this->skripHapusLangsung('Hapus akun BSI ini? Akun dinonaktifkan, kelurahannya dilepas, semua harga acuannya ikut dihapus dan BSU terkait diputus (harga nasabah tetap). Bisa dipulihkan.', 'Pulihkan akun BSI ini?');
        $this->pre_index_html = null;
        $this->post_index_html = null;
        $this->load_js = [];
        $this->style_css = null;
        $this->load_css = [];
    }

    public function getIndex() { $this->wajibPeran([1]); return parent::getIndex(); }

    public function hook_query_index(&$query)
    {
        $query->where('cms_users.id_cms_privileges', $this->idBsi());
        $query->addSelect('cms_users.dihapus_at');   // akun yang dihapus tetap tampil, bertanda DIHAPUS
    }

    private function akunBsiAtauKembali($id)
    {
        $a = DB::table('cms_users')->where('id', (int) $id)->where('id_cms_privileges', $this->idBsi())->first();
        if (!$a) { CRUDBooster::redirect(CRUDBooster::mainpath(), 'Akun BSI tidak ditemukan.', 'warning'); }
        return $a;
    }

    /** Langkah 1 hapus: halaman konfirmasi dengan akibatnya (data berubah hanya lewat POST). */
    public function getHapus($id)
    {
        $this->wajibPeran([1]);
        $this->cbLoader();
        $a = $this->akunBsiAtauKembali($id);
        if ($a->dihapus_at) { CRUDBooster::redirect(CRUDBooster::mainpath(), 'Akun ini sudah dihapus.', 'warning'); }
        if ((int) $a->id === (int) CRUDBooster::myId()) { CRUDBooster::redirect(CRUDBooster::mainpath(), 'Anda tidak bisa menghapus akun Anda sendiri.', 'warning'); }
        $wilayah = app(WilayahBsiService::class);
        $kel = $a->kelurahan_id ? DB::table('kelurahans')->where('id', $a->kelurahan_id)->value('nama_kelurahan') : null;
        $harga = DB::table('bsi_harga')->where('bsi_id', $a->id)->whereNull('dihapus_at')->pluck('id');
        $sampah = DB::table('sampah')->whereIn('bsi_harga_id', $harga->all() ?: [0]);
        return view('admin.hapus_konfirmasi', [
            'page_title' => 'Hapus Akun BSI', 'judul' => 'Hapus akun BSI "' . strip_tags($a->name) . '"?',
            'rincian' => [
                'Akun <b>dinonaktifkan</b> dan tidak bisa masuk lagi. Kelurahan' . ($kel ? ' <b>' . e($kel) . '</b>' : '') . ' dilepas sehingga bisa dipegang BSI lain.',
                'Seluruh <b>' . $harga->count() . ' jenis harga acuan</b> milik akun ini ikut dihapus. <b>' . (clone $sampah)->distinct()->count('admin_id') . ' BSU</b> ('
                    . (clone $sampah)->count() . ' jenis sampah) yang terhubung diputus: harga beli nasabah tidak berubah, BSU diberi notifikasi.',
                count($wilayah->bsuIdsBsi($a->id)) . ' BSU di kelurahan ini akan memakai Harga Bersama sampai ada BSI baru.',
                'Data tidak dibuang: akun dan riwayatnya tetap tercatat dengan tanda <span class="label label-danger">DIHAPUS</span> dan bisa dipulihkan. Email akun tidak bisa dipakai akun baru.',
            ],
            'id' => $a->id, 'aksi' => CRUDBooster::mainpath('hapus'), 'kembali' => CRUDBooster::mainpath(), 'tombol' => 'Ya, hapus akun BSI',
        ]);
    }

    public function postHapus()
    {
        $this->wajibPeran([1]);
        try {
            $r = app(HapusBsiService::class)->hapusBsi((int) request('id'), (int) CRUDBooster::myId());
        } catch (\InvalidArgumentException $e) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), $e->getMessage(), 'warning');
        }
        CRUDBooster::insertLog('Menghapus akun BSI #' . (int) request('id') . ': ' . json_encode($r));
        CRUDBooster::redirect(CRUDBooster::mainpath(), 'Akun BSI dihapus. ' . $r['harga'] . ' jenis harga ikut dihapus; ' . $r['bsu'] . ' BSU kini memakai Harga Bersama.', 'success');
    }

    public function getPulihkan($id)
    {
        $this->wajibPeran([1]);
        $this->cbLoader();
        $a = $this->akunBsiAtauKembali($id);
        if (!$a->dihapus_at) { CRUDBooster::redirect(CRUDBooster::mainpath(), 'Akun ini tidak dalam keadaan dihapus.', 'warning'); }
        $kel = $a->dihapus_kelurahan_id ? DB::table('kelurahans')->where('id', $a->dihapus_kelurahan_id)->value('nama_kelurahan') : null;
        return view('admin.hapus_konfirmasi', [
            'page_title' => 'Pulihkan Akun BSI', 'judul' => 'Pulihkan akun BSI "' . strip_tags($a->name) . '"?', 'bahaya' => false, 'ikon' => 'undo',
            'rincian' => [
                'Dihapus pada ' . date('d/m/Y H:i', strtotime($a->dihapus_at)) . '. Kelurahan sebelumnya: <b>' . e($kel ?: '-') . '</b> (harus masih kosong, kalau tidak pemulihan ditolak).',
                'Jenis harga yang ikut terhapus bersama akun dipulihkan. Akun kembali berstatus <b>Inactive</b>; aktifkan lewat Ubah. Sambungan BSU tidak dipulihkan otomatis.',
            ],
            'id' => $a->id, 'aksi' => CRUDBooster::mainpath('pulihkan'), 'kembali' => CRUDBooster::mainpath(), 'tombol' => 'Pulihkan',
        ]);
    }

    public function postPulihkan()
    {
        $this->wajibPeran([1]);
        try {
            $r = app(HapusBsiService::class)->pulihkanBsi((int) request('id'));
        } catch (\InvalidArgumentException $e) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), $e->getMessage(), 'warning');
        }
        CRUDBooster::insertLog('Memulihkan akun BSI #' . (int) request('id'));
        CRUDBooster::redirect(CRUDBooster::mainpath(), 'Akun BSI dipulihkan (status Inactive), ' . $r['harga'] . ' jenis harga ikut dipulihkan.', 'success');
    }

    public function hook_before_add(&$postdata)
    {
        $this->wajibPeran([1]);
        if ($this->idBsi() < 1) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'Role BSI belum ada. Jalankan migrasi fitur_baru terlebih dahulu.', 'warning');
        }
        if (empty($postdata['password'])) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'Password wajib diisi untuk akun baru.', 'warning');
        }
        $this->cekKelurahan($postdata['kelurahan_id'] ?? null, null);
        $postdata['id_cms_privileges'] = $this->idBsi();   // dipaksa BSI, tidak percaya input form
        $postdata['no_whatsapp'] = ($postdata['no_whatsapp'] ?? '') !== '' ? $postdata['no_whatsapp'] : '0';
    }

    public function hook_before_edit(&$postdata, $id)
    {
        $this->wajibPeran([1]);
        $this->pastikanBsi($id);
        $this->tolakBilaDihapus($id);
        $this->cekKelurahan($postdata['kelurahan_id'] ?? null, $id);
        unset($postdata['id_cms_privileges']);
    }

    /** Satu BSI = satu kelurahan: kelurahan wajib ada dan belum dipegang BSI lain. */
    private function cekKelurahan($kelurahanId, $kecualiId)
    {
        try {
            app(WilayahBsiService::class)->pastikanKelurahanTersedia($kelurahanId, $kecualiId);
        } catch (\InvalidArgumentException $e) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), $e->getMessage(), 'warning');
        }
    }

    public function hook_after_add($id)
    {
        CRUDBooster::insertLog('Membuat akun BSI #' . $id . ' untuk kelurahan #' . DB::table('cms_users')->where('id', $id)->value('kelurahan_id'));
    }

    public function getEdit($id)
    {
        $this->wajibPeran([1]);
        $this->pastikanBsi($id);
        $this->tolakBilaDihapus($id);
        return parent::getEdit($id);
    }

    /** Akun yang dihapus tidak bisa diubah (mis. diaktifkan kembali) sebelum dipulihkan. */
    private function tolakBilaDihapus($id)
    {
        if (DB::table('cms_users')->where('id', (int) $id)->whereNotNull('dihapus_at')->exists()) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'Akun yang dihapus tidak bisa diubah. Pulihkan dulu.', 'warning');
        }
    }
    public function getDetail($id) { $this->wajibPeran([1]); $this->pastikanBsi($id); return parent::getDetail($id); }

    /** Halaman ini hanya boleh menyentuh akun BSI, bukan akun peran lain. */
    private function pastikanBsi($id)
    {
        if (!DB::table('cms_users')->where('id', $id)->where('id_cms_privileges', $this->idBsi())->exists()) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'Akun BSI tidak ditemukan.', 'warning');
        }
    }
}
