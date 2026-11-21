<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Wilayah BSI (Bank Sampah Induk).
 *
 *   - Satu akun BSI memegang SATU kelurahan; satu kelurahan hanya punya SATU BSI.
 *     Kelurahan BSI = cms_users.kelurahan_id (akun dengan peran BSI).
 *   - BSU tercakup oleh BSI bila cms_users.kelurahan_id BSU sama dengan kelurahan BSI itu.
 *   - Harga acuan sebuah BSU berasal dari BSI kelurahannya. Bila kelurahan BSU belum punya BSI,
 *     BSU memakai "Harga Bersama" (bsi_harga.bsi_id = NULL, daftar lama) agar sistem tetap berjalan.
 */
class WilayahBsiService
{
    const NAMA_BERSAMA = 'Harga Bersama (daftar lama)';

    public function idPrivBsi()
    {
        static $id = null;
        if ($id === null) {
            $id = (int) DB::table('cms_privileges')->where('name', 'BSI')->value('id');
        }
        return $id;
    }

    private function bsiQuery()
    {
        return DB::table('cms_users')->where('cms_users.id_cms_privileges', $this->idPrivBsi())->whereNull('cms_users.dihapus_at');
    }

    private function kelurahanQuery()
    {
        $q = DB::table('kelurahans');
        if (Schema::hasColumn('kelurahans', 'deleted_at')) {
            $q->whereNull('deleted_at');
        }
        return $q;
    }

    /** Batasi query ke pemilik harga: null = Harga Bersama, angka = akun BSI tertentu. */
    public static function batasiPemilik($query, $pemilik, $kolom = 'bsi_id')
    {
        if ($pemilik === null) {
            return $query->whereNull($kolom);
        }
        return $query->where($kolom, (int) $pemilik);
    }

    // ------------------------------------------------------------------ pencarian dasar

    public function adalahAkunBsi($id)
    {
        return $id && $this->idPrivBsi() > 0
            && $this->bsiQuery()->where('id', (int) $id)->exists();
    }

    public function kelurahanBsi($bsiId)
    {
        $v = $this->bsiQuery()->where('id', (int) $bsiId)->value('kelurahan_id');
        return $v ? (int) $v : null;
    }

    /** Akun BSI yang memegang sebuah kelurahan (apa pun statusnya), atau null. */
    public function bsiUntukKelurahan($kelurahanId)
    {
        if (!$kelurahanId) {
            return null;
        }
        return $this->bsiQuery()->where('kelurahan_id', (int) $kelurahanId)
            ->first(['id', 'name', 'email', 'status', 'kelurahan_id']);
    }

    /** Akun BSI yang menjadi sumber harga acuan sebuah BSU, atau null bila belum ada. */
    public function bsiUntukBsu($bsuId)
    {
        $kel = DB::table('cms_users')->where('id', (int) $bsuId)->where('id_cms_privileges', 4)->value('kelurahan_id');
        return $this->bsiUntukKelurahan($kel);
    }

    /**
     * Pemilik daftar harga yang berlaku untuk sebuah BSU:
     * id akun BSI kelurahannya, atau null (= Harga Bersama) bila kelurahannya belum punya BSI.
     */
    public function pemilikHargaBsu($bsuId)
    {
        $bsi = $this->bsiUntukBsu($bsuId);
        return $bsi ? (int) $bsi->id : null;
    }

    public function namaPemilik($pemilik)
    {
        if ($pemilik === null) {
            return self::NAMA_BERSAMA;
        }
        return (string) DB::table('cms_users')->where('id', (int) $pemilik)->value('name');
    }

    /** Id semua BSU (peran 4) di kelurahan yang dipegang sebuah BSI. */
    public function bsuIdsBsi($bsiId)
    {
        $kel = $this->kelurahanBsi($bsiId);
        if (!$kel) {
            return [];
        }
        return DB::table('cms_users')->where('id_cms_privileges', 4)->where('kelurahan_id', $kel)->pluck('id')->map(function ($x) {
            return (int) $x;
        })->all();
    }

    /** Id BSU (peran 4) yang kelurahannya belum punya BSI atau belum diisi: memakai Harga Bersama. */
    public function bsuIdsTanpaBsi()
    {
        $kelBsi = $this->bsiQuery()->whereNotNull('kelurahan_id')->pluck('kelurahan_id')->all();
        $q = DB::table('cms_users')->where('id_cms_privileges', 4);
        if ($kelBsi) {
            $q->where(function ($w) use ($kelBsi) {
                $w->whereNull('kelurahan_id')->orWhereNotIn('kelurahan_id', $kelBsi);
            });
        }
        return $q->pluck('id')->map(function ($x) {
            return (int) $x;
        })->all();
    }

