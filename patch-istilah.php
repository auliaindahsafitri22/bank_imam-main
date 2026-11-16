<?php
$f = __DIR__ . '/resources/views/user-app/sampah.blade.php';
$s = @file_get_contents($f);
if ($s === false) { exit("File tidak ditemukan: $f\n"); }
if (strpos($s, 'istilah-lipat') !== false) { exit("Sudah pernah di-patch, tidak ada yang diubah.\n"); }

$new = <<<'EOT'
<section class="px-3 pb-4" id="blok-istilah">
        <details id="istilah-lipat">
            <summary class="d-flex align-items-center gap-2 mb-2" style="cursor:pointer; list-style:none;">
                <div style="width:4px; height:18px; background:#d97706; border-radius:4px;"></div>
                <h6 class="fw-bold m-0" style="font-size:15px;">Istilah Khusus <span style="font-size:12px; font-weight:500; color:#6b7280;">({{ count($istilah) }}) · ketuk untuk membuka</span></h6>
            </summary>
            __LOOP__
        </details>
    </section>
EOT;

$n1 = 0;
$s2 = preg_replace_callback('/<section class="px-3 pb-4" id="blok-istilah">(.*?)<\/section>/s', function ($m) use ($new) {
    if (!preg_match('/@foreach\(\$istilah as \$t\).*?@endforeach/s', $m[1], $l)) { return $m[0]; }
    return str_replace('__LOOP__', $l[0], $new);
}, $s, 1, $n1);

$n2 = 0;
$s2 = str_replace(
    "document.querySelectorAll('.item-istilah')",
    "var dl=document.getElementById('istilah-lipat'); if(dl){ dl.open=!!t; }\n    document.querySelectorAll('.item-istilah')",
    $s2, $n2
);

if ($n1 !== 1 || $n2 !== 1 || strpos($s2, '__LOOP__') !== false) {
    exit("Struktur file berbeda dari perkiraan, tidak ada yang diubah (n1=$n1, n2=$n2).\n");
}
copy($f, $f . '.bak');
file_put_contents($f, $s2);
echo "Selesai. Cadangan: sampah.blade.php.bak\n";