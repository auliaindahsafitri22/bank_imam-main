<?php

namespace App\Services;

use App\Exports\HargaImporReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Memudahkan BSI mengisi harga acuan: katalog gabungan dari kategori & jenis sampah yang SUDAH ada di BSU,
 * isi harga cepat (tabel), serta impor file Excel/CSV dengan pratinjau sebelum diterapkan.
 */
class KatalogHargaService
{
    const MAKS_BARIS = 1000;
    const MAKS_HARGA = 100000000;

    private $harga;

    /** Pemilik daftar harga yang dikelola: id akun BSI, atau null = Harga Bersama (daftar lama). */
    private $pemilik = null;

    public function __construct(HargaService $harga)
    {
        $this->harga = $harga;
    }

    /** Batasi seluruh operasi service ini ke daftar harga satu BSI (atau Harga Bersama bila null). */
    public function untukPemilik($pemilik)
    {
        $this->pemilik = $pemilik === null ? null : (int) $pemilik;
        return $this;
    }

    /** Query baris harga acuan milik pemilik aktif. */
    private function masterQuery()
    {
        return WilayahBsiService::batasiPemilik(DB::table('bsi_harga'), $this->pemilik)->whereNull('dihapus_at');
    }

    public static function kunci($nama)
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $nama)));
    }

    private static function rapikan($t)
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $t));
    }

    private static function terbanyak(array $hitung)
    {
        arsort($hitung);
        reset($hitung);
        return (string) key($hitung);
    }

    private static function awalBesar($t)
    {
        return $t === '' ? $t : mb_strtoupper(mb_substr($t, 0, 1)) . mb_substr($t, 1);
    }

    /**
     * Katalog gabungan: jenis sampah master BSI + jenis yang sudah dipakai BSU (nama sama dianggap satu).
     * @return array daftar item [kunci, master_id, nama, kategori, harga, poin, deskripsi, aktif, ada, ref]
     */
    public function katalog()
    {
        // Acuan "di BSU" hanya dari BSU yang dilayani pemilik ini (BSU di kelurahan BSI itu).
        $lingkup = app(WilayahBsiService::class)->bsuIdsPemilik($this->pemilik);
        $data = DB::table('sampah as s')->leftJoin('sampah_categories as c', 'c.id', '=', 's.category_id')
            ->whereIn('s.admin_id', $lingkup ?: [0])
            ->get(['s.name', 'c.name as kategori', 's.price_per_kg', 's.poin_per_kg', 's.admin_id']);

        $grup = [];
        foreach ($data as $r) {
            $k = self::kunci($r->name);
            if ($k === '') {
                continue;
            }
            if (!isset($grup[$k])) {
                $grup[$k] = ['nama' => [], 'kat' => [], 'katAsli' => [], 'harga' => [], 'poin' => [], 'bsu' => []];
            }
            $nama = self::rapikan($r->name);
            $grup[$k]['nama'][$nama] = ($grup[$k]['nama'][$nama] ?? 0) + 1;
            $kat = self::rapikan($r->kategori);
            if ($kat !== '') {
                $kk = mb_strtolower($kat);
                $grup[$k]['kat'][$kk] = ($grup[$k]['kat'][$kk] ?? 0) + 1;
                $grup[$k]['katAsli'][$kk][$kat] = ($grup[$k]['katAsli'][$kk][$kat] ?? 0) + 1;
            }
            $grup[$k]['harga'][] = (int) $r->price_per_kg;
            $grup[$k]['poin'][] = (int) $r->poin_per_kg;
            $grup[$k]['bsu'][$r->admin_id] = true;
        }

        $master = [];
        foreach ($this->masterQuery()->get() as $m) {
            $master[self::kunci($m->nama)] = $m;
        }

        $item = [];
        foreach ($grup as $k => $g) {
            $kat = '';
            if ($g['kat']) {
                $kk = self::terbanyak($g['kat']);
                $kat = self::awalBesar(self::terbanyak($g['katAsli'][$kk]));
            }
            $item[$k] = [
                'nama' => self::terbanyak($g['nama']), 'kategori' => $kat,
                'ref' => [
                    'jml' => count($g['bsu']), 'min' => min($g['harga']), 'maks' => max($g['harga']),
                    'rata' => (int) round(array_sum($g['harga']) / count($g['harga'])),
                    'poin' => (int) round(array_sum($g['poin']) / count($g['poin'])),
                ],
            ];
        }

        $hasil = [];
        foreach (array_unique(array_merge(array_keys($item), array_keys($master))) as $k) {
            $m = $master[$k] ?? null;
            $c = $item[$k] ?? null;
            $hasil[] = [
                'kunci' => $k,
                'master_id' => $m ? (int) $m->id : null,
                'nama' => $m ? $m->nama : $c['nama'],
                'kategori' => $m && $m->kategori ? $m->kategori : ($c ? $c['kategori'] : ''),
                'harga' => $m ? (int) $m->harga : null,
                'poin' => $m ? (int) $m->poin_per_kg : ($c ? $c['ref']['poin'] : 0),
                'deskripsi' => $m ? (string) $m->deskripsi : '',
                'aktif' => $m ? (bool) $m->is_active : true,
                'ada' => (bool) $m,
                'ref' => $c ? $c['ref'] : null,
            ];
        }
        usort($hasil, function ($a, $b) {
            $ka = $a['kategori'] === '' ? "\xEF\xBF\xBF" : mb_strtolower($a['kategori']);
            $kb = $b['kategori'] === '' ? "\xEF\xBF\xBF" : mb_strtolower($b['kategori']);
            return strcmp($ka, $kb) ?: strcmp(mb_strtolower($a['nama']), mb_strtolower($b['nama']));
        });
        return $hasil;
    }

    public function daftarKategori(array $katalog = null)
    {
        $katalog = $katalog ?: $this->katalog();
        $k = [];
        foreach ($katalog as $i) {
            if ($i['kategori'] !== '') {
                $k[mb_strtolower($i['kategori'])] = $i['kategori'];
            }
        }
        ksort($k);
        return array_values($k);
    }

    // ------------------------------------------------------------------ isi harga cepat

    public static function parseRupiah($v)
    {
        if ($v === null || (is_string($v) && trim($v) === '')) {
            return null;
        }
        if (is_int($v) || is_float($v)) {
            $n = (float) $v;
        } else {
            $t = mb_strtolower(trim((string) $v));
            $t = preg_replace('/^rp\.?\s*/u', '', $t);
            $t = str_replace([' ', "\xC2\xA0"], '', $t);
            if ($t === '') {
                return null;
            }
            if (preg_match('/^\d+$/', $t)) {
                $n = (float) $t;
            } elseif (preg_match('/^\d{1,3}(\.\d{3})+(,\d+)?$/', $t)) {
                $n = (float) str_replace(',', '.', str_replace('.', '', $t));
            } elseif (preg_match('/^\d{1,3}(,\d{3})+(\.\d+)?$/', $t)) {
                $n = (float) str_replace(',', '', $t);
            } elseif (preg_match('/^\d+,\d{1,2}$/', $t)) {
                $n = (float) str_replace(',', '.', $t);
            } elseif (preg_match('/^\d+\.\d{1,2}$/', $t)) {
                $n = (float) $t;
            } else {
                throw new InvalidArgumentException('Harga "' . mb_substr((string) $v, 0, 30) . '" tidak valid. Contoh: 3500 atau 3.500');
            }
        }
        if ($n < 0 || $n > self::MAKS_HARGA) {
            throw new InvalidArgumentException('Harga harus antara 0 dan ' . number_format(self::MAKS_HARGA, 0, ',', '.') . '.');
        }
        return (int) round($n);
    }

    private function cekTeks($nama, $kategori, $deskripsi, $label)
    {
        if (mb_strlen($nama) < 2 || mb_strlen($nama) > 120) {
            throw new InvalidArgumentException("$label: nama jenis sampah harus 2–120 karakter.");
        }
        if (mb_strlen($kategori) > 80) {
            throw new InvalidArgumentException("$label: kategori maksimal 80 karakter.");
        }
        if (mb_strlen($deskripsi) > 1000) {
            throw new InvalidArgumentException("$label: penjelasan maksimal 1000 karakter.");
        }
    }

    private static function parsePoin($v)
    {
        if ($v === null || (is_string($v) && trim($v) === '')) {
            return null;
        }
        $n = self::parseRupiah($v);
        if ($n > 100000) {
            throw new InvalidArgumentException('Poin per kg maksimal 100.000.');
        }
        return $n;
    }

    /**
     * Simpan dari tabel isi cepat.
     * @param array $rows [['master_id','nama','kategori','harga','poin','aktif'], ...]
     * @return array [dibuat, harga_berubah, data_berubah, bsu]
     */
    public function simpanCepat(array $rows, $userId)
    {
        if (count($rows) > self::MAKS_BARIS) {
            throw new InvalidArgumentException('Maksimal ' . self::MAKS_BARIS . ' baris.');
        }
        $master = $this->masterQuery()->get()->keyBy('id');
        $kunciMaster = [];
        foreach ($master as $m) {
            $kunciMaster[self::kunci($m->nama)] = $m->id;
        }

        // 1) validasi semuanya dulu — gagal satu = tidak ada yang tersimpan
        $rencana = [];
        $dipakai = [];
        foreach ($rows as $i => $r) {
            $label = 'Baris ' . ($i + 1);
            $mid = (int) ($r['master_id'] ?? 0);
            $nama = self::rapikan($r['nama'] ?? '');
            $kat = self::rapikan($r['kategori'] ?? '');
            $harga = self::parseRupiahBaris($r['harga'] ?? '', $label);
            $poin = self::parsePoinBaris($r['poin'] ?? '', $label);
            $aktif = !empty($r['aktif']);

            if ($mid && !$master->has($mid)) {
                throw new InvalidArgumentException("$label: jenis sampah tidak ditemukan. Muat ulang halaman.");
            }
            if (!$mid) {
                if ($harga === null) {
                    continue; // jenis dari katalog BSU yang belum diisi hargai: dilewati
                }
                if (isset($kunciMaster[self::kunci($nama)])) {
                    throw new InvalidArgumentException("$label: \"$nama\" sudah ada di daftar harga acuan. Muat ulang halaman.");
                }
            }
            $this->cekTeks($nama ?: ($mid ? $master[$mid]->nama : ''), $kat, '', $label);
            $k = $mid ? 'm' . $mid : 'n' . self::kunci($nama);
            if (isset($dipakai[$k])) {
                throw new InvalidArgumentException("$label: jenis \"$nama\" muncul lebih dari sekali.");
            }
            $dipakai[$k] = true;
            $rencana[] = compact('mid', 'nama', 'kat', 'harga', 'poin', 'aktif');
        }

        // 2) terapkan
        $hasil = ['dibuat' => 0, 'harga_berubah' => 0, 'data_berubah' => 0, 'bsu' => 0];
        DB::transaction(function () use ($rencana, $master, $userId, &$hasil) {
            $ubahHarga = [];
            foreach ($rencana as $p) {
                if ($p['mid']) {
                    $m = $master[$p['mid']];
                    $data = [];
                    if ($p['kat'] !== '' && $p['kat'] !== (string) $m->kategori) {
                        $data['kategori'] = $p['kat'];
                    }
                    if ($p['poin'] !== null && $p['poin'] !== (int) $m->poin_per_kg) {
                        $data['poin_per_kg'] = $p['poin'];
                    }
                    if ((int) $p['aktif'] !== (int) $m->is_active) {
                        $data['is_active'] = (int) $p['aktif'];
                    }
                    if ($data) {
                        $data['updated_by'] = $userId;
                        $data['updated_at'] = now();
                        DB::table('bsi_harga')->where('id', $m->id)->update($data);
                        $hasil['data_berubah']++;
                    }
                    if ($p['harga'] !== null && $p['harga'] !== (int) $m->harga) {
                        $ubahHarga[$m->id] = $p['harga'];
                    }
                } else {
                    $id = DB::table('bsi_harga')->insertGetId([
                        'bsi_id' => $this->pemilik,
                        'nama' => $p['nama'], 'kategori' => $p['kat'] !== '' ? $p['kat'] : null, 'satuan' => 'kg',
                        'harga' => $p['harga'], 'poin_per_kg' => (int) $p['poin'], 'is_active' => $p['aktif'] ? 1 : 0,
                        'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $this->harga->catatHargaAwalBsi($id, $p['harga'], $userId);
                    $hasil['dibuat']++;
                }
            }
            if ($ubahHarga) {
                $r = $this->harga->ubahHargaBanyak($ubahHarga, $userId);
                $hasil['harga_berubah'] = $r['item'];
                $hasil['bsu'] = $r['bsu'];
            }
        });
        return $hasil;
    }

    private static function parseRupiahBaris($v, $label)
    {
        try {
            return self::parseRupiah($v);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("$label: " . $e->getMessage());
        }
    }

    private static function parsePoinBaris($v, $label)
    {
        try {
            return self::parsePoin($v);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException("$label: " . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------ impor file

    /** Template Excel: seluruh jenis yang sudah ada, harga master terisi, yang belum punya harga dikosongkan. */
    public function barisTemplate()
    {
        $rows = [];
        foreach ($this->katalog() as $i) {
            $rows[] = [
                $i['kategori'], $i['nama'], $i['harga'] !== null ? $i['harga'] : '', $i['poin'], $i['deskripsi'],
                $i['ada'] ? ($i['aktif'] ? 'Aktif' : 'Nonaktif') : '',
                $i['ref'] ? $i['ref']['rata'] : '',
            ];
        }
        return $rows;
    }

    public static function judulTemplate()
    {
        return ['Kategori', 'Jenis Sampah', 'Harga BSI per kg (Rp)', 'Poin per kg', 'Penjelasan', 'Status (Aktif/Nonaktif)', 'Acuan rata-rata di BSU (info, diabaikan)'];
    }

    /** @return array baris mentah [no_baris_di_file => [sel...]] dari file xlsx/xls/csv */
    public function bacaFile(UploadedFile $file)
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            throw new InvalidArgumentException('Format file harus .xlsx, .xls, atau .csv.');
        }
        if ($file->getSize() > 2 * 1024 * 1024) {
            throw new InvalidArgumentException('Ukuran file maksimal 2 MB.');
        }
        if ($ext === 'csv') {
            $raw = $this->bacaCsv($file->getRealPath());
        } else {
            try {
                $sheets = Excel::toArray(new HargaImporReader(), $file);
            } catch (\Throwable $e) {
                throw new InvalidArgumentException('File Excel tidak dapat dibaca. Pastikan bukan file rusak atau berpassword.');
            }
            $raw = isset($sheets[0]) ? $sheets[0] : [];
        }
        if (count($raw) > self::MAKS_BARIS + 10) {
            throw new InvalidArgumentException('Maksimal ' . self::MAKS_BARIS . ' baris data per file.');
        }
        return $raw;
    }

    private function bacaCsv($path)
    {
        $isi = file_get_contents($path);
        if (substr($isi, 0, 3) === "\xEF\xBB\xBF") {
            $isi = substr($isi, 3);
        }
        if (!mb_check_encoding($isi, 'UTF-8')) {
            $isi = mb_convert_encoding($isi, 'UTF-8', 'Windows-1252');
        }
        $baris = preg_split('/\r\n|\n|\r/', $isi);
        $contoh = '';
        foreach ($baris as $b) {
            if (trim($b) !== '') { $contoh = $b; break; }
        }
        $pemisah = ',';
        $maks = -1;
        foreach ([';', ',', "\t"] as $d) {
            $n = substr_count($contoh, $d);
            if ($n > $maks) { $maks = $n; $pemisah = $d; }
        }
        $hasil = [];
        foreach ($baris as $b) {
            if (trim($b) === '') { $hasil[] = []; continue; }
            $hasil[] = str_getcsv($b, $pemisah, '"');
        }
        return $hasil;
    }

    private static function sel($row, $idx)
    {
        return ($idx !== null && array_key_exists($idx, $row)) ? $row[$idx] : null;
    }

    /**
     * @return array [baris => [...], ringkas => [...]] ; status: baru | ubah | sama | kosong | error
     */
    public function pratinjau(array $raw)
    {
        // cari baris judul
        $peta = null;
        $awal = 0;
        for ($i = 0; $i < min(10, count($raw)); $i++) {
            $c = ['kategori' => null, 'nama' => null, 'harga' => null, 'poin' => null, 'deskripsi' => null, 'status' => null];
            foreach ((array) $raw[$i] as $j => $teks) {
                $t = mb_strtolower(self::rapikan((string) $teks));
                if ($t === '') { continue; }
                if ($c['harga'] === null && preg_match('/^harga/u', $t)) { $c['harga'] = $j; }
                elseif ($c['kategori'] === null && preg_match('/^kategori/u', $t)) { $c['kategori'] = $j; }
                elseif ($c['nama'] === null && preg_match('/^(jenis|nama)/u', $t)) { $c['nama'] = $j; }
                elseif ($c['poin'] === null && preg_match('/^poin/u', $t)) { $c['poin'] = $j; }
                elseif ($c['deskripsi'] === null && preg_match('/^(penjelasan|deskripsi|keterangan)/u', $t)) { $c['deskripsi'] = $j; }
                elseif ($c['status'] === null && preg_match('/^(status|aktif)/u', $t)) { $c['status'] = $j; }
            }
            if ($c['harga'] !== null && $c['nama'] !== null) {
                $peta = $c;
                $awal = $i + 1;
                break;
            }
        }
        if (!$peta) {
            throw new InvalidArgumentException('Baris judul kolom tidak ditemukan. File harus punya kolom "Jenis Sampah" dan "Harga". Gunakan template dari tombol "Unduh Template".');
        }

        $master = [];
        foreach ($this->masterQuery()->get() as $m) {
            $master[self::kunci($m->nama)] = $m;
        }

        $baris = [];
        $terlihat = [];
        $ringkas = ['baru' => 0, 'ubah' => 0, 'sama' => 0, 'kosong' => 0, 'error' => 0];
        for ($i = $awal; $i < count($raw); $i++) {
            $r = (array) $raw[$i];
            $nama = self::rapikan((string) self::sel($r, $peta['nama']));
            $hargaMentah = self::sel($r, $peta['harga']);
            if ($nama === '' && ($hargaMentah === null || trim((string) $hargaMentah) === '')) {
                continue; // baris kosong
            }
            $b = [
                'no' => $i + 1, 'nama' => $nama, 'kategori' => self::rapikan((string) self::sel($r, $peta['kategori'])),
                'deskripsi' => trim((string) self::sel($r, $peta['deskripsi'])), 'harga' => null, 'poin' => null, 'aktif' => null,
                'master_id' => null, 'harga_lama' => null, 'status' => 'sama', 'pesan' => '',
            ];
            try {
                $this->cekTeks($nama, $b['kategori'], $b['deskripsi'], 'Baris ' . $b['no']);
                $k = self::kunci($nama);
                if (isset($terlihat[$k])) {
                    throw new InvalidArgumentException('Duplikat dengan baris ' . $terlihat[$k] . '.');
                }
                $terlihat[$k] = $b['no'];
                $b['harga'] = self::parseRupiah($hargaMentah);
                $b['poin'] = self::parsePoin(self::sel($r, $peta['poin']));
                $st = mb_strtolower(trim((string) self::sel($r, $peta['status'])));
                if ($st !== '') {
                    if (in_array($st, ['aktif', '1', 'ya', 'y', 'true'], true)) { $b['aktif'] = true; }
                    elseif (in_array($st, ['nonaktif', 'non aktif', 'tidak aktif', '0', 'tidak', 'n', 'false'], true)) { $b['aktif'] = false; }
                    else { throw new InvalidArgumentException('Status "' . mb_substr($st, 0, 20) . '" tidak dikenal (isi Aktif atau Nonaktif).'); }
                }

                $m = $master[$k] ?? null;
                if ($m) {
                    $b['master_id'] = (int) $m->id;
                    $b['harga_lama'] = (int) $m->harga;
                    $beda = ($b['harga'] !== null && $b['harga'] !== (int) $m->harga)
                        || ($b['poin'] !== null && $b['poin'] !== (int) $m->poin_per_kg)
                        || ($b['kategori'] !== '' && $b['kategori'] !== (string) $m->kategori)
                        || ($b['deskripsi'] !== '' && $b['deskripsi'] !== (string) $m->deskripsi)
                        || ($b['aktif'] !== null && (int) $b['aktif'] !== (int) $m->is_active);
                    $b['status'] = $beda ? 'ubah' : 'sama';
                } else {
                    $b['status'] = $b['harga'] === null ? 'kosong' : 'baru';
                    if ($b['status'] === 'kosong') { $b['pesan'] = 'Harga belum diisi — dilewati.'; }
                }
            } catch (InvalidArgumentException $e) {
                $b['status'] = 'error';
                $b['pesan'] = $e->getMessage();
            }
            $ringkas[$b['status']]++;
            $baris[] = $b;
        }
        return ['baris' => $baris, 'ringkas' => $ringkas];
    }

    /** Simpan pratinjau sementara; mengembalikan token sekali pakai. */
    public function simpanPratinjau($userId, array $baris)
    {
        $token = Str::random(32);
        Cache::put('harga_impor:' . $userId . ':' . ($this->pemilik === null ? 'b' : $this->pemilik) . ':' . $token, $baris, now()->addMinutes(30));
        return $token;
    }

    public function terapkanImpor($userId, $token)
    {
        $baris = Cache::pull('harga_impor:' . $userId . ':' . ($this->pemilik === null ? 'b' : $this->pemilik) . ':' . preg_replace('/[^A-Za-z0-9]/', '', (string) $token));
        if (!is_array($baris)) {
            throw new InvalidArgumentException('Pratinjau sudah kedaluwarsa atau sudah diterapkan. Unggah ulang file.');
        }
        $hasil = ['dibuat' => 0, 'harga_berubah' => 0, 'data_berubah' => 0, 'bsu' => 0, 'dilewati' => 0];
        DB::transaction(function () use ($baris, $userId, &$hasil) {
            $ubahHarga = [];
            foreach ($baris as $b) {
                if ($b['status'] === 'baru') {
                    if ($this->masterQuery()->whereRaw('LOWER(TRIM(nama)) = ?', [self::kunci($b['nama'])])->exists()) {
                        $hasil['dilewati']++;   // sudah dibuat orang lain sejak pratinjau
                        continue;
                    }
                    $id = DB::table('bsi_harga')->insertGetId([
                        'bsi_id' => $this->pemilik,
                        'nama' => $b['nama'], 'kategori' => $b['kategori'] !== '' ? $b['kategori'] : null,
                        'deskripsi' => $b['deskripsi'] !== '' ? $b['deskripsi'] : null, 'satuan' => 'kg',
                        'harga' => (int) $b['harga'], 'poin_per_kg' => (int) $b['poin'],
                        'is_active' => $b['aktif'] === null ? 1 : (int) $b['aktif'],
                        'updated_by' => $userId, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                    $this->harga->catatHargaAwalBsi($id, (int) $b['harga'], $userId);
                    $hasil['dibuat']++;
                } elseif ($b['status'] === 'ubah') {
                    $m = $this->masterQuery()->where('id', $b['master_id'])->first();
                    if (!$m) { $hasil['dilewati']++; continue; }
                    $data = [];
                    if ($b['kategori'] !== '' && $b['kategori'] !== (string) $m->kategori) { $data['kategori'] = $b['kategori']; }
                    if ($b['deskripsi'] !== '' && $b['deskripsi'] !== (string) $m->deskripsi) { $data['deskripsi'] = $b['deskripsi']; }
                    if ($b['poin'] !== null && $b['poin'] !== (int) $m->poin_per_kg) { $data['poin_per_kg'] = $b['poin']; }
                    if ($b['aktif'] !== null && (int) $b['aktif'] !== (int) $m->is_active) { $data['is_active'] = (int) $b['aktif']; }
                    if ($data) {
                        $data['updated_by'] = $userId;
                        $data['updated_at'] = now();
                        DB::table('bsi_harga')->where('id', $m->id)->update($data);
                        $hasil['data_berubah']++;
                    }
                    if ($b['harga'] !== null && $b['harga'] !== (int) $m->harga) {
                        $ubahHarga[$m->id] = $b['harga'];
                    }
                }
            }
            if ($ubahHarga) {
                $r = $this->harga->ubahHargaBanyak($ubahHarga, $userId);
                $hasil['harga_berubah'] = $r['item'];
                $hasil['bsu'] = $r['bsu'];
            }
        });
        return $hasil;
    }
}
