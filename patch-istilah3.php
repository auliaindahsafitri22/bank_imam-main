<?php
$f = __DIR__ . '/resources/views/user-app/sampah.blade.php';
$s = @file_get_contents($f);
if ($s === false) { exit("File tidak ditemukan: $f\n"); }
if (strpos($s, 'istilah-lipat') === false) { exit("Patch istilah pertama belum terpasang. Jalankan patch-istilah.php dulu.\n"); }
if (strpos($s, 'daftarIstilah') !== false) { exit("Sudah pernah di-patch, tidak ada yang diubah.\n"); }

// 1) Susun daftar: istilah kamus + sampah yang punya penjelasan (tanpa harga), tanpa nama kembar.
$blokPhp = <<<'EOT'
@php
        $daftarIstilah = []; $sudahAda = [];
        foreach (($istilah ?? []) as $t) {
            $daftarIstilah[] = ['nama' => $t->istilah, 'desk' => $t->penjelasan];
            $sudahAda[mb_strtolower(trim($t->istilah))] = true;
        }
        foreach ($sampahByCategory as $kat => $lst) {
            foreach ($lst as $it) {
                $kunci = mb_strtolower(trim($it->name));
                if (trim((string) $it->deskripsi) !== '' && !isset($sudahAda[$kunci])) {
                    $daftarIstilah[] = ['nama' => $it->name, 'desk' => $it->deskripsi];
                    $sudahAda[$kunci] = true;
                }
            }
        }
        usort($daftarIstilah, function ($a, $b) { return strcasecmp($a['nama'], $b['nama']); });
    @endphp
    @if(count($daftarIstilah))
EOT;

$loopBaru = <<<'EOT'
@foreach($daftarIstilah as $t)
            <div class="rounded-3 p-3 mb-2 item-istilah" data-cari="{{ mb_strtolower($t['nama'].' '.$t['desk']) }}" style="background:#fffbeb; border:1px solid #fde68a;">
                <p class="fw-bold m-0" style="font-size:13px;">{{ $t['nama'] }}</p>
                <p class="m-0" style="font-size:12px; color:#4b5563;">{{ $t['desk'] }}</p>
            </div>
            @endforeach
EOT;

$n1 = 0; $n2 = 0; $n3 = 0;
$s2 = str_replace('@if(isset($istilah) && count($istilah))', $blokPhp, $s, $n1);
$s2 = str_replace('{{ count($istilah) }}', '{{ count($daftarIstilah) }}', $s2, $n2);
$s2 = preg_replace_callback('/@foreach\(\$istilah as \$t\).*?@endforeach/s', function ($m) use ($loopBaru) {
    return $loopBaru;
}, $s2, 1, $n3);

// 2) Jarak bawah agar tidak tertutup menu bawah (aman bila sudah pernah diubah).
$s2 = str_replace(
    '<section class="px-3 pb-4" id="blok-istilah">',
    '<section class="px-3" id="blok-istilah" style="padding-bottom:110px;">',
    $s2
);

if ($n1 !== 1 || $n2 !== 1 || $n3 !== 1) {
    exit("Struktur file berbeda dari perkiraan, tidak ada yang diubah (n1=$n1, n2=$n2, n3=$n3).\n");
}
copy($f, $f . '.bak2');
file_put_contents($f, $s2);
echo "Selesai. Cadangan: sampah.blade.php.bak2\n";