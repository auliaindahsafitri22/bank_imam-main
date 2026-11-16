<?php
require __DIR__ . '/vendor/autoload.php';

$simpan = in_array('simpan', $argv, true);   // tanpa "simpan" = simulasi saja
$hanya  = null;                              // opsional: file=bagian-nama-file
foreach ($argv as $a) { if (strpos($a, 'file=') === 0) $hanya = strtolower(str_replace('\\', '/', substr($a, 5))); }

$kolJudul = array('judul', 'title');
$kolIsi   = array('isi', 'konten', 'pesan', 'deskripsi', 'keterangan', 'body', 'content');

$base = str_replace('\\', '/', __DIR__ . '/resources/views');
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));

$ada = 0;
foreach ($it as $f) {
    if (!$f->isFile()) continue;
    $path = str_replace('\\', '/', $f->getPathname());
    if (substr($path, -10) !== '.blade.php') continue;

    $isi = file_get_contents($path);
    if (stripos($isi, 'tombol-wa') !== false) continue;                       // sudah dipasang
    if (stripos($path, 'pengumuman') === false && stripos($isi, 'pengumuman') === false) continue;
    if ($hanya !== null && strpos(strtolower($path), $hanya) === false) continue;
    if (!preg_match('/\$(\w+)->(' . implode('|', $kolJudul) . ')\b/', $isi, $m)) continue;

    $var = $m[1]; $kJudul = $m[2];
    $baris = file($path);                                                      // baris + akhir barisnya
    $eol = (strpos($isi, "\r\n") !== false) ? "\r\n" : "\n";

    $idxJudul = null; $idxIsi = null; $kIsi = null;
    foreach ($baris as $i => $b) {
        if ($idxJudul === null && preg_match('/\$' . $var . '->' . $kJudul . '\b/', $b)) $idxJudul = $i;
        if ($idxJudul !== null && $idxIsi === null && preg_match('/\$' . $var . '->(' . implode('|', $kolIsi) . ')\b/', $b, $mi)) {
            $idxIsi = $i; $kIsi = $mi[1];
        }
    }
    if ($idxJudul === null) continue;
    $sisip = ($idxIsi !== null) ? $idxIsi : $idxJudul;

    $bagianIsi = ($kIsi !== null)
        ? " . chr(10) . chr(10) . \\Illuminate\\Support\\Str::limit(strip_tags((string) \$__V__->__I__), 800)"
        : "";
    $tombol = '{{-- tombol-wa --}}<a href="https://wa.me/?text={{ rawurlencode(\'*\' . $__V__->__T__ . \'*\''
        . $bagianIsi
        . " . chr(10) . chr(10) . '_EcoBuana - Bank Sampah Digital_') }}\""
        . ' target="_blank" rel="noopener" style="display:inline-block;margin-top:8px;background:#16a34a;color:#fff;padding:7px 14px;border-radius:10px;text-decoration:none;font-size:13px;font-weight:600;">Bagikan ke WhatsApp</a>';
    $tombol = str_replace(array('__V__', '__T__', '__I__'), array($var, $kJudul, (string) $kIsi), $tombol);

    $ada++;
    echo ($simpan ? 'DIPASANG' : 'KANDIDAT') . ': ' . str_replace(str_replace('\\', '/', __DIR__) . '/', '', $path) . "\n";
    echo "  variabel: \$$var | judul: $kJudul | isi: " . ($kIsi ?: '(tidak ketemu)') . "\n";
    echo '  tombol disisipkan setelah baris ' . ($sisip + 1) . ': ' . trim($baris[$sisip]) . "\n\n";

    if ($simpan) {
        copy($path, $path . '.bak-wa');
        array_splice($baris, $sisip + 1, 0, array($tombol . $eol));
        file_put_contents($path, implode('', $baris));
    }
}

if ($ada === 0) {
    echo "Tidak ada halaman pengumuman yang cocok. Kirim isi blade pengumuman ke saya.\n";
} elseif (!$simpan) {
    echo "Ini simulasi, belum ada file yang diubah.\n";
    echo "Pasang: php pasang-wa.php simpan   (atau hanya satu file: php pasang-wa.php simpan file=admin)\n";
}