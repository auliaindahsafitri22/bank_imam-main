<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$tabel = null;
foreach (array('kelurahans', 'kelurahan') as $t) { if (Schema::hasTable($t)) { $tabel = $t; break; } }
if (!$tabel) exit("Tabel kelurahan tidak ketemu (kelurahans / kelurahan).\n");

echo "===== TABEL KELURAHAN: $tabel =====\n";
echo 'Kolom: ' . implode(', ', Schema::getColumnListing($tabel)) . "\n\n";

$nama = array();
foreach (DB::table($tabel)->orderBy('id')->get() as $r) {
    $a = (array) $r; $p = array();
    foreach ($a as $k => $v) { if (!preg_match('/_at$/', $k)) $p[] = "$k=$v"; }
    echo '  ' . implode(' | ', $p) . "\n";
    foreach (array('nama', 'name', 'kelurahan', 'nama_kelurahan') as $c) { if (isset($a[$c])) { $nama[$r->id] = $a[$c]; break; } }
}

$kolom = null;
foreach (Schema::getColumnListing('cms_users') as $c) { if (strpos($c, 'kelurahan') !== false) { $kolom = $c; break; } }
echo "\nKolom kelurahan di cms_users: " . ($kolom ? $kolom : '(TIDAK ADA)') . "\n";
if (!$kolom) exit("Tidak bisa mengelompokkan BSU tanpa kolom itu.\n");

$bsu = DB::table('cms_users')->where('id_cms_privileges', 4)->orderBy('id')->get(array('id', 'name', $kolom));
$grup = array(); $tanpa = array();
foreach ($bsu as $b) {
    $k = $b->$kolom;
    if (!$k) { $tanpa[] = $b; continue; }
    $grup[$k][] = $b;
}

echo "\n===== BSU PER KELURAHAN (total BSU: " . count($bsu) . ") =====\n";
foreach ($nama as $id => $n) {
    $list = isset($grup[$id]) ? $grup[$id] : array();
    echo "\n#$id $n: " . count($list) . " BSU\n";
    foreach ($list as $b) echo "   - #{$b->id} {$b->name}\n";
}
foreach ($grup as $id => $list) {
    if (!isset($nama[$id])) { echo "\n(kelurahan_id $id TIDAK ADA di tabel): " . count($list) . " BSU\n"; foreach ($list as $b) echo "   - #{$b->id} {$b->name}\n"; }
}
echo "\n===== BSU TANPA KELURAHAN: " . count($tanpa) . " =====\n";
foreach ($tanpa as $b) echo "   - #{$b->id} {$b->name}\n";

echo "\n===== AKUN LAIN YANG MEMAKAI KELURAHAN (selain BSU) =====\n";
foreach (DB::table('cms_users')->where('id_cms_privileges', '!=', 4)->whereNotNull($kolom)->get(array('id', 'name', 'id_cms_privileges', $kolom)) as $u) {
    echo "   - #{$u->id} {$u->name} (privilege {$u->id_cms_privileges}) -> kelurahan_id {$u->$kolom}\n";
}