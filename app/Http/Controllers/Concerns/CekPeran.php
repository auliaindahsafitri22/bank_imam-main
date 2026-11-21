<?php

namespace App\Http\Controllers\Concerns;

use CRUDBooster;
use Illuminate\Support\Facades\DB;
use App\Services\WilayahBsiService;

/** Pembatasan peran & kepemilikan data untuk controller CMS fitur baru. */
trait CekPeran
{
    /** Privilege id: 1 Super Admin, 2 Admin, 4 BSU, BSI = cms_privileges.name 'BSI' */
    protected function idBsi()
    {
        static $id = null;
        if ($id === null) {
            $id = (int) DB::table('cms_privileges')->where('name', 'BSI')->value('id');
        }
        return $id;
    }

    protected function peranSaya()
    {
        return (int) CRUDBooster::myPrivilegeId();
    }

    protected function adalahBsu()
    {
        return $this->peranSaya() === 4;
    }

    protected function adalahBsi()
    {
        return $this->idBsi() > 0 && $this->peranSaya() === $this->idBsi();
    }

    protected function adalahPusat()
    {
        return in_array($this->peranSaya(), [1, 2], true);
    }

    /** Hentikan jika peran tidak termasuk. Gunakan 'bsi' sebagai pengganti id BSI. */
    protected function wajibPeran(array $daftar)
    {
        $ok = in_array($this->peranSaya(), array_map(function ($x) {
            return $x === 'bsi' ? $this->idBsi() : (int) $x;
        }, $daftar), true);
        if (!$ok) {
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }
    }

    /** BSU hanya boleh menyentuh baris miliknya; Super Admin/Admin bebas. */
    protected function wajibMilik($table, $id, $kolom = 'admin_id')
    {
        if ($this->adalahPusat()) {
            return;
        }
        $pemilik = DB::table($table)->where('id', $id)->value($kolom);
        if ((int) $pemilik !== (int) CRUDBooster::myId()) {
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }
    }

    /**
     * Pemilik daftar harga acuan yang sedang dikelola.
     *  - Akun BSI: selalu akun sendiri (tidak bisa melihat/mengubah daftar BSI lain).
     *  - Super Admin/Admin: BSI pilihan dari ?bsi=ID (diingat di sesi); kosong = "Harga Bersama" (daftar lama).
     * @return int|null  id akun BSI, atau null untuk Harga Bersama
     */
    protected function pemilikHargaAktif()
    {
        if ($this->adalahBsi()) {
            return (int) CRUDBooster::myId();
        }
        $wilayah = app(WilayahBsiService::class);
        if (request()->has('bsi')) {
            $v = (int) request('bsi');
            session(['bsi_harga_aktif' => ($v > 0 && $wilayah->adalahAkunBsi($v)) ? $v : 0]);
        }
        $v = (int) session('bsi_harga_aktif', 0);
        return ($v > 0 && $wilayah->adalahAkunBsi($v)) ? $v : null;
    }

    /** Akun BSI hanya boleh menyentuh baris harga miliknya; Super Admin/Admin bebas. */
    protected function wajibHargaMilikBsi($bsiHargaId)
    {
        if (!$this->adalahBsi()) {
            return;
        }
        $pemilik = DB::table('bsi_harga')->where('id', (int) $bsiHargaId)->value('bsi_id');
        if ((int) $pemilik !== (int) CRUDBooster::myId()) {
            CRUDBooster::redirect(CRUDBooster::adminPath(), trans('crudbooster.denied_access'));
        }
    }

