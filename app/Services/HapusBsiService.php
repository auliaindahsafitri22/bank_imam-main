<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Hapus "lunak" harga acuan BSI dan akun BSI. Data tetap ada (dihapus_at, dihapus_oleh) supaya riwayat utuh
 * dan bisa dipulihkan; di semua daftar aktif data yang dihapus tidak muncul.
 *
 * Saat harga acuan dihapus, jenis sampah BSU yang terhubung diputus dari harga BSI itu: harga beli nasabah
 * TIDAK berubah (sama seperti tombol "Putuskan"), margin dikosongkan, dicatat di riwayat BSU dengan
 * sumber "dihapus", dan BSU mendapat satu notifikasi ringkas.
 */
class HapusBsiService
{
    /**
     * Hapus satu jenis harga acuan.
     * @param bool $kirimNotif false bila pemanggil mengirim satu notifikasi gabungan sendiri (hapus akun BSI)
     * @return array [bsu, sampah, perBsu => [bsu_id => [nama jenis...]]]
     */
    public function hapusHarga($bsiHargaId, $oleh, $catatan = null, $waktu = null, $kirimNotif = true)
    {
        $waktu = $waktu ?: now();
        $hasil = ['bsu' => 0, 'sampah' => 0, 'perBsu' => []];
        DB::transaction(function () use ($bsiHargaId, $oleh, $catatan, $waktu, $kirimNotif, &$hasil) {
            $item = DB::table('bsi_harga')->where('id', (int) $bsiHargaId)->lockForUpdate()->first();
            if (!$item) {
                throw new InvalidArgumentException('Jenis harga acuan tidak ditemukan.');
            }
            if ($item->dihapus_at) {
                throw new InvalidArgumentException('Jenis harga acuan ini sudah dihapus.');
            }
            DB::table('bsi_harga')->where('id', $item->id)->update(['dihapus_at' => $waktu, 'dihapus_oleh' => $oleh, 'updated_at' => now()]);

            $ket = $catatan ?: 'Harga acuan "' . $item->nama . '" dihapus; sambungan diputus, harga beli nasabah tetap';
            $perBsu = [];
            foreach (DB::table('sampah')->where('bsi_harga_id', $item->id)->lockForUpdate()->get() as $s) {
                DB::table('sampah')->where('id', $s->id)->update([
                    'bsi_harga_id' => null, 'margin_type' => 'nominal', 'margin_value' => 0, 'auto_sync' => 1, 'updated_at' => now(),
                ]);
                app(HargaService::class)->catatHargaManual($s->id, $s->admin_id, $item->id, (int) $s->price_per_kg, (int) $s->price_per_kg, $oleh, $ket, 'dihapus');
                $perBsu[$s->admin_id][] = strip_tags($item->nama);
                $hasil['sampah']++;
            }
            $hasil['bsu'] = count($perBsu);
            $hasil['perBsu'] = $perBsu;
            if ($kirimNotif) {
                $this->notifikasi($perBsu);
            }
        });
        return $hasil;
    }

    /** Pulihkan jenis harga acuan yang dihapus. Sambungan BSU tidak dipulihkan otomatis. */
    public function pulihkanHarga($bsiHargaId)
    {
        DB::transaction(function () use ($bsiHargaId) {
            $item = DB::table('bsi_harga')->where('id', (int) $bsiHargaId)->lockForUpdate()->first();
            if (!$item || !$item->dihapus_at) {
                throw new InvalidArgumentException('Jenis harga acuan yang dihapus tidak ditemukan.');
            }
            if ($item->bsi_id !== null && DB::table('cms_users')->where('id', $item->bsi_id)->whereNotNull('dihapus_at')->exists()) {
                throw new InvalidArgumentException('Akun BSI pemilik jenis ini sedang dihapus. Pulihkan akun BSI-nya dulu.');
            }
            $bentrok = WilayahBsiService::batasiPemilik(DB::table('bsi_harga'), $item->bsi_id === null ? null : (int) $item->bsi_id)
                ->whereNull('dihapus_at')->whereRaw('LOWER(TRIM(nama)) = ?', [KatalogHargaService::kunci($item->nama)])->exists();
            if ($bentrok) {
                throw new InvalidArgumentException('Sudah ada jenis aktif bernama "' . strip_tags($item->nama) . '" di daftar yang sama.');
            }
            DB::table('bsi_harga')->where('id', $item->id)->update(['dihapus_at' => null, 'dihapus_oleh' => null, 'updated_at' => now()]);
        });
    }

