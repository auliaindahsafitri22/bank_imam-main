<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "===== TABEL WILAYAH DI DATABASE =====\n";
foreach (DB::select('SHOW TABLES') as $r) {
    $a = array_values((array) $r); $t = $a[0];
    if (!preg_match('/kecamatan|kelurahan|wilayah|rw|region/i', $t)) continue;
    echo "\n$t (" . DB::table($t)->count() . " baris)\n  kolom: " . implode(', ', Schema::getColumnListing($t)) . "\n";
    foreach (DB::table($t)->limit(12)->get() as $x) {
        $p = array();
        foreach ((array) $x as $k => $v) { if (!preg_match('/_at$/', $k)) $p[] = "$k=$v"; }
        echo '    ' . implode(' | ', $p) . "\n";
    }
}

echo "\n===== FILE YANG MEMUAT DAFTAR / FORM KELURAHAN =====\n";
$pola = '/Pilih Kelurahan|Kembangan Selatan|Kecamatan Kembangan|Kembangan Utara|Srengseng|kelurahan_id|Kelurahans?::|kelurahans/i';
$dirs = array('app', 'routes', 'resources/views', 'resources/js', 'public/js', 'config');
foreach ($dirs as $d) {
    if (!is_dir(__DIR__ . '/' . $d)) continue;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/' . $d, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile() || !preg_match('/\.(php|js|json|html)$/i', $f->getFilename())) continue;
        if (preg_match('/\.bak/i', $f->getFilename())) continue;
        $rel = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $f->getPathname());
        foreach (file($f->getPathname()) as $no => $baris) {
            if (preg_match($pola, $baris)) {
                $t = trim($baris);
                echo " $rel:" . ($no + 1) . '  ' . (strlen($t) > 150 ? substr($t, 0, 150) . '...' : $t) . "\n";
            }
        }
    }
}

echo "\n===== RUTE TERKAIT WILAYAH =====\n";
foreach (file(__DIR__ . '/routes/web.php') as $no => $l) {
    if (preg_match('/kelurahan|kecamatan|wilayah|register|daftar/i', $l)) echo ' web.php:' . ($no + 1) . '  ' . trim($l) . "\n";
}
if (is_file(__DIR__ . '/routes/api.php')) {
    foreach (file(__DIR__ . '/routes/api.php') as $no => $l) {
        if (preg_match('/kelurahan|kecamatan|wilayah|bsu/i', $l)) echo ' api.php:' . ($no + 1) . '  ' . trim($l) . "\n";
    }
}