    /** Id BSU yang dilayani sebuah pemilik harga: BSI tertentu, atau (null) BSU tanpa BSI. */
    public function bsuIdsPemilik($pemilik)
    {
        return $pemilik === null ? $this->bsuIdsTanpaBsi() : $this->bsuIdsBsi($pemilik);
    }

    public function bsuMilikBsi($bsiId, $bsuId)
    {
        return in_array((int) $bsuId, $this->bsuIdsBsi($bsiId), true);
    }

    /** Daftar akun BSI untuk pilihan (id, nama, kelurahan). */
    public function daftarBsi()
    {
        return $this->bsiQuery()->leftJoin('kelurahans as k', 'k.id', '=', 'cms_users.kelurahan_id')
            ->orderBy('cms_users.name')
            ->get(['cms_users.id', 'cms_users.name', 'cms_users.status', 'cms_users.kelurahan_id', 'k.nama_kelurahan']);
    }

    // ------------------------------------------------------------------ aturan 1 BSI = 1 kelurahan

    /**
     * Kelurahan wajib dipilih, wajib ada, dan belum dipegang BSI lain.
     * @param int|null $kecualiUserId akun BSI yang sedang diubah (boleh mempertahankan kelurahannya)
     */
    public function pastikanKelurahanTersedia($kelurahanId, $kecualiUserId = null)
    {
        $kelurahanId = (int) $kelurahanId;
        if ($kelurahanId < 1) {
            throw new InvalidArgumentException('Pilih kelurahan untuk akun BSI. Satu BSI hanya memegang satu kelurahan.');
        }
        $kel = $this->kelurahanQuery()->where('id', $kelurahanId)->first(['id', 'nama_kelurahan']);
        if (!$kel) {
            throw new InvalidArgumentException('Kelurahan tidak ditemukan.');
        }
        $q = $this->bsiQuery()->where('kelurahan_id', $kelurahanId);
        if ($kecualiUserId) {
            $q->where('id', '!=', (int) $kecualiUserId);
        }
        $lain = $q->first(['id', 'name']);
        if ($lain) {
            throw new InvalidArgumentException('Kelurahan ' . $kel->nama_kelurahan . ' sudah dipegang ' . $lain->name
                . '. Satu kelurahan hanya boleh punya satu BSI; pindahkan atau nonaktifkan BSI tersebut lebih dulu.');
        }
        return $kel;
    }

    // ------------------------------------------------------------------ pemantauan (Super Admin)

