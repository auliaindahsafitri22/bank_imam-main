<?php

namespace App\Services;

use App\Exports\DaftarHargaExport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Maatwebsite\Excel\Facades\Excel;

/** Pengumuman dari BSI ke satu atau lebih BSU, opsional dengan daftar harga acuan terbaru. */
class PengumumanBsiService
{
    const MAKS_TUJUAN = 500;
    const MAKS_FILE = 5;                 // lampiran unggahan per pengumuman
    const MAKS_UKURAN = 5242880;         // 5 MB per file
    const EKSTENSI = ['pdf', 'xlsx', 'xls', 'csv', 'docx', 'doc', 'jpg', 'jpeg', 'png'];

    /**
     * BSU aktif yang dapat dipilih sebagai tujuan, urut A–Z.
     * @param int|null $batasBsi bila diisi (akun BSI): hanya BSU di kelurahan yang dipegang BSI itu
     */
    public function daftarBsu($batasBsi = null)
    {
        $q = DB::table('cms_users')->where('id_cms_privileges', 4)
            ->where(function ($q) { $q->whereNull('status')->orWhere('status', '!=', 'Inactive'); });
        if ($batasBsi !== null) {
            $q->whereIn('id', app(WilayahBsiService::class)->bsuIdsBsi($batasBsi) ?: [0]);
        }
        return $q->orderBy('name')->get(['id', 'name', 'kecamatan', 'kelurahan']);
    }

    /**
     * Snapshot harga acuan aktif saat ini.
     * @param int|null|false $pemilik false = semua; null = Harga Bersama; angka = satu BSI
     */
    public function snapshotHarga($pemilik = false)
    {
        $q = DB::table('bsi_harga')->where('is_active', 1)->whereNull('dihapus_at');
        if ($pemilik !== false) {
            WilayahBsiService::batasiPemilik($q, $pemilik);
        }
        return $q->orderBy('kategori')->orderBy('nama')
            ->get(['nama', 'kategori', 'harga'])
            ->map(function ($r) { return ['nama' => $r->nama, 'kategori' => $r->kategori, 'harga' => (int) $r->harga]; })->all();
    }

