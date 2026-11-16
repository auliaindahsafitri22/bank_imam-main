<?php
define('NAMA_IKON', 'palette');   // ganti kalau mau ikon lain, misalnya 'gear' atau 'brush'

$root   = __DIR__;
$simpan = in_array('simpan', $argv, true);   // tanpa "simpan" = simulasi saja
$POLA   = '/pengaturan\s+tampilan/i';

function ikon_di_baris($b) {
    if (preg_match('/<i\s[^>]*>\s*<\/i>/i', $b, $m)) {
        $tag = $m[0];
        if (preg_match('/class="([^"]*)"/i', $tag, $c)) {
            $k = $c[1];
            if (preg_match('/(^|\s)bi(\s|$)/', $k)) return array('bi', $tag);
            if (preg_match('/(^|\s)(fa|fas|far|fab|fal|fad|fa-solid|fa-regular|fa-brands|fa-light)(\s|$)/', $k)) return array('fa', $tag);
            if (preg_match('/(^|\s)ti(\s|$)/', $k)) return array('ti', $tag);
        }
        if (preg_match('/data-(lucide|feather)=/i', $tag)) return array('data', $tag);
    }
    if (preg_match('/<span\s[^>]*class="[^"]*material-icons[^"]*"[^>]*>[^<]*<\/span>/i', $b, $m)) return array('mat', $m[0]);
    if (preg_match('/<svg\b[^>]*>/i', $b, $m)) return array('svg', $m[0]);
    return null;
}

function ada_ikon_di($b) {
    return preg_match('/<i\s[^>]*>\s*<\/i>|<svg\b|<img\b|material-icons|data-(lucide|feather)=/i', $b);
}

function buat_ikon($tipe, $tag) {
    $nama = NAMA_IKON;
    if ($tipe === 'bi' || $tipe === 'fa' || $tipe === 'ti') {
        preg_match('/class="([^"]*)"/i', $tag, $c);
        $keep = array();
        foreach (preg_split('/\s+/', trim($c[1])) as $t) {
            if ($t === '') continue;
            if ($tipe === 'bi' && strpos($t, 'bi-') === 0) continue;
            if ($tipe === 'ti' && strpos($t, 'ti-') === 0) continue;
            if ($tipe === 'fa' && strpos($t, 'fa-') === 0
                && !preg_match('/^fa-(solid|regular|brands|light|duotone|fw|lg|xs|sm|1x|2x|3x|ul)$/', $t)) continue;
            $keep[] = $t;
        }
        $keep[] = $tipe . '-' . $nama;
        return preg_replace('/class="[^"]*"/i', 'class="' . implode(' ', $keep) . '"', $tag, 1);
    }
    if ($tipe === 'data') {
        $n = (stripos($tag, 'feather') !== false) ? 'droplet' : $nama;
        return preg_replace('/data-(lucide|feather)="[^"]*"/i', 'data-$1="' . $n . '"', $tag, 1);
    }
    if ($tipe === 'mat') {
        return preg_replace('/>[^<]*<\/span>/', '>' . $nama . '</span>', $tag, 1);
    }
    // SVG: ikuti ukuran ikon lain kalau ada, kalau tidak pakai bawaan
    $w = 20; $h = 20; $cls = ''; $gaya = ' style="flex:none;margin-right:8px;"';
    if ($tipe === 'svg') {
        if (preg_match('/\bwidth="(\d+)/i', $tag, $m)) $w = $m[1];
        $h = $w;
        if (preg_match('/\bheight="(\d+)/i', $tag, $m)) $h = $m[1];
        if (preg_match('/\bclass="([^"]*)"/i', $tag, $m)) { $cls = ' class="' . $m[1] . '"'; $gaya = ''; }
    }
    return '<svg' . $cls . $gaya . ' width="' . $w . '" height="' . $h . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">'
        . '<circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/>'
        . '<path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.555-2.503 5.555-5.554C21.965 6.012 17.461 2 12 2z"/></svg>';
}

$dir = $root . '/resources/views';
if (!is_dir($dir)) { exit("Folder tidak ditemukan: $dir\n"); }

echo $simpan ? "MODE PASANG\n\n" : "MODE SIMULASI (belum mengubah file)\n\n";

