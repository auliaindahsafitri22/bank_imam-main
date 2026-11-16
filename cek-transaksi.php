<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function pot($v, $n = 50) {
    $s = preg_replace('/\s+/', ' ', (string) $v);
    return mb_strlen($s) > $n ? mb_substr($s, 0, $n) . '...' : $s;
}

$semua = array_map(function ($r) { $a = array_values((array) $r); return $a[0]; }, DB::select('SHOW TABLES'));

echo "===== TABEL TERKAIT TRANSAKSI =====\n";
foreach ($semua as $t) {
    if (!preg_match('/user|setor|transaksi|transaction|bsu|jual|sales|kas|bsi|saldo|tukar/i', $t)) continue;
    $jml = DB::table($t)->count();
    echo "\n$t ($jml baris)\n  kolom: " . implode(', ', Schema::getColumnListing($t)) . "\n";
}

echo "\n===== CONTOH 1 BARIS (dipotong) =====\n";
foreach (array('transactions', 'sales', 'cms_users') as $t) {
    if (!Schema::hasTable($t)) { echo "\n$t: tidak ada\n"; continue; }
    $r = DB::table($t)->orderBy('id', 'desc')->first();
    echo "\n$t:\n";
    if ($r) foreach ((array) $r as $k => $v) {
        if (preg_match('/password|remember|photo|picture|api_key/i', $k)) continue;
        echo "  $k = " . pot($v) . "\n";
    }
}

echo "\n===== JUMLAH JENIS SAMPAH PER BSU (sampah.admin_id) =====\n";
$rows = DB::table('sampah')->leftJoin('cms_users', 'cms_users.id', '=', 'sampah.admin_id')
    ->select('sampah.admin_id', 'cms_users.name', DB::raw('count(*) as jml'))
    ->groupBy('sampah.admin_id', 'cms_users.name')->orderBy('sampah.admin_id')->get();
foreach ($rows as $x) echo '  #' . $x->admin_id . ' ' . ($x->name ?: '(tidak ada akunnya)') . ': ' . $x->jml . "\n";