    /** Memastikan isi file sesuai ekstensinya (bukan sekadar nama), lalu mengembalikan MIME hasil deteksi. */
    private function periksaFile($file)
    {
        if (!($file instanceof UploadedFile) || !$file->isValid()) {
            throw new InvalidArgumentException('Salah satu file gagal diunggah (mungkin melebihi batas ukuran server).');
        }
        $nama = $this->namaAman($file->getClientOriginalName());
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, self::EKSTENSI, true)) {
            throw new InvalidArgumentException('File "' . $nama . '" tidak diizinkan. Format yang boleh: ' . implode(', ', self::EKSTENSI) . '.');
        }
        if ($file->getSize() > self::MAKS_UKURAN) {
            throw new InvalidArgumentException('File "' . $nama . '" melebihi 5 MB.');
        }
        $kepala = (string) file_get_contents($file->getRealPath(), false, null, 0, 4096);
        $ok = false;
        switch ($ext) {
            case 'pdf': $ok = strncmp($kepala, '%PDF', 4) === 0; break;
            case 'xlsx': case 'docx': $ok = strncmp($kepala, "PK\x03\x04", 4) === 0; break;
            case 'xls': case 'doc': $ok = strncmp($kepala, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1", 8) === 0; break;
            case 'jpg': case 'jpeg': $ok = strncmp($kepala, "\xFF\xD8\xFF", 3) === 0; break;
            case 'png': $ok = strncmp($kepala, "\x89PNG\r\n\x1A\n", 8) === 0; break;
            case 'csv': $ok = strpos($kepala, "\0") === false; break;
        }
        if (!$ok) {
            throw new InvalidArgumentException('Isi file "' . $nama . '" tidak sesuai dengan ekstensinya (.' . $ext . ').');
        }
        return $file->getMimeType() ?: 'application/octet-stream';
    }

    /** Nama tampilan yang aman (tanpa path/karakter kontrol), ekstensi dipertahankan. */
    public function namaAman($nama)
    {
        $nama = basename(str_replace('\\', '/', (string) $nama));
        $nama = preg_replace('/[\x00-\x1F\x7F\\\\\/:*?"<>|]+/u', '_', $nama);
        $nama = trim(preg_replace('/\s+/u', ' ', $nama), " ._");
        if ($nama === '') {
            $nama = 'lampiran';
        }
        if (mb_strlen($nama) > 120) {
            $ext = pathinfo($nama, PATHINFO_EXTENSION);
            $nama = mb_substr(pathinfo($nama, PATHINFO_FILENAME), 0, 110) . ($ext !== '' ? '.' . $ext : '');
        }
        return $nama;
    }

    /**
     * @param array $files UploadedFile[] lampiran unggahan
     * @param bool $lampirkanExcel membuat file Excel daftar harga acuan saat ini sebagai lampiran
     * @return array [id, jumlah, lampiran]
     */
    public function kirim($pengirimId, $judul, $isi, array $bsuIds, $sertakanHarga = false, array $files = [], $lampirkanExcel = false, $batasBsi = null, $pemilikHarga = false)
    {
        $judul = trim((string) $judul);
        $isi = trim((string) $isi);
        if (mb_strlen($judul) < 3 || mb_strlen($judul) > 150) {
            throw new InvalidArgumentException('Judul wajib diisi (3–150 karakter).');
        }
        if (mb_strlen($isi) < 5 || mb_strlen($isi) > 5000) {
            throw new InvalidArgumentException('Isi pengumuman wajib diisi (5–5000 karakter).');
        }
        $bsuIds = array_values(array_unique(array_map('intval', $bsuIds)));
        if (count($bsuIds) < 1) {
            throw new InvalidArgumentException('Pilih minimal satu BSU tujuan.');
        }
        if (count($bsuIds) > self::MAKS_TUJUAN) {
            throw new InvalidArgumentException('Maksimal ' . self::MAKS_TUJUAN . ' BSU per pengumuman.');
        }

        // Hanya akun BSU aktif yang diterima; id lain (Admin/BSI/tidak ada) ditolak, bukan diabaikan diam-diam.
        $valid = DB::table('cms_users')->whereIn('id', $bsuIds)->where('id_cms_privileges', 4)
            ->where(function ($q) { $q->whereNull('status')->orWhere('status', '!=', 'Inactive'); })->pluck('id')->all();
        if (count($valid) !== count($bsuIds)) {
            throw new InvalidArgumentException('Ada tujuan yang bukan BSU aktif. Muat ulang halaman lalu pilih kembali.');
        }
        // Akun BSI hanya boleh menyapa BSU di kelurahan yang dipegangnya.
        if ($batasBsi !== null) {
            $dalam = app(WilayahBsiService::class)->bsuIdsBsi($batasBsi);
            if (array_diff($valid, $dalam)) {
                throw new InvalidArgumentException('Ada BSU tujuan di luar kelurahan BSI Anda. Muat ulang halaman lalu pilih kembali.');
            }
        }

        $files = array_values(array_filter($files));
        if (count($files) > self::MAKS_FILE) {
            throw new InvalidArgumentException('Maksimal ' . self::MAKS_FILE . ' file lampiran per pengumuman.');
        }
        $mimeFile = [];
        foreach ($files as $i => $f) {
            $mimeFile[$i] = $this->periksaFile($f);
        }
        $snapExcel = null;
        if ($lampirkanExcel) {
            $snapExcel = $this->snapshotHarga($pemilikHarga);
            if (!count($snapExcel)) {
                throw new InvalidArgumentException('Belum ada harga acuan aktif untuk dibuat menjadi file Excel.');
            }
        }

        $lampiran = null;
        if ($sertakanHarga) {
            $snap = $this->snapshotHarga($pemilikHarga);
            if (!count($snap)) {
                throw new InvalidArgumentException('Belum ada harga acuan aktif untuk dilampirkan.');
            }
            $lampiran = json_encode($snap, JSON_UNESCAPED_UNICODE);
        }

        $id = null;
        $jmlLampiran = count($files) + ($snapExcel ? 1 : 0);
        try {
            DB::transaction(function () use ($pengirimId, $judul, $isi, $lampiran, $valid, $files, $mimeFile, $snapExcel, $jmlLampiran, &$id) {
                $id = DB::table('bsi_pengumuman')->insertGetId([
                    'judul' => $judul, 'isi' => $isi, 'lampiran_harga' => $lampiran,
                    'created_by' => $pengirimId, 'created_at' => now(), 'updated_at' => now(),
                ]);
                $dir = 'bsi_lampiran/' . $id;
                foreach ($files as $i => $f) {
                    $ext = strtolower($f->getClientOriginalExtension());
                    $path = $dir . '/' . Str::random(32) . '.' . $ext;
                    Storage::disk('local')->putFileAs($dir, $f, basename($path));
                    DB::table('bsi_pengumuman_file')->insert([
                        'pengumuman_id' => $id, 'nama_asli' => $this->namaAman($f->getClientOriginalName()), 'path' => $path,
                        'mime' => mb_substr($mimeFile[$i], 0, 100), 'ukuran' => (int) $f->getSize(), 'created_at' => now(),
                    ]);
                }
                if ($snapExcel) {
                    $rows = array_map(function ($r) { return [$r['kategori'], $r['nama'], $r['harga']]; }, $snapExcel);
                    $path = $dir . '/' . Str::random(32) . '.xlsx';
                    Excel::store(new DaftarHargaExport('Harga BSI', ['Kategori', 'Jenis Sampah', 'Harga BSI per kg (Rp)'], $rows), $path, 'local');
                    DB::table('bsi_pengumuman_file')->insert([
                        'pengumuman_id' => $id, 'nama_asli' => 'daftar-harga-acuan-BSI-' . date('Y-m-d') . '.xlsx', 'path' => $path,
                        'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'ukuran' => (int) Storage::disk('local')->size($path), 'created_at' => now(),
                    ]);
                }
                $url = url(config('crudbooster.ADMIN_PATH', 'admin') . '/pesan_bsi/baca/' . $id);
                $teks = 'Pengumuman BSI: ' . strip_tags($judul) . ($jmlLampiran ? ' (+' . $jmlLampiran . ' lampiran)' : '');
                foreach ($valid as $bsuId) {
                    DB::table('bsi_pengumuman_tujuan')->insert(['pengumuman_id' => $id, 'bsu_id' => $bsuId, 'created_at' => now()]);
                    DB::table('cms_notifications')->insert([
                        'id_cms_users' => $bsuId, 'content' => mb_substr($teks, 0, 255),   // lonceng CMS dirakit via JS: tag HTML dibuang
                        'url' => $url, 'is_read' => 0, 'created_at' => now(),
                    ]);
                }
            });
        } catch (\Throwable $e) {
            if ($id) {
                Storage::disk('local')->deleteDirectory('bsi_lampiran/' . $id);   // jangan tinggalkan file yatim
            }
            throw $e;
        }

        return ['id' => $id, 'jumlah' => count($valid), 'lampiran' => $jmlLampiran];
    }
}