    /**
     * Data satu halaman pemantauan.
     * @return array [bsi => [...], bsu => [...], kelurahanTanpaBsi => [...], ringkas => [...], bersama => [...]]
     */
    public function pantau()
    {
        $kelurahan = $this->kelurahanQuery()->orderBy('nama_kelurahan')->get(['id', 'nama_kelurahan'])->keyBy('id');

        $bsiRows = $this->bsiQuery()->orderBy('name')->get(['id', 'name', 'email', 'status', 'no_whatsapp', 'kelurahan_id']);
        $bsiById = $bsiRows->keyBy('id');
        $bsiPerKel = [];
        foreach ($bsiRows as $b) {
            if ($b->kelurahan_id && !isset($bsiPerKel[$b->kelurahan_id])) {
                $bsiPerKel[$b->kelurahan_id] = $b;
            }
        }

        $bsuRows = DB::table('cms_users')->where('id_cms_privileges', 4)->orderBy('name')
            ->get(['id', 'name', 'status', 'kelurahan_id', 'kelurahan', 'kecamatan', 'rw']);

        // Sumber harga yang benar-benar dipakai tiap BSU (dari sampah yang terhubung ke harga acuan).
        $sumber = [];
        $pakai = DB::table('sampah as s')->join('bsi_harga as b', 'b.id', '=', 's.bsi_harga_id')
            ->groupBy('s.admin_id', 'b.bsi_id')
            ->select('s.admin_id', 'b.bsi_id', DB::raw('COUNT(*) as n'))->get();
        foreach ($pakai as $p) {
            $sumber[$p->admin_id][] = ['bsi_id' => $p->bsi_id === null ? null : (int) $p->bsi_id, 'n' => (int) $p->n];
        }

        $bsu = [];
        $jmlPerBsi = [];
        $ringkas = ['bsu' => count($bsuRows), 'ok' => 0, 'tanpa_kelurahan' => 0, 'kelurahan_tanpa_bsi' => 0, 'sumber_beda' => 0];
        foreach ($bsuRows as $u) {
            $bsi = $u->kelurahan_id ? ($bsiPerKel[$u->kelurahan_id] ?? null) : null;
            $kel = $u->kelurahan_id ? ($kelurahan[$u->kelurahan_id] ?? null) : null;
            $kondisi = 'ok';
            if (!$u->kelurahan_id || !$kel) {
                $kondisi = 'tanpa_kelurahan';
            } elseif (!$bsi) {
                $kondisi = 'kelurahan_tanpa_bsi';
            }

            $harapan = $bsi ? (int) $bsi->id : null;
            $daftarSumber = [];
            $beda = false;
            foreach (($sumber[$u->id] ?? []) as $s) {
                $nama = $s['bsi_id'] === null ? self::NAMA_BERSAMA
                    : (isset($bsiById[$s['bsi_id']]) ? $bsiById[$s['bsi_id']]->name : 'BSI #' . $s['bsi_id'] . ' (tidak ditemukan)');
                $daftarSumber[] = ['bsi_id' => $s['bsi_id'], 'nama' => $nama, 'n' => $s['n']];
                if ($s['bsi_id'] !== $harapan) {
                    $beda = true;
                }
            }
            if ($beda && $kondisi === 'ok') {
                $kondisi = 'sumber_beda';
            }
            $ringkas[$kondisi]++;
            if ($bsi) {
                $jmlPerBsi[$bsi->id] = ($jmlPerBsi[$bsi->id] ?? 0) + 1;
            }

            $bsu[] = (object) [
                'id' => (int) $u->id, 'name' => $u->name, 'aktif' => $u->status !== 'Inactive',
                'kelurahan_id' => $u->kelurahan_id ? (int) $u->kelurahan_id : null,
                'kelurahan' => $kel ? $kel->nama_kelurahan : null,
                'kelurahan_teks' => $u->kelurahan, 'kecamatan' => $u->kecamatan, 'rw' => $u->rw,
                'bsi_id' => $bsi ? (int) $bsi->id : null, 'bsi_nama' => $bsi ? $bsi->name : null,
                'bsi_aktif' => $bsi ? $bsi->status !== 'Inactive' : null,
                'sumber' => $daftarSumber, 'kondisi' => $kondisi,
            ];
        }

        // Ringkasan harga per pemilik.
        $hargaStat = DB::table('bsi_harga')->whereNull('dihapus_at')->groupBy('bsi_id')
            ->select('bsi_id', DB::raw('COUNT(*) as jml'), DB::raw('SUM(is_active) as aktif'), DB::raw('MAX(updated_at) as terakhir'))->get();
        $stat = [];
        foreach ($hargaStat as $h) {
            $stat[$h->bsi_id === null ? 'null' : (int) $h->bsi_id] = $h;
        }

        $bsi = [];
        foreach ($bsiRows as $b) {
            $h = $stat[(int) $b->id] ?? null;
            $bsi[] = (object) [
                'id' => (int) $b->id, 'name' => $b->name, 'email' => $b->email, 'aktif' => $b->status !== 'Inactive',
                'wa' => $b->no_whatsapp, 'kelurahan_id' => $b->kelurahan_id ? (int) $b->kelurahan_id : null,
                'kelurahan' => $b->kelurahan_id && isset($kelurahan[$b->kelurahan_id]) ? $kelurahan[$b->kelurahan_id]->nama_kelurahan : null,
                'jml_bsu' => (int) ($jmlPerBsi[$b->id] ?? 0),
                'jml_harga' => $h ? (int) $h->jml : 0, 'jml_harga_aktif' => $h ? (int) $h->aktif : 0,
                'harga_terakhir' => $h ? $h->terakhir : null,
            ];
        }

        $tanpaBsi = [];
        $jmlBsuKel = [];
        foreach ($bsu as $u) {
            if ($u->kelurahan_id) {
                $jmlBsuKel[$u->kelurahan_id] = ($jmlBsuKel[$u->kelurahan_id] ?? 0) + 1;
            }
        }
        foreach ($kelurahan as $k) {
            if (!isset($bsiPerKel[$k->id])) {
                $tanpaBsi[] = (object) ['id' => (int) $k->id, 'nama' => $k->nama_kelurahan, 'jml_bsu' => (int) ($jmlBsuKel[$k->id] ?? 0)];
            }
        }

        $b = $stat['null'] ?? null;
        $bersama = (object) [
            'jml' => $b ? (int) $b->jml : 0, 'aktif' => $b ? (int) $b->aktif : 0, 'terakhir' => $b ? $b->terakhir : null,
            'bsu_terhubung' => (int) DB::table('sampah as s')->join('bsi_harga as h', 'h.id', '=', 's.bsi_harga_id')
                ->whereNull('h.bsi_id')->distinct()->count('s.admin_id'),
        ];

        // Akun BSI yang sudah dihapus (tetap tercatat, bisa dipulihkan).
        $dihapus = DB::table('cms_users as u')->leftJoin('cms_users as p', 'p.id', '=', 'u.dihapus_oleh')
            ->leftJoin('kelurahans as k', 'k.id', '=', 'u.dihapus_kelurahan_id')
            ->where('u.id_cms_privileges', $this->idPrivBsi())->whereNotNull('u.dihapus_at')->orderByDesc('u.dihapus_at')
            ->get(['u.id', 'u.name', 'u.email', 'u.dihapus_at', 'p.name as penghapus', 'k.nama_kelurahan']);

        return ['bsi' => $bsi, 'bsu' => $bsu, 'kelurahanTanpaBsi' => $tanpaBsi, 'ringkas' => $ringkas, 'bersama' => $bersama, 'bsiDihapus' => $dihapus];
    }