    /**
     * Kotak keterangan di halaman harga acuan: untuk BSI menunjukkan kelurahan & jumlah BSU yang tercakup,
     * untuk Super Admin/Admin berupa pemilih BSI yang sedang dikelola. Mengembalikan HTML (sudah di-escape).
     */
    protected function bannerPemilikHtml()
    {
        $w = app(WilayahBsiService::class);
        $pemilik = $this->pemilikHargaAktif();

        if ($this->adalahBsi()) {
            $kelId = $w->kelurahanBsi($pemilik);
            if (!$kelId) {
                return '<div class="alert alert-warning" style="margin-bottom:12px"><i class="fa fa-warning"></i> Akun BSI Anda belum dikaitkan ke kelurahan, '
                    . 'jadi harga acuan ini belum dipakai BSU mana pun. Minta Super Admin mengatur kelurahan Anda di menu <b>Akun BSI</b>.</div>';
            }
            $nama = DB::table('kelurahans')->where('id', $kelId)->value('nama_kelurahan');
            $jml = count($w->bsuIdsBsi($pemilik));
            return '<div class="callout callout-info" style="margin-bottom:12px"><i class="fa fa-map-marker"></i> Harga acuan Anda berlaku untuk <b>'
                . $jml . ' BSU</b> di <b>Kelurahan ' . e($nama) . '</b>. BSU di kelurahan lain memakai harga BSI kelurahannya sendiri.</div>';
        }

        $opsi = '<option value="0"' . ($pemilik === null ? ' selected' : '') . '>' . e(WilayahBsiService::NAMA_BERSAMA) . '</option>';
        foreach ($w->daftarBsi() as $b) {
            $label = $b->name . ($b->nama_kelurahan ? ' — Kel. ' . $b->nama_kelurahan : ' — (belum ada kelurahan)');
            $opsi .= '<option value="' . (int) $b->id . '"' . ($pemilik === (int) $b->id ? ' selected' : '') . '>' . e($label) . '</option>';
        }
        return '<div class="callout callout-warning" style="margin-bottom:12px"><form method="get" action="' . e(url()->current()) . '" class="form-inline">'
            . '<b><i class="fa fa-sitemap"></i> Daftar harga yang sedang dikelola:</b> '
            . '<select name="bsi" class="form-control" onchange="this.form.submit()">' . $opsi . '</select> '
            . '<a href="' . e(CRUDBooster::adminPath('pantau_bsi')) . '" class="btn btn-default btn-sm"><i class="fa fa-search"></i> Pantau BSI</a></form></div>';
    }

    /**
     * JS untuk daftar CRUDBooster: tombol "Hapus"/"Pulihkan" (tautan .../hapus/ID, .../pulihkan/ID) langsung
     * bekerja setelah satu konfirmasi, TANPA halaman perantara. Dikirim sebagai POST + token CSRF, sehingga
     * perubahan data tidak bisa dipicu dari tautan di situs lain.
     */
    protected function skripHapusLangsung($pesanHapus, $pesanPulihkan = 'Pulihkan data ini?')
    {
        $js = <<<'JS'
(function(){
  var TOKEN=__TOKEN__, PH=__PH__, PP=__PP__;
  document.addEventListener('click',function(e){
    var a=e.target.closest?e.target.closest('a[href]'):null; if(!a) return;
    var m=a.getAttribute('href').match(/^(.*\/)(hapus|pulihkan)\/(\d+)$/); if(!m) return;
    e.preventDefault(); e.stopPropagation();
    if(!confirm(m[2]==='hapus'?PH:PP)) return;
    var f=document.createElement('form'); f.method='POST'; f.action=m[1]+m[2];
    [['_token',TOKEN],['id',m[3]]].forEach(function(p){var i=document.createElement('input'); i.type='hidden'; i.name=p[0]; i.value=p[1]; f.appendChild(i);});
    document.body.appendChild(f); f.submit();
  },true);
})();
JS;
        return str_replace(['__TOKEN__', '__PH__', '__PP__'],
            [json_encode(csrf_token()), json_encode($pesanHapus), json_encode($pesanPulihkan)], $js);
    }

    protected function rupiah($n)
    {
        return 'Rp ' . number_format((int) $n, 0, ',', '.');
    }
}
