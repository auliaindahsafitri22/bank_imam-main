<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function pot($s, $n = 70) {
    $s = preg_replace('/\s+/', ' ', (string) $s);
    return mb_strlen($s) > $n ? mb_substr($s, 0, $n) . '...' : $s;
}
function tampil($judul, $path, $maks = 140) {
    echo "\n===== $judul ($path) =====\n";
    if (!is_file($path)) { echo "(file tidak ada)\n"; return; }
    $b = file($path);
    foreach (array_slice($b, 0, $maks) as $i => $l) echo ($i + 1) . ': ' . pot(rtrim($l), 200) . "\n";
    if (count($b) > $maks) echo '... (' . count($b) . " baris, dipotong)\n";
}

try {
    $m = new \App\Models\EducationalNews;
    $t = $m->getTable();
    echo "Tabel: $t\n";
    echo 'Kolom: ' . implode(', ', Illuminate\Support\Facades\Schema::getColumnListing($t)) . "\n";
    $r = Illuminate\Support\Facades\DB::table($t)->first();
    if ($r) {
        echo "Contoh baris pertama (dipotong):\n";
        foreach ((array) $r as $k => $v) echo " - $k = " . pot($v) . "\n";
    }
} catch (Throwable $e) {
    echo 'Gagal membaca tabel: ' . $e->getMessage() . "\n";
}

$ctrl = __DIR__ . '/app/Http/Controllers/UserApp/EducationalNewsController.php';
tampil('CONTROLLER', $ctrl);
tampil('MODEL', __DIR__ . '/app/Models/EducationalNews.php', 60);

$src = is_file($ctrl) ? file_get_contents($ctrl) : '';
if (preg_match_all('/view\(\s*[\'"]([^\'"]+)[\'"]/', $src, $mm)) {
    foreach (array_unique($mm[1]) as $v) {
        tampil('VIEW ' . $v, __DIR__ . '/resources/views/' . str_replace('.', '/', $v) . '.blade.php', 200);
    }
}

echo "\n===== RUTE EDUKASI di routes/web.php =====\n";
foreach (file(__DIR__ . '/routes/web.php') as $no => $l) {
    if (preg_match('/edukasi|EducationalNews/i', $l)) echo ($no + 1) . ': ' . trim($l) . "\n";
}