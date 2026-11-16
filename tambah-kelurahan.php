<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$simpan = in_array('simpan', $argv, true);
$resmi  = array('Joglo', 'Kembangan Selatan', 'Kembangan Utara', 'Meruya Selatan', 'Meruya Utara', 'Srengseng');

echo $simpan ? "MODE TAMBAH\n\n" : "MODE SIMULASI (belum mengubah data)\n\n";

$ada = array();
foreach (DB::table('kelurahans')->get() as $r) { $ada[mb_strtolower(trim($r->nama_kelurahan))] = $r; }

foreach ($resmi as $n) {
    $k = mb_strtolower($n);
    if (isset($ada[$k])) {
        if ($ada[$k]->deleted_at) {
            echo "PULIHKAN: $n (sebelumnya terhapus)\n";
            if ($simpan) DB::table('kelurahans')->where('id', $ada[$k]->id)->update(array('deleted_at' => null));
        } else {
            echo "SUDAH ADA: $n (id {$ada[$k]->id})\n";
        }
        continue;
    }
    echo "TAMBAH: $n\n";
    if ($simpan) DB::table('kelurahans')->insert(array('nama_kelurahan' => $n, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')));
}

// Entri yang bukan kelurahan resmi (misalnya "Kecamatan Kembangan"): hanya dilaporkan, tidak dihapus
foreach ($ada as $k => $r) {
    $resmiCek = false;
    foreach ($resmi as $n) { if (mb_strtolower($n) === $k) $resmiCek = true; }
    if ($resmiCek) continue;
    $pakai = DB::table('cms_users')->where('kelurahan_id', $r->id)->count();
    echo "PERIKSA: \"{$r->nama_kelurahan}\" (id {$r->id}) bukan kelurahan resmi, dipakai $pakai akun\n";
}
if (!$simpan) echo "\nTambah: php tambah-kelurahan.php simpan\n";