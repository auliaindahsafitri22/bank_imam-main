<?php
$root = __DIR__;
$target = array(
    'syarat\s+dan\s+ketentuan' => "{{ url('/syarat-ketentuan') }}",
    'tentang\s+kami'           => "{{ url('/tentang-kami') }}",
);

$diubah = array();
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/resources/views', FilesystemIterator::SKIP_DOTS)
);

foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $path = $f->getPathname();
    if (substr($path, -4) !== '.php') continue;
    if (preg_match('/(syarat-ketentuan|tentang-kami)\.blade\.php$/', $path)) continue;

    $isi  = file_get_contents($path);
    $baru = $isi;

    foreach ($target as $pola => $href) {
        // Kasus 1: tulisan sudah ada di dalam <a>, ganti href-nya
        $re1 = '/<a\b([^>]*)>((?:(?!<\/a>).)*?' . $pola . '(?:(?!<\/a>).)*?)<\/a>/is';
        if (preg_match($re1, $baru)) {
            $baru = preg_replace_callback($re1, function ($m) use ($href) {
                if (strpos($m[0], $href) !== false) return $m[0];   // sudah benar
                $attr = preg_replace(
                    '/\s(href|onclick|data-bs-toggle|data-bs-target|data-toggle|data-target)\s*=\s*("[^"]*"|\'[^\']*\')/i',
                    '', ' ' . $m[1]
                );
                return '<a' . $attr . ' href="' . $href . '">' . $m[2] . '</a>';
            }, $baru);
        } else {
            // Kasus 2: tulisan biasa di antara tag, bungkus dengan <a>
            $re2  = '/>(\s*)(' . $pola . ')(\s*)</i';
            $baru = preg_replace(
                $re2,
                '>$1<a href="' . $href . '" style="color:inherit;text-decoration:none;position:relative;z-index:5;">$2</a>$3<',
                $baru
            );
        }
    }

    if ($baru !== $isi) {
        copy($path, $path . '.bak-link');
        file_put_contents($path, $baru);
        $diubah[] = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
    }
}

if ($diubah) {
    echo "Berhasil dijadikan link di:\n";
    foreach ($diubah as $d) echo " - $d\n";
    echo "\nJalankan: php artisan view:clear lalu refresh browser (Ctrl+F5)\n";
    exit;
}

// Tidak ada yang berubah: cari tulisannya di seluruh project untuk dilaporkan
echo "Tidak ada file view yang cocok. Hasil pencarian di folder lain:\n";
foreach (array('app', 'resources', 'routes', 'public', 'lang') as $d) {
    if (!is_dir("$root/$d")) continue;
    $it2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$d", FilesystemIterator::SKIP_DOTS));
    foreach ($it2 as $f) {
        if (!$f->isFile() || preg_match('/\.(png|jpe?g|gif|svg|ico|woff2?|ttf|map|bak.*)$/i', $f->getFilename())) continue;
        foreach (file($f->getPathname()) as $no => $baris) {
            if (preg_match('/syarat\s*(dan|&)\s*ketentuan|tentang\s*kami/i', $baris)
                && !preg_match('/(syarat-ketentuan|tentang-kami)\.blade\.php$/', $f->getPathname())) {
                echo ' ' . str_replace($root . DIRECTORY_SEPARATOR, '', $f->getPathname()) . ':' . ($no + 1) . '  ' . trim($baris) . "\n";
            }
        }
    }
}