    // ------------------------------------------------------------------ migrasi daftar harga

    /**
     * Menyerahkan "Harga Bersama" (daftar lama, bsi_id NULL) menjadi milik satu BSI.
     * Hanya bila BSI itu belum punya daftar sendiri, agar nama jenis tidak bentrok. Harga, riwayat, dan
     * sambungan BSU tidak berubah sama sekali — hanya pemiliknya.
     * @return int jumlah jenis yang diserahkan
     */
    public function serahkanHargaBersama($bsiId)
    {
        $bsiId = (int) $bsiId;
        if (!$this->adalahAkunBsi($bsiId)) {
            throw new InvalidArgumentException('Akun BSI tidak ditemukan.');
        }
        if (!$this->kelurahanBsi($bsiId)) {
            throw new InvalidArgumentException('BSI ini belum punya kelurahan. Atur kelurahannya di menu Akun BSI.');
        }
        return DB::transaction(function () use ($bsiId) {
            if (DB::table('bsi_harga')->where('bsi_id', $bsiId)->whereNull('dihapus_at')->lockForUpdate()->exists()) {
                throw new InvalidArgumentException('BSI ini sudah punya daftar harga sendiri. Gunakan "Salin daftar" agar tidak bentrok.');
            }
            return (int) DB::table('bsi_harga')->whereNull('bsi_id')->whereNull('dihapus_at')->update(['bsi_id' => $bsiId, 'updated_at' => now()]);
        });
    }

    /**
     * Menyalin daftar harga acuan (jenis, kategori, harga, poin) dari satu pemilik ke BSI lain sebagai
     * titik awal. Jenis yang namanya sudah ada di BSI tujuan dilewati. BSU tidak dipindah otomatis.
     * @param int|null $dariPemilik null = Harga Bersama
     * @return array [disalin, dilewati]
     */
    public function salinHarga($dariPemilik, $keBsiId, $oleh = null)
    {
        $keBsiId = (int) $keBsiId;
        if (!$this->adalahAkunBsi($keBsiId)) {
            throw new InvalidArgumentException('BSI tujuan tidak ditemukan.');
        }
        if ($dariPemilik !== null) {
            $dariPemilik = (int) $dariPemilik;
            if (!$this->adalahAkunBsi($dariPemilik)) {
                throw new InvalidArgumentException('Sumber daftar harga tidak ditemukan.');
            }
            if ($dariPemilik === $keBsiId) {
                throw new InvalidArgumentException('Sumber dan tujuan tidak boleh sama.');
            }
        }

        $harga = app(HargaService::class);
        return DB::transaction(function () use ($dariPemilik, $keBsiId, $oleh, $harga) {
            $sudah = [];
            foreach (DB::table('bsi_harga')->where('bsi_id', $keBsiId)->whereNull('dihapus_at')->pluck('nama') as $n) {
                $sudah[KatalogHargaService::kunci($n)] = true;
            }
            $hasil = ['disalin' => 0, 'dilewati' => 0];
            $sumber = self::batasiPemilik(DB::table('bsi_harga'), $dariPemilik)->whereNull('dihapus_at')->orderBy('id')->get();
            foreach ($sumber as $r) {
                $k = KatalogHargaService::kunci($r->nama);
                if (isset($sudah[$k])) {
                    $hasil['dilewati']++;
                    continue;
                }
                $id = DB::table('bsi_harga')->insertGetId([
                    'bsi_id' => $keBsiId, 'nama' => $r->nama, 'kategori' => $r->kategori, 'deskripsi' => $r->deskripsi,
                    'image' => $r->image, 'satuan' => $r->satuan, 'harga' => $r->harga, 'poin_per_kg' => $r->poin_per_kg,
                    'is_active' => $r->is_active, 'updated_by' => $oleh, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $harga->catatHargaAwalBsi($id, (int) $r->harga, $oleh);
                $sudah[$k] = true;
                $hasil['disalin']++;
            }
            return $hasil;
        });
    }
}