    /**
     * Hapus akun BSI: akun dinonaktifkan (tidak bisa masuk), kelurahan dilepas agar bisa dipegang BSI lain,
     * dan seluruh jenis harga acuannya ikut dihapus (BSU terkait diputus seperti di hapusHarga).
     * @return array [harga, bsu, sampah]
     */
    public function hapusBsi($bsiId, $oleh)
    {
        $bsiId = (int) $bsiId;
        if ($bsiId === (int) $oleh) {
            throw new InvalidArgumentException('Anda tidak bisa menghapus akun Anda sendiri.');
        }
        $wilayah = app(WilayahBsiService::class);
        $waktu = now();
        $hasil = ['harga' => 0, 'bsu' => 0, 'sampah' => 0];
        DB::transaction(function () use ($bsiId, $oleh, $wilayah, $waktu, &$hasil) {
            if (!$wilayah->adalahAkunBsi($bsiId)) {
                throw new InvalidArgumentException('Akun BSI tidak ditemukan atau sudah dihapus.');
            }
            $akun = DB::table('cms_users')->where('id', $bsiId)->lockForUpdate()->first();
            $bsuTercakup = count($wilayah->bsuIdsBsi($bsiId));

            DB::table('cms_users')->where('id', $bsiId)->update([
                'dihapus_at' => $waktu, 'dihapus_oleh' => $oleh, 'dihapus_kelurahan_id' => $akun->kelurahan_id,
                'kelurahan_id' => null, 'status' => 'Inactive', 'updated_at' => now(),
            ]);

            $gabung = [];
            foreach (DB::table('bsi_harga')->where('bsi_id', $bsiId)->whereNull('dihapus_at')->pluck('id') as $hid) {
                $r = $this->hapusHarga($hid, $oleh, 'BSI "' . $akun->name . '" dihapus; sambungan diputus, harga beli nasabah tetap', $waktu, false);
                $hasil['harga']++;
                $hasil['sampah'] += $r['sampah'];
                foreach ($r['perBsu'] as $bsuId => $nama) {
                    $gabung[$bsuId] = array_merge($gabung[$bsuId] ?? [], $nama);
                }
            }
            $this->notifikasi($gabung);   // satu notifikasi per BSU
            $hasil['bsu'] = $bsuTercakup;
        });
        return $hasil;
    }

    /**
     * Pulihkan akun BSI ke kelurahan sebelumnya (harus masih kosong) beserta jenis harga yang ikut terhapus
     * bersamanya. Akun dipulihkan dalam status Inactive; aktifkan lewat menu Akun BSI. Sambungan BSU tidak dipulihkan.
     */
    public function pulihkanBsi($bsiId)
    {
        $hasil = ['harga' => 0];
        DB::transaction(function () use ($bsiId, &$hasil) {
            $akun = DB::table('cms_users')->where('id', (int) $bsiId)->whereNotNull('dihapus_at')->lockForUpdate()->first();
            if (!$akun || (int) $akun->id_cms_privileges !== app(WilayahBsiService::class)->idPrivBsi()) {
                throw new InvalidArgumentException('Akun BSI yang dihapus tidak ditemukan.');
            }
            if ($akun->dihapus_kelurahan_id) {
                app(WilayahBsiService::class)->pastikanKelurahanTersedia($akun->dihapus_kelurahan_id, $akun->id);
            }
            DB::table('cms_users')->where('id', $akun->id)->update([
                'kelurahan_id' => $akun->dihapus_kelurahan_id, 'dihapus_at' => null, 'dihapus_oleh' => null,
                'dihapus_kelurahan_id' => null, 'status' => 'Inactive', 'updated_at' => now(),
            ]);
            // Hanya jenis yang terhapus bersama akun (stempel waktu sama).
            $hasil['harga'] = (int) DB::table('bsi_harga')->where('bsi_id', $akun->id)->where('dihapus_at', $akun->dihapus_at)
                ->update(['dihapus_at' => null, 'dihapus_oleh' => null, 'updated_at' => now()]);
        });
        return $hasil;
    }

    private function notifikasi(array $perBsu)
    {
        foreach ($perBsu as $bsuId => $nama) {
            $isi = count($nama) === 1
                ? 'Harga acuan "' . $nama[0] . '" dihapus. Sambungan diputus; harga beli nasabah tetap.'
                : count($nama) . ' harga acuan dihapus (' . implode(', ', array_slice($nama, 0, 3)) . (count($nama) > 3 ? ', dll' : '') . '). Sambungan diputus; harga beli nasabah tetap.';
            DB::table('cms_notifications')->insert([
                'id_cms_users' => $bsuId, 'content' => mb_substr($isi, 0, 255),
                'url' => url(config('crudbooster.ADMIN_PATH', 'admin') . '/sampah/harga'), 'is_read' => 0, 'created_at' => now(),
            ]);
        }
    }
}
