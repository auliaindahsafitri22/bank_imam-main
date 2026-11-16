<?php
$cf = __DIR__ . '/app/Http/Controllers/UserApp/RegisterController.php';
$s  = file_get_contents($cf);

$n = 0;
$s2 = preg_replace(
    '/Kelurahan::whereIn\(\s*\'id\'\s*,\s*\$idKelurahan\s*\)\s*->orderBy\(/',
    "Kelurahan::whereNull('deleted_at')->orderBy(",
    $s, 1, $n
);

if ($n === 0) {
    echo "Baris whereIn tidak ketemu, mungkin sudah diubah.\n";
} else {
    copy($cf, $cf . '.bak-kelurahan');
    file_put_contents($cf, $s2);
    echo "OK  RegisterController diubah: semua kelurahan tampil (cadangan: RegisterController.php.bak-kelurahan)\n";
}

$vf = __DIR__ . '/resources/views/user-app/register.blade.php';
$b  = file($vf);
$mulai = 0;
foreach ($b as $i => $l) { if (strpos($l, "getElementById('kelurahan_id')") !== false) { $mulai = max(0, $i - 6); break; } }
echo "\n===== register.blade.php, baris " . ($mulai + 1) . " dst =====\n";
foreach (array_slice($b, $mulai, 70, true) as $i => $l) echo ($i + 1) . ': ' . rtrim($l) . "\n";