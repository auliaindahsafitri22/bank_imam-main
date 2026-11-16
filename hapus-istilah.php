<?php
$root   = __DIR__;
$simpan = in_array('simpan', $argv, true);   // tanpa "simpan" = simulasi saja
$hanya  = null;                              // opsional: file=bagian-nama-path
foreach ($argv as $a) { if (strpos($a, 'file=') === 0) $hanya = strtolower(str_replace('\\', '/', substr($a, 5))); }

function penutup($s, $tag, $mulai) {
    if (!preg_match_all('/<(\/?)' . $tag . '\b[^>]*>/i', $s, $m, PREG_SET_ORDER | PREG_OFFSET_CAPTURE, $mulai)) return null;
    $d = 0;
    foreach ($m as $x) {
        $d += ($x[1][0] === '/') ? -1 : 1;
        if ($d === 0) return $x[0][1] + strlen($x[0][0]);
    }
    return null;
}

// Cari blok (section/div) terkecil yang memuat judul "Istilah Khusus" beserta daftarnya
function blok_istilah($s) {
    if (!preg_match_all('/istilah\s+khusus/i', $s, $mm, PREG_OFFSET_CAPTURE)) return null;
    preg_match_all('/<(section|div|details|aside)\b[^>]*>/i', $s, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
    foreach ($mm[0] as $hit) {
        $pos = $hit[1];
        for ($i = count($tags) - 1; $i >= 0; $i--) {
            $start = $tags[$i][0][1];
            if ($start >= $pos) continue;
            $end = penutup($s, strtolower($tags[$i][1][0]), $start);
            if ($end === null || $end <= $pos) continue;
            $blok = substr($s, $start, $end - $start);
            if (strlen($blok) > 6000) continue;
            if (preg_match('/data-desk|popup-info-sampah|@extends|@section\s*\(|<script/i', $blok)) continue;
            if (!preg_match('/@foreach|\$istilah|<li\b|bodong/i', $blok)) continue;
            return array($start, $end);
        }
    }
    return null;
}

echo $simpan ? "MODE HAPUS\n\n" : "MODE SIMULASI (belum mengubah file)\n\n";

$totalBlok = 0;
$sisa = array();
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/resources/views', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $path = $f->getPathname();
    if (substr($path, -10) !== '.blade.php') continue;
    $norm = strtolower(str_replace('\\', '/', $path));
    if ($hanya !== null && strpos($norm, $hanya) === false) continue;

    $asli = file_get_contents($path);
    if (!preg_match('/istilah\s+khusus/i', $asli)) continue;

    $s = $asli; $hapus = 0;
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    while ($hapus < 5 && ($b = blok_istilah($s))) {
        $noBaris = substr_count(substr($s, 0, $b[0]), "\n") + 1;
        $cuplikan = preg_split('/\r?\n/', trim(strip_tags(substr($s, $b[0], $b[1] - $b[0]))));
        echo ($simpan ? 'DIHAPUS' : 'AKAN DIHAPUS') . ": $rel (baris sekitar $noBaris, " . ($b[1] - $b[0]) . " karakter)\n";
        echo '   isi: ' . substr(preg_replace('/\s+/', ' ', implode(' ', array_slice($cuplikan, 0, 6))), 0, 160) . "\n\n";
        $s = substr($s, 0, $b[0]) . substr($s, $b[1]);
        $hapus++; $totalBlok++;
    }

    if ($hapus > 0 && $simpan) {
        copy($path, $path . '.bak-istilah');
        file_put_contents($path, $s);
    }

    // sisa tulisan "istilah khusus" yang tidak dihapus otomatis (misalnya di dalam JavaScript)
    foreach (preg_split('/\r?\n/', $s) as $no => $baris) {
        if (preg_match('/istilah\s+khusus/i', $baris)) $sisa[] = " $rel:" . ($no + 1) . '  ' . trim($baris);
    }
}

echo "Blok yang " . ($simpan ? 'dihapus' : 'akan dihapus') . ": $totalBlok\n";
if ($sisa) {
    echo "\nTulisan 'istilah khusus' yang masih tersisa (tidak dihapus otomatis):\n" . implode("\n", $sisa) . "\n";
}
if ($totalBlok === 0 && !$sisa) {
    echo "Tidak ada yang cocok di resources/views. Jalankan: findstr /s /i /n \"istilah khusus\" app\\*.php public\\js\\*.js resources\\*.php\n";
}
if (!$simpan && $totalBlok > 0) {
    echo "\nIni simulasi. Hapus: php hapus-istilah.php simpan   (atau satu file: php hapus-istilah.php simpan file=user-app)\n";
}