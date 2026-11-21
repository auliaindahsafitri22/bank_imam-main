<?php namespace App\Http\Controllers;

use CRUDBooster;
use DB;
use App\Http\Controllers\Concerns\CekPeran;
use App\Exports\DaftarHargaExport;
use App\Services\HargaService;
use App\Services\KatalogHargaService;
use App\Services\WilayahBsiService;
use App\Services\HapusBsiService;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Harga acuan Bank Sampah Induk (BSI). Hanya BSI, Admin, Super Admin.
 * Setiap BSI memegang satu kelurahan dan hanya mengelola daftar harganya sendiri (bsi_harga.bsi_id);
 * Super Admin/Admin memilih BSI yang dikelola lewat pemilih di bagian atas halaman.
 */
class AdminBsiHargaController extends \crocodicstudio\crudbooster\controllers\CBController
{
    use CekPeran;

    private $hargaBaru = null;

    public function cbInit()
    {
        $this->title_field = "nama";
        $this->limit = "20";
        $this->orderby = "nama,asc";
        $this->global_privilege = false;
        $this->button_table_action = true;
        $this->button_bulk_action = false;
        $this->button_action_style = "button_icon";
        $this->button_add = true;
        $this->button_edit = true;
        $this->button_delete = false;   // hapus lewat tombol "Hapus" (hapus lunak: riwayat tetap utuh, ada tanda Dihapus)
        $this->button_detail = true;
        $this->button_show = true;
        $this->button_filter = true;
        $this->button_import = false;
        $this->button_export = true;
        $this->table = "bsi_harga";

        $this->col = [];
        $this->col[] = ["label" => "Jenis Sampah", "name" => "nama"];
        $this->col[] = ["label" => "Kategori", "name" => "kategori"];
        $this->col[] = ["label" => "Harga BSI / kg", "name" => "harga", 'callback_php' => '"Rp. ".number_format($row->harga,0,",",".")'];
        $this->col[] = ["label" => "Poin / kg", "name" => "poin_per_kg"];
        $this->col[] = ["label" => "Status", "name" => "is_active", 'callback_php' => '$row->dihapus_at ? "<span class=\"label label-danger\">DIHAPUS ".date("d/m/Y", strtotime($row->dihapus_at))."</span>" : ($row->is_active ? "Aktif" : "Nonaktif")'];
        $this->col[] = ["label" => "Diperbarui", "name" => "updated_at", 'callback_php' => '$row->updated_at ? date("d/m/Y H:i", strtotime($row->updated_at)) : "-"'];

        $this->form = [];
        $this->form[] = ['label' => 'Nama Jenis Sampah', 'name' => 'nama', 'type' => 'text', 'validation' => 'required|string|min:2|max:120', 'width' => 'col-sm-8'];
        $this->form[] = ['label' => 'Kategori', 'name' => 'kategori', 'type' => 'text', 'validation' => 'nullable|string|max:80', 'width' => 'col-sm-8', 'help' => 'Contoh: Plastik, Kertas, Logam, Kaca'];
        $this->form[] = ['label' => 'Penjelasan', 'name' => 'deskripsi', 'type' => 'textarea', 'validation' => 'nullable|string|max:1000', 'width' => 'col-sm-8', 'help' => 'Tampil sebagai penjelasan (ikon ⓘ) di katalog nasabah.'];
        $this->form[] = ['label' => 'Foto Contoh', 'name' => 'image', 'type' => 'upload', 'validation' => 'nullable|image|max:5000', 'width' => 'col-sm-8', 'help' => 'JPG/PNG, maks 5 MB.'];
        $this->form[] = ['label' => 'Harga Acuan BSI per kg (Rp)', 'name' => 'harga', 'type' => 'number', 'validation' => 'required|integer|min:0|max:100000000', 'width' => 'col-sm-8', 'help' => 'Saat harga diubah, harga beli semua BSU yang terhubung ikut dihitung ulang dan BSU diberi notifikasi.'];
        $this->form[] = ['label' => 'Poin per kg (bawaan)', 'name' => 'poin_per_kg', 'type' => 'number', 'validation' => 'required|integer|min:0|max:100000', 'width' => 'col-sm-8'];
        $this->form[] = ['label' => 'Status', 'name' => 'is_active', 'type' => 'select', 'dataenum' => '1|Aktif;0|Nonaktif', 'validation' => 'required|in:0,1', 'width' => 'col-sm-4'];

        $this->addaction = [];
        $this->addaction[] = ['label' => 'Riwayat', 'url' => CRUDBooster::mainpath('riwayat/[id]'), 'icon' => 'fa fa-history', 'color' => 'info'];
        $this->addaction[] = ['label' => 'Hapus', 'url' => CRUDBooster::mainpath('hapus/[id]'), 'icon' => 'fa fa-trash', 'color' => 'danger', 'showIf' => "'[dihapus_at]' == ''"];
        $this->addaction[] = ['label' => 'Pulihkan', 'url' => CRUDBooster::mainpath('pulihkan/[id]'), 'icon' => 'fa fa-undo', 'color' => 'success', 'showIf' => "'[dihapus_at]' != ''"];

        $this->index_button = [];
        $this->index_button[] = ['label' => 'Isi Harga Cepat', 'url' => CRUDBooster::mainpath('isi-cepat'), 'icon' => 'fa fa-table', 'color' => 'primary'];
        $this->index_button[] = ['label' => 'Impor dari File', 'url' => CRUDBooster::mainpath('impor'), 'icon' => 'fa fa-upload', 'color' => 'warning'];
        $this->index_button[] = ['label' => 'Kirim Pengumuman ke BSU', 'url' => CRUDBooster::adminPath('bsi_pengumuman/kirim'), 'icon' => 'fa fa-bullhorn', 'color' => 'success'];
        $this->index_button[] = request('dihapus')
            ? ['label' => 'Tampilkan yang aktif', 'url' => CRUDBooster::mainpath(), 'icon' => 'fa fa-list', 'color' => 'default']
            : ['label' => 'Tampilkan yang dihapus', 'url' => CRUDBooster::mainpath() . '?dihapus=1', 'icon' => 'fa fa-trash-o', 'color' => 'default'];
        $this->index_button[] = ['label' => 'Riwayat Semua Perubahan', 'url' => CRUDBooster::mainpath('riwayat'), 'icon' => 'fa fa-history', 'color' => 'info'];

        $this->button_selected = [];
        $this->alert = [];
        $this->table_row_color = [];
        $this->index_statistic = [];
        $this->script_js = $this->skripSaran() . $this->skripHapusLangsung('Hapus harga acuan ini? BSU yang terhubung diputus (harga beli nasabah tetap). Data tetap tercatat bertanda DIHAPUS dan bisa dipulihkan.', 'Pulihkan harga acuan ini?');
        $this->pre_index_html = $this->bannerPemilikHtml();
        $this->post_index_html = null;
        $this->load_js = [];
        $this->style_css = null;
        $this->load_css = [];
    }

