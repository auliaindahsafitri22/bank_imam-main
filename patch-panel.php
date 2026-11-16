<?php
$f = __DIR__ . '/resources/views/user-app/sampah.blade.php';
$s = @file_get_contents($f);
if ($s === false) { exit("File tidak ditemukan: $f\n"); }
if (strpos($s, 'id="panel-info"') === false) { exit("Panel versi kartu kecil tidak ditemukan, tidak ada yang diubah.\n"); }

$n1 = 0; $n2 = 0;
$s2 = str_replace('max-width:380px;', 'max-width:360px;', $s, $n1);
$s2 = str_replace('background:#fff; border-radius:16px;', 'background:#fffbeb; border:1px solid #fde68a; border-radius:16px;', $s2, $n2);

if ($n1 !== 1 || $n2 !== 1) { exit("Struktur berbeda, tidak ada yang diubah (n1=$n1, n2=$n2).\n"); }
copy($f, $f . '.bak3');
file_put_contents($f, $s2);
echo "Selesai. Cadangan: sampah.blade.php.bak3\n";