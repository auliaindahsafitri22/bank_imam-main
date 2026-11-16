<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Menyalin katalog sampah (kategori, nama jenis, harga/kg, poin/kg, deskripsi) dari BSU sumber
 * (BSU Mercu Buana) ke akun BSU lain. Jenis yang namanya sudah ada di akun tujuan dilewati,
 * sehingga aman dijalankan berulang dan tidak menimpa harga yang sudah diatur BSU tujuan.
 * Gambar tidak ikut disalin.
 */
class ImporKatalogBsuService
{
    public static function kunci($t)
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $t)));
    }

    /** Akun BSU sumber: dari .env (KATALOG_SUMBER_BSU_ID) atau, bila kosong, BSU yang namanya memuat "mercu". */
    public function sumber()
    {
        $id = (int) config('services.katalog_sumber_bsu_id');
        $q = DB::table('cms_users')->where('id_cms_privileges', 4);
        $row = $id > 0
            ? (clone $q)->where('id', $id)->first(['id', 'name'])
            : (clone $q)->where('name', 'like', '%mercu%')->orderBy('id')->first(['id', 'name']);
        if (!$row) {
            throw new InvalidArgumentException('Akun BSU sumber (Mercu Buana) tidak ditemukan. Hubungi Super Admin untuk mengisi KATALOG_SUMBER_BSU_ID.');
        }
        return $row;
    }

    /**
     * Rencana impor untuk satu akun BSU tujuan (belum menyimpan apa pun).
     * @return array [sumber, total_sumber, baru[], sudah_ada[], kategori_baru[kunci => [nama, deskripsi]]]
     */
    public function rencana($adminId)
    {
        $adminId = (int) $adminId;
        $sumber = $this->sumber();
        if ((int) $sumber->id === $adminId) {
            throw new InvalidArgumentException('Akun ini adalah sumber katalog, tidak perlu diimpor.');
        }

        $data = DB::table('sampah as s')
            ->leftJoin('sampah_categories as c', 'c.id', '=', 's.category_id')
            ->where('s.admin_id', $sumber->id)
            ->orderBy('c.name')->orderBy('s.name')
            ->get(['s.name', 's.deskripsi', 's.price_per_kg', 's.poin_per_kg', 'c.name as kategori', 'c.description as kategori_deskripsi']);

        $adaSampah = [];
        foreach (DB::table('sampah')->where('admin_id', $adminId)->pluck('name') as $n) {
            $adaSampah[self::kunci($n)] = true;
        }
        $adaKategori = [];
        foreach (DB::table('sampah_categories')->where('admin_id', $adminId)->pluck('name') as $n) {
            $adaKategori[self::kunci($n)] = true;
        }

        $baru = [];
        $sudahAda = [];
        $kategoriBaru = [];
        $terlihat = [];
        foreach ($data as $r) {
            $k = self::kunci($r->name);
            if ($k === '' || isset($terlihat[$k])) {
                continue; // nama kosong / dobel di sumber
            }
            $terlihat[$k] = true;
            if (isset($adaSampah[$k])) {
                $sudahAda[] = trim($r->name);
                continue;
            }
            $kat = trim((string) $r->kategori);
            $kk = self::kunci($kat);
            if ($kk !== '' && !isset($adaKategori[$kk]) && !isset($kategoriBaru[$kk])) {
                $kategoriBaru[$kk] = ['nama' => $kat, 'deskripsi' => (string) $r->kategori_deskripsi];
            }
            $baru[] = [
                'kategori'  => $kat,
                'nama'      => trim($r->name),
                'deskripsi' => $r->deskripsi,
                'harga'     => (int) $r->price_per_kg,
                'poin'      => (int) $r->poin_per_kg,
            ];
        }

        return [
            'sumber'        => ['id' => (int) $sumber->id, 'nama' => $sumber->name],
            'total_sumber'  => count($terlihat),
            'baru'          => $baru,
            'sudah_ada'     => $sudahAda,
            'kategori_baru' => $kategoriBaru,
        ];
    }

    /** Menyimpan impor. Dihitung ulang saat ini juga, jadi aman dari klik ganda. */
    public function terapkan($adminId)
    {
        $adminId = (int) $adminId;
        $r = $this->rencana($adminId);
        if (!count($r['baru'])) {
            throw new InvalidArgumentException('Tidak ada jenis sampah baru untuk diimpor. Semua jenis dari Mercu Buana sudah ada di akun Anda.');
        }

        DB::transaction(function () use ($r, $adminId) {
            $now = now();
            $peta = [];
            foreach (DB::table('sampah_categories')->where('admin_id', $adminId)->get(['id', 'name']) as $c) {
                $peta[self::kunci($c->name)] = (int) $c->id;
            }
            foreach ($r['kategori_baru'] as $kk => $kat) {
                $peta[$kk] = DB::table('sampah_categories')->insertGetId([
                    'admin_id'    => $adminId,
                    'name'        => $kat['nama'],
                    'description' => $kat['deskripsi'],
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
            foreach ($r['baru'] as $b) {
                $kk = self::kunci($b['kategori']);
                DB::table('sampah')->insert([
                    'admin_id'     => $adminId,
                    'category_id'  => $kk !== '' ? ($peta[$kk] ?? null) : null,
                    'name'         => $b['nama'],
                    'deskripsi'    => $b['deskripsi'],
                    'price_per_kg' => $b['harga'],
                    'poin_per_kg'  => $b['poin'],
                    'created_at'   => $now,
                    'updated_at'   => $now,
                ]);
            }
        });

        return [
            'jenis_ditambah'    => count($r['baru']),
            'kategori_ditambah' => count($r['kategori_baru']),
            'dilewati'          => count($r['sudah_ada']),
            'sumber'            => $r['sumber']['nama'],
        ];
    }
}
