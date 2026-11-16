<?php
$f = __DIR__ . '/resources/views/user-app/sampah.blade.php';
$s = file_get_contents($f);
$n = 0;
$s = str_replace(
    '<section class="px-3 pb-4" id="blok-istilah">',
    '<section class="px-3" id="blok-istilah" style="padding-bottom:110px;">',
    $s, $n
);
if ($n !== 1) { exit("Tidak ditemukan atau sudah diubah.\n"); }
file_put_contents($f, $s);
echo "OK\n";