    public function getIndex()
    {
        $this->wajibPeran([1, 2, 'bsi']);
        return parent::getIndex();
    }

    /** Service katalog/impor yang sudah dibatasi ke daftar harga BSI yang sedang dikelola. */
    private function katalogSvc()
    {
        return app(KatalogHargaService::class)->untukPemilik($this->pemilikHargaAktif());
    }

    /** Daftar hanya berisi harga milik BSI yang sedang dikelola (BSI lain tidak terlihat). */
    public function hook_query_index(&$query)
    {
        WilayahBsiService::batasiPemilik($query, $this->pemilikHargaAktif(), 'bsi_harga.bsi_id');
        $query->addSelect('bsi_harga.dihapus_at');
        // Bawaan: hanya yang aktif. Dengan ?dihapus=1 hanya yang sudah dihapus (bertanda DIHAPUS).
        if (request('dihapus')) { $query->whereNotNull('bsi_harga.dihapus_at'); }
        else { $query->whereNull('bsi_harga.dihapus_at'); }
    }

    private function barisAktifAtauKembali($id)
    {
        $r = DB::table('bsi_harga')->where('id', (int) $id)->first();
        if (!$r) { CRUDBooster::redirect(CRUDBooster::mainpath(), 'Jenis harga acuan tidak ditemukan.', 'warning'); }
        return $r;
    }

