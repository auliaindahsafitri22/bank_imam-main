<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$simpan = in_array('simpan', $argv, true);
$pindah = null;
foreach ($argv as $a) { if (strpos($a, 'pindah=') === 0) $pindah = trim(substr($a, 7), " \"'"); }
$now = date('Y-m-d H:i:s');

echo $simpan ? "MODE SIMPAN\n\n" : "MODE SIMULASI (belum mengubah data)\n\n";

// Tabel yang punya kolom kelurahan_id
$tabelKel = array();
foreach (DB::select('SHOW TABLES') as $r) {
    $a = array_values((array) $r); $t = $a[0];
    if ($t !== 'kelurahans' && Schema::hasColumn($t, 'kelurahan_id')) $tabelKel[] = $t;
}

// ===== 1. Entri kelurahan yang sebenarnya nama kecamatan =====
echo "===== 1. ENTRI 'KECAMATAN' DI DAFTAR KELURAHAN =====\n";
$idSalah = array();
$salah = DB::table('kelurahans')->where('nama_kelurahan', 'like', '%kecamatan%')->whereNull('deleted_at')->get();
if (!count($salah)) echo "Tidak ada entri seperti itu.\n";
foreach ($salah as $k) {
    $idSalah[] = $k->id;
    echo "\"{$k->nama_kelurahan}\" (id {$k->id})\n";
    $pakai = array();
    foreach ($tabelKel as $t) {
        $n = DB::table($t)->where('kelurahan_id', $k->id)->count();
        if ($n > 0) $pakai[$t] = $n;
    }
    if (!$pakai) {
        echo "   tidak dipakai data apa pun -> akan disembunyikan\n";
        if ($simpan) DB::table('kelurahans')->where('id', $k->id)->update(array('deleted_at' => $now));
        continue;
    }
    foreach ($pakai as $t => $n) echo "   dipakai di $t: $n baris\n";
    foreach (DB::table('cms_users')->where('kelurahan_id', $k->id)->get(array('id', 'name', 'id_cms_privileges')) as $u) {
        echo "   akun #{$u->id} {$u->name} (privilege {$u->id_cms_privileges})\n";
    }
    if (!$pindah) { echo "   BELUM DIUBAH. Tentukan tujuan, contoh: php rapikan-wilayah.php pindah=\"Meruya Selatan\"\n"; continue; }
    $tuj = DB::table('kelurahans')->whereNull('deleted_at')->where('nama_kelurahan', $pindah)->first();
    if (!$tuj) { echo "   kelurahan tujuan \"$pindah\" tidak ketemu\n"; continue; }
    echo "   -> semua data dipindah ke {$tuj->nama_kelurahan} (id {$tuj->id}), entri lama disembunyikan\n";
    if ($simpan) {
        $dari = $k->id; $ke = $tuj->id; $tabel = $tabelKel;
        DB::transaction(function () use ($tabel, $dari, $ke, $now) {
            foreach ($tabel as $t) DB::table($t)->where('kelurahan_id', $dari)->update(array('kelurahan_id' => $ke));
            DB::table('kelurahans')->where('id', $dari)->update(array('deleted_at' => $now));
        });
    }
}

// ===== 2. Penamaan BSU yang tidak seragam =====
echo "\n===== 2. PENAMAAN BSU =====\n";
$bsu = DB::table('cms_users')->where('id_cms_privileges', 4)->orderBy('id')->get(array('id', 'name', 'kelurahan_id'));
$hitung = array(); $semuaNama = array();
foreach ($bsu as $b) {
    $semuaNama[mb_strtolower($b->name)] = true;
    if (preg_match('/^([A-Za-z]{1,6})-BSU\s+/', $b->name, $m)) {
        $p = strtoupper($m[1]);
        $hitung[$b->kelurahan_id][$p] = (isset($hitung[$b->kelurahan_id][$p]) ? $hitung[$b->kelurahan_id][$p] : 0) + 1;
    }
}
$dominan = array();
foreach ($hitung as $kel => $list) { arsort($list); $p = key($list); if ($list[$p] >= 3) $dominan[$kel] = $p; }

$ubah = 0;
foreach ($bsu as $b) {
    if (preg_match('/^[A-Za-z]{1,6}-BSU\s+/', $b->name)) continue;                 // sudah standar
    if (!isset($dominan[$b->kelurahan_id])) continue;                             // kelurahan ini tidak punya pola
    if (in_array($b->kelurahan_id, $idSalah) || stripos($b->name, 'kecamatan') !== false) continue;
    $rest = preg_replace('/^BSU[\s-]+/i', '', trim($b->name));
    $baru = $dominan[$b->kelurahan_id] . '-BSU ' . $rest;
    if (isset($semuaNama[mb_strtolower($baru)])) { echo "LEWAT #{$b->id} {$b->name}: nama '$baru' sudah dipakai\n"; continue; }
    echo "#{$b->id}  {$b->name}  ->  $baru\n";
    if ($simpan) DB::table('cms_users')->where('id', $b->id)->update(array('name' => $baru));
    $ubah++;
}
if (!$ubah) echo "Tidak ada nama yang perlu diseragamkan (atau tidak ada pola awalan yang dominan).\n";

echo "\nSelesai. " . ($simpan ? 'Perubahan disimpan.' : 'Ini simulasi, jalankan dengan kata "simpan" untuk menerapkan.') . "\n";