$total = 0; $dilewati = 0; $dipasang = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $path = $f->getPathname();
    if (substr($path, -4) !== '.php') continue;
    $baris = file($path);
    if ($baris === false) continue;
    $rel = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    $aksi = array();

    foreach ($baris as $i => $b) {
        if (!preg_match($POLA, $b, $m, PREG_OFFSET_CAPTURE)) continue;
        if (preg_match('/<h[1-6]\b|<title\b/i', $b)) continue;      // judul halaman, bukan menu

        $dekat = false;                                              // harus di dalam item menu
        for ($j = $i; $j >= 0 && $j >= $i - 4; $j--) {
            if (preg_match('/<a\b|<li\b|<button\b|href=/i', $baris[$j])) { $dekat = true; break; }
        }
        if (!$dekat) continue;

        $total++;
        $pos = $m[0][1];
        $pre = substr($b, 0, $pos);

        $sudah = ada_ikon_di($pre);
        if (!$sudah) {
            for ($j = $i - 1; $j >= 0 && $j >= $i - 4; $j--) {
                if (preg_match('/<\/a>|<\/li>|<\/button>/i', $baris[$j])) break;
                if (ada_ikon_di($baris[$j])) { $sudah = true; break; }
                if (preg_match('/<a\b|<li\b|<button\b/i', $baris[$j])) break;
            }
        }
        if ($sudah) { $dilewati++; echo "LEWAT (sudah ada ikon): $rel:" . ($i + 1) . "\n"; continue; }

        // cari ikon menu lain sebagai pembanding
        $sib = null;
        for ($j = $i - 1; $j >= 0 && $j >= $i - 80; $j--) { $sib = ikon_di_baris($baris[$j]); if ($sib) break; }
        if (!$sib) { foreach ($baris as $bb) { $sib = ikon_di_baris($bb); if ($sib) break; } }
        $tipe = $sib ? $sib[0] : null;
        $ikon = buat_ikon($tipe, $sib ? $sib[1] : '');

        $indent = preg_match('/^(\s*)/', $b, $im) ? $im[1] : '';
        $eol = (substr($b, -2) === "\r\n") ? "\r\n" : "\n";
        if (trim($pre) === '' || preg_match('/^\s*<(span|p|div|small|label)\b[^>]*>\s*$/i', $pre)) {
            $aksi[] = array('baris', $i, $indent . $ikon . $eol);
        } else {
            $aksi[] = array('inline', $i, substr($b, 0, $pos) . $ikon . ' ' . substr($b, $pos));
        }
        echo "PASANG: $rel:" . ($i + 1) . "  [" . ($tipe ? $tipe : 'svg-bawaan') . "]\n   $ikon\n";
    }

    if ($aksi && $simpan) {
        copy($path, $path . '.bak-ikon');
        for ($k = count($aksi) - 1; $k >= 0; $k--) {
            list($jenis, $idx, $isi) = $aksi[$k];
            if ($jenis === 'baris') array_splice($baris, $idx, 0, array($isi));
            else $baris[$idx] = $isi;
        }
        file_put_contents($path, implode('', $baris));
        $dipasang += count($aksi);
    }
}

echo "\nMenu ditemukan: $total | Sudah ada ikon: $dilewati | "
   . ($simpan ? "Dipasang: $dipasang" : "Akan dipasang: " . ($total - $dilewati)) . "\n";

if ($total === 0) {
    echo "\nTidak ada menu di file tampilan. Lokasi lain yang memuat tulisan itu:\n";
}
foreach (array('app', 'config', 'routes', 'resources/js', 'public/js') as $d) {
    if (!is_dir("$root/$d")) continue;
    $it2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$d", FilesystemIterator::SKIP_DOTS));
    foreach ($it2 as $f2) {
        if (!$f2->isFile() || !preg_match('/\.(php|js|json)$/i', $f2->getFilename())) continue;
        foreach (file($f2->getPathname()) as $no => $bb) {
            if (preg_match($POLA, $bb)) {
                echo ' ' . str_replace($root . DIRECTORY_SEPARATOR, '', $f2->getPathname()) . ':' . ($no + 1) . '  ' . trim($bb) . "\n";
            }
        }
    }
}