    /** Langkah 1 hapus: halaman konfirmasi (tidak mengubah data; perubahan lewat POST). */
    public function getHapus($id)
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $this->wajibHargaMilikBsi($id);
        $this->cbLoader();
        $r = $this->barisAktifAtauKembali($id);
        if ($r->dihapus_at) { CRUDBooster::redirect(CRUDBooster::mainpath(), 'Jenis ini sudah dihapus.', 'warning'); }
        $terhubung = DB::table('sampah')->where('bsi_harga_id', $r->id);
        return view('admin.hapus_konfirmasi', [
            'page_title' => 'Hapus Harga Acuan', 'judul' => 'Hapus harga acuan "' . strip_tags($r->nama) . '"?',
            'rincian' => [
                'Harga acuan saat ini <b>Rp ' . number_format($r->harga, 0, ',', '.') . '/kg</b>.',
                '<b>' . (clone $terhubung)->distinct()->count('admin_id') . ' BSU</b> (' . (clone $terhubung)->count() . ' jenis sampah) masih terhubung. Sambungannya akan diputus: <b>harga beli nasabah tidak berubah</b>, margin dikosongkan, dan BSU diberi notifikasi.',
                'Data tidak dibuang: riwayat tetap ada dengan tanda <span class="label label-danger">DIHAPUS</span> dan bisa dipulihkan (sambungan BSU tidak ikut dipulihkan).',
            ],
            'id' => $r->id, 'aksi' => CRUDBooster::mainpath('hapus'), 'kembali' => CRUDBooster::mainpath(), 'tombol' => 'Ya, hapus',
        ]);
    }

    public function postHapus()
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $this->wajibHargaMilikBsi((int) request('id'));
        try {
            $r = app(HapusBsiService::class)->hapusHarga((int) request('id'), (int) CRUDBooster::myId());
        } catch (\InvalidArgumentException $e) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), $e->getMessage(), 'warning');
        }
        CRUDBooster::insertLog('Menghapus harga acuan BSI #' . (int) request('id') . ': ' . json_encode(['bsu' => $r['bsu'], 'sampah' => $r['sampah']]));
        CRUDBooster::redirect(CRUDBooster::mainpath(), 'Harga acuan dihapus. ' . $r['bsu'] . ' BSU diputus dari jenis ini dan diberi notifikasi.', 'success');
    }

    public function getPulihkan($id)
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $this->wajibHargaMilikBsi($id);
        $this->cbLoader();
        $r = $this->barisAktifAtauKembali($id);
        if (!$r->dihapus_at) { CRUDBooster::redirect(CRUDBooster::mainpath(), 'Jenis ini tidak dalam keadaan dihapus.', 'warning'); }
        return view('admin.hapus_konfirmasi', [
            'page_title' => 'Pulihkan Harga Acuan', 'judul' => 'Pulihkan harga acuan "' . strip_tags($r->nama) . '"?', 'bahaya' => false, 'ikon' => 'undo',
            'rincian' => ['Dihapus pada ' . date('d/m/Y H:i', strtotime($r->dihapus_at)) . '. Jenis akan kembali muncul di daftar aktif dengan harga Rp ' . number_format($r->harga, 0, ',', '.') . '/kg.',
                'BSU yang sempat diputus <b>tidak</b> tersambung otomatis; mereka menghubungkan ulang di Harga &amp; Margin.'],
            'id' => $r->id, 'aksi' => CRUDBooster::mainpath('pulihkan'), 'kembali' => CRUDBooster::mainpath() . '?dihapus=1', 'tombol' => 'Pulihkan',
        ]);
    }

    public function postPulihkan()
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $this->wajibHargaMilikBsi((int) request('id'));
        try {
            app(HapusBsiService::class)->pulihkanHarga((int) request('id'));
        } catch (\InvalidArgumentException $e) {
            CRUDBooster::redirect(CRUDBooster::mainpath() . '?dihapus=1', $e->getMessage(), 'warning');
        }
        CRUDBooster::insertLog('Memulihkan harga acuan BSI #' . (int) request('id'));
        CRUDBooster::redirect(CRUDBooster::mainpath(), 'Harga acuan dipulihkan.', 'success');
    }

    public function getEdit($id)
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $this->wajibHargaMilikBsi($id);
        if (DB::table('bsi_harga')->where('id', (int) $id)->whereNotNull('dihapus_at')->exists()) {
            CRUDBooster::redirect(CRUDBooster::mainpath() . '?dihapus=1', 'Jenis yang dihapus tidak bisa diubah. Pulihkan dulu.', 'warning');
        }
        return parent::getEdit($id);
    }

    public function getDetail($id)
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $this->wajibHargaMilikBsi($id);
        return parent::getDetail($id);
    }

    public function hook_before_add(&$postdata)
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $pemilik = $this->pemilikHargaAktif();
        $kunci = KatalogHargaService::kunci($postdata['nama'] ?? '');
        $ada = WilayahBsiService::batasiPemilik(DB::table('bsi_harga'), $pemilik)
            ->whereRaw('LOWER(TRIM(nama)) = ?', [$kunci])->exists();
        if ($ada) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'Jenis sampah "' . strip_tags($postdata['nama']) . '" sudah ada di daftar harga ini.', 'warning');
        }
        $postdata['bsi_id'] = $pemilik;                 // pemilik dipaksa dari sesi, bukan dari input form
        $postdata['updated_by'] = CRUDBooster::myId();
    }

    public function hook_after_add($id)
    {
        $harga = (int) DB::table('bsi_harga')->where('id', $id)->value('harga');
        app(HargaService::class)->catatHargaAwalBsi($id, $harga, CRUDBooster::myId());
    }

    public function hook_before_edit(&$postdata, $id)
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $this->wajibHargaMilikBsi($id);
        if (DB::table('bsi_harga')->where('id', (int) $id)->whereNotNull('dihapus_at')->exists()) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'Jenis yang dihapus tidak bisa diubah.', 'warning');
        }
        unset($postdata['bsi_id']);                      // pemilik tidak boleh dipindah lewat form
        // Harga TIDAK ditulis oleh CRUDBooster; diproses HargaService agar riwayat, sinkron BSU, dan notifikasi jalan.
        $this->hargaBaru = array_key_exists('harga', $postdata) ? (int) $postdata['harga'] : null;
        unset($postdata['harga']);
        $postdata['updated_by'] = CRUDBooster::myId();
    }

    public function hook_after_edit($id)
    {
        if ($this->hargaBaru !== null) {
            $n = app(HargaService::class)->ubahHargaBsi($id, $this->hargaBaru, CRUDBooster::myId());
            if ($n > 0) {
                session()->flash('bsi_info', "Harga diperbarui. $n BSU diberi notifikasi.");
            }
        }
    }

    /** Riwayat perubahan harga acuan (semua atau satu jenis). */
    public function getRiwayat($id = null)
    {
        $this->wajibPeran([1, 2, 'bsi']);
        if ($id) {
            $this->wajibHargaMilikBsi($id);
        }
        $this->cbLoader();
        $data = [
            'page_title' => 'Riwayat Perubahan Harga BSI',
            'bannerBsi' => $this->bannerPemilikHtml(),
            'rows' => app(HargaService::class)->riwayatBsi($id ? (int) $id : null, 300, $id ? false : $this->pemilikHargaAktif()),
            'judul' => $id ? DB::table('bsi_harga')->where('id', (int) $id)->value('nama') : null,
            'kembali' => CRUDBooster::mainpath(),
        ];
        return view('admin.bsi_riwayat', $data);
    }

    // ------------------------------------------------------------ isi harga cepat & impor file

    /** Daftar kategori & jenis (gabungan master BSI + yang sudah dipakai BSU) untuk saran di form. */
    public function getKatalog()
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $svc = $this->katalogSvc();
        $k = $svc->katalog();
        return response()->json([
            'kategori' => $svc->daftarKategori($k),
            'item' => array_map(function ($i) { return ['nama' => $i['nama'], 'kategori' => $i['kategori'], 'ref' => $i['ref']]; }, $k),
        ]);
    }

    public function getIsiCepat()
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $this->cbLoader();
        $svc = $this->katalogSvc();
        $katalog = $svc->katalog();
        $kelompok = [];
        foreach ($katalog as $i) {
            $kelompok[$i['kategori'] !== '' ? $i['kategori'] : 'Tanpa kategori'][] = $i;
        }
        return view('admin.bsi_isi_cepat', [
            'bannerBsi' => $this->bannerPemilikHtml(), 'page_title' => 'Isi Harga Cepat', 'kelompok' => $kelompok, 'kategori' => $svc->daftarKategori($katalog),
            'total' => count($katalog), 'belum' => count(array_filter($katalog, function ($i) { return $i['harga'] === null; })),
        ]);
    }

    public function postIsiCepat()
    {
        $this->wajibPeran([1, 2, 'bsi']);
        try {
            $r = $this->katalogSvc()->simpanCepat((array) request('row', []), (int) CRUDBooster::myId());
        } catch (\InvalidArgumentException $e) {
            CRUDBooster::redirect(CRUDBooster::mainpath('isi-cepat'), $e->getMessage(), 'warning');
        }
        CRUDBooster::insertLog('Isi harga cepat: ' . json_encode($r));
        CRUDBooster::redirect(CRUDBooster::mainpath(), $this->ringkasanHasil($r), 'success');
    }

    public function getImpor()
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $this->cbLoader();
        return view('admin.bsi_impor', ['bannerBsi' => $this->bannerPemilikHtml(), 'page_title' => 'Impor Harga Acuan dari File', 'pratinjau' => null, 'token' => null]);
    }

    public function getTemplate()
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $svc = $this->katalogSvc();
        return Excel::download(new DaftarHargaExport('Harga BSI', KatalogHargaService::judulTemplate(), $svc->barisTemplate()),
            'template-harga-acuan-bsi.xlsx');
    }

    /** Langkah 1: unggah file -> tampilkan pratinjau (belum ada yang disimpan). */
    public function postImpor()
    {
        $this->wajibPeran([1, 2, 'bsi']);
        $this->cbLoader();
        $file = request()->file('berkas');
        $svc = $this->katalogSvc();
        try {
            if (!$file || !$file->isValid()) {
                throw new \InvalidArgumentException('Pilih file terlebih dahulu (atau file melebihi batas unggah server).');
            }
            $pratinjau = $svc->pratinjau($svc->bacaFile($file));
        } catch (\InvalidArgumentException $e) {
            CRUDBooster::redirect(CRUDBooster::mainpath('impor'), $e->getMessage(), 'warning');
        }
        $token = $svc->simpanPratinjau((int) CRUDBooster::myId(), $pratinjau['baris']);
        return view('admin.bsi_impor', ['bannerBsi' => $this->bannerPemilikHtml(), 'page_title' => 'Pratinjau Impor Harga', 'pratinjau' => $pratinjau, 'token' => $token]);
    }

    /** Langkah 2: terapkan pratinjau yang sudah diperiksa. */
    public function postImporTerapkan()
    {
        $this->wajibPeran([1, 2, 'bsi']);
        try {
            $r = $this->katalogSvc()->terapkanImpor((int) CRUDBooster::myId(), request('token'));
        } catch (\InvalidArgumentException $e) {
            CRUDBooster::redirect(CRUDBooster::mainpath('impor'), $e->getMessage(), 'warning');
        }
        CRUDBooster::insertLog('Impor harga acuan: ' . json_encode($r));
        $tujuan = request('lanjut_kirim') ? CRUDBooster::adminPath('bsi_pengumuman/kirim') : CRUDBooster::mainpath();
        CRUDBooster::redirect($tujuan, $this->ringkasanHasil($r), 'success');
    }

    private function ringkasanHasil(array $r)
    {
        $t = [];
        if (!empty($r['dibuat'])) { $t[] = $r['dibuat'] . ' jenis baru'; }
        if (!empty($r['harga_berubah'])) { $t[] = $r['harga_berubah'] . ' harga diubah'; }
        if (!empty($r['data_berubah'])) { $t[] = $r['data_berubah'] . ' data diperbarui'; }
        $teks = $t ? implode(', ', $t) . '.' : 'Tidak ada perubahan.';
        if (!empty($r['bsu'])) { $teks .= ' ' . $r['bsu'] . ' BSU diberi notifikasi.'; }
        return $teks;
    }

    /** JS saran otomatis di form tambah/ubah: kategori & jenis yang sudah ada + acuan harga di BSU. */
    private function skripSaran()
    {
        $js = <<<'JS'
(function(){
  var nama=document.getElementById('nama'), kat=document.getElementById('kategori'), harga=document.getElementById('harga');
  if(!nama||!kat) return;
  fetch(__URL_KATALOG__,{credentials:'same-origin',headers:{'Accept':'application/json'}}).then(function(r){return r.json();}).then(function(d){
    var dn=document.createElement('datalist'); dn.id='dl-nama'; d.item.forEach(function(i){var o=document.createElement('option'); o.value=i.nama; dn.appendChild(o);});
    var dk=document.createElement('datalist'); dk.id='dl-kat'; d.kategori.forEach(function(k){var o=document.createElement('option'); o.value=k; dk.appendChild(o);});
    document.body.appendChild(dn); document.body.appendChild(dk); nama.setAttribute('list','dl-nama'); kat.setAttribute('list','dl-kat');
    var info=document.createElement('p'); info.className='help-block'; (harga?harga.parentNode:nama.parentNode).appendChild(info);
    function cek(){
      var t=nama.value.trim().toLowerCase(); var i=d.item.filter(function(x){return x.nama.toLowerCase()===t;})[0]; info.textContent='';
      if(!i) return;
      if(!kat.value) kat.value=i.kategori||'';
      if(i.ref) info.textContent='Acuan di BSU: Rp '+i.ref.min.toLocaleString('id-ID')+' – Rp '+i.ref.maks.toLocaleString('id-ID')+' (rata-rata Rp '+i.ref.rata.toLocaleString('id-ID')+', '+i.ref.jml+' BSU).';
    }
    nama.addEventListener('input',cek); nama.addEventListener('change',cek); cek();
  });
})();
JS;
        return str_replace('__URL_KATALOG__', json_encode(CRUDBooster::mainpath('katalog')), $js);
    }
}
