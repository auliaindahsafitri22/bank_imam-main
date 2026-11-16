<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$simpan = in_array('simpan', $argv, true);
if (!Schema::hasColumn('education', 'isi')) {
    exit("Kolom isi belum ada. Jalankan dulu migrasi (langkah 2).\n");
}

echo $simpan ? "MODE PINDAH\n\n" : "MODE SIMULASI (belum mengubah data)\n\n";
$n = 0;
foreach (DB::table('education')->get() as $r) {
    $u = trim((string) $r->url);
    $teks = $u !== '' && (preg_match('/\s/', $u) || strlen($u) > 500);   // bukan alamat web
    if (!$teks) continue;
    if (trim((string) $r->isi) !== '') { echo "LEWAT id {$r->id}: kolom isi sudah terisi\n"; continue; }
    echo "id {$r->id}: " . mb_substr($r->judul, 0, 70) . "\n   url -> isi: " . mb_substr($u, 0, 80) . "...\n";
    if ($simpan) DB::table('education')->where('id', $r->id)->update(['isi' => $u, 'url' => '']);
    $n++;
}
echo "\n" . ($simpan ? 'Dipindah' : 'Akan dipindah') . ": $n berita\n";
if (!$simpan && $n > 0) echo "Pindahkan: php pindah-isi-edukasi.php simpan\n";