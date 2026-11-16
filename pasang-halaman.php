<?php
$dir = __DIR__ . '/resources/views/user-app';
if (!is_dir($dir)) { exit("Folder tidak ditemukan: $dir\n"); }

function halaman(string $judul, string $isi): string {
    return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{$judul} - EcoBuana</title>
  <style>
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;background:#f3f4f6;color:#1f2937;line-height:1.65}
    .bar{position:sticky;top:0;background:#fff;border-bottom:1px solid #e5e7eb;padding:12px 16px;display:flex;align-items:center;gap:12px;z-index:10}
    .bar a{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:#f3f4f6;color:#111;text-decoration:none;font-size:18px}
    .bar h1{font-size:17px;margin:0}
    .wrap{max-width:720px;margin:0 auto;padding:16px}
    .kartu{background:#fff;border-radius:16px;padding:20px 18px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
    h2{font-size:15px;margin:22px 0 6px;color:#047857}
    h2:first-child{margin-top:0}
    p{margin:0 0 10px;font-size:14px}
    ul,ol{margin:0 0 10px;padding-left:20px;font-size:14px}
    li{margin-bottom:4px}
    .catatan{font-size:12px;color:#6b7280;margin-top:18px}
  </style>
</head>
<body>
  <div class="bar"><a href="javascript:history.back()" aria-label="Kembali">&larr;</a><h1>{$judul}</h1></div>
  <div class="wrap"><div class="kartu">
{$isi}
  </div></div>
</body>
</html>
HTML;
}

$tentang = <<<'HTML'
    <h2>Siapa Kami</h2>
    <p>EcoBuana adalah platform digital bank sampah yang membantu masyarakat mengelola sampah dengan cara yang mudah, transparan, dan bernilai. Kami percaya bahwa sampah yang dipilah dengan benar bukan lagi beban, melainkan sumber daya yang bisa memberi manfaat bagi lingkungan sekaligus bagi dompet Anda.</p>

    <h2>Apa yang Kami Lakukan</h2>
    <p>Lewat EcoBuana, Anda dapat melihat daftar jenis sampah beserta harga per kilogram, memahami cara memilahnya, dan mencatat setoran dengan rapi. Semua riwayat dan saldo tersimpan di satu tempat sehingga mudah dipantau kapan saja, tanpa perlu mencatat manual.</p>

    <h2>Cara Kerjanya</h2>
    <ol>
      <li><strong>Pilah</strong> sampah di rumah sesuai jenisnya, bersih dan kering.</li>
      <li><strong>Setor</strong> ke bank sampah untuk ditimbang oleh petugas.</li>
      <li><strong>Catat</strong> hasilnya otomatis dalam akun Anda.</li>
      <li><strong>Manfaatkan</strong> saldo sesuai ketentuan yang berlaku di bank sampah.</li>
    </ol>

    <h2>Visi Kami</h2>
    <p>Mewujudkan lingkungan yang bersih dan lestari melalui kebiasaan memilah sampah yang menjadi bagian dari keseharian masyarakat.</p>

    <h2>Misi Kami</h2>
    <ul>
      <li>Mengajak lebih banyak orang memilah dan menyetor sampah.</li>
      <li>Memberikan informasi harga dan jenis sampah yang jelas dan mudah dipahami.</li>
      <li>Menyediakan pencatatan yang transparan, adil, dan dapat dipercaya.</li>
      <li>Mengurangi sampah yang berakhir di tempat pembuangan akhir.</li>
    </ul>

    <h2>Nilai yang Kami Pegang</h2>
    <p>Transparan dalam setiap timbangan dan saldo, peduli pada lingkungan, dan mudah digunakan oleh siapa saja.</p>

    <p class="catatan">Terima kasih telah menjadi bagian dari gerakan sampah terpilah, lingkungan lestari.</p>
HTML;

$syarat = <<<'HTML'
    <p>Dengan menggunakan aplikasi EcoBuana, Anda dianggap telah membaca, memahami, dan menyetujui syarat dan ketentuan berikut. Mohon dibaca dengan saksama.</p>

    <h2>1. Ketentuan Umum</h2>
    <p>EcoBuana adalah platform digital untuk mencatat dan mengelola setoran sampah di bank sampah. Layanan ini digunakan untuk tujuan yang baik dan sesuai dengan hukum yang berlaku.</p>

    <h2>2. Akun Pengguna</h2>
    <ul>
      <li>Data yang Anda berikan harus benar, lengkap, dan terbaru.</li>
      <li>Anda bertanggung jawab menjaga kerahasiaan akun dan kata sandi.</li>
      <li>Segala aktivitas yang terjadi melalui akun Anda menjadi tanggung jawab Anda.</li>
    </ul>

    <h2>3. Penyetoran Sampah</h2>
    <ul>
      <li>Sampah yang disetor harus sudah dipilah sesuai jenisnya, dalam keadaan bersih dan kering.</li>
      <li>Penentuan jenis dan hasil penimbangan oleh petugas bank sampah menjadi acuan pencatatan.</li>
      <li>Sampah yang tidak sesuai kriteria dapat ditolak atau dihargai berbeda.</li>
    </ul>

    <h2>4. Harga dan Saldo</h2>
    <ul>
      <li>Harga per kilogram pada aplikasi dapat berubah sewaktu-waktu mengikuti kondisi pasar.</li>
      <li>Saldo dihitung berdasarkan berat dan harga yang berlaku pada saat setoran dicatat.</li>
      <li>Penarikan atau penggunaan saldo mengikuti ketentuan yang ditetapkan bank sampah.</li>
    </ul>

    <h2>5. Larangan</h2>
    <p>Anda tidak diperkenankan menyetor sampah berbahaya dan beracun (B3), sampah medis, bahan mudah meledak atau terbakar, serta sampah organik basah yang berbau. Anda juga dilarang memberikan data palsu atau menyalahgunakan aplikasi untuk merugikan pihak lain.</p>

    <h2>6. Privasi dan Data Pribadi</h2>
    <p>Kami menjaga data pribadi Anda dan hanya menggunakannya untuk keperluan layanan, seperti pencatatan setoran dan saldo. Data Anda tidak dijual kepada pihak lain.</p>

    <h2>7. Perubahan Layanan dan Ketentuan</h2>
    <p>Kami dapat memperbarui fitur, harga, maupun syarat dan ketentuan ini dari waktu ke waktu. Penggunaan aplikasi setelah perubahan berarti Anda menyetujui ketentuan yang baru.</p>

    <h2>8. Pertanyaan dan Bantuan</h2>
    <p>Jika ada hal yang kurang jelas, silakan hubungi petugas bank sampah terdekat.</p>
HTML;

file_put_contents("$dir/tentang-kami.blade.php", halaman('Tentang Kami', $tentang));
file_put_contents("$dir/syarat-ketentuan.blade.php", halaman('Syarat dan Ketentuan', $syarat));
echo "View dibuat: tentang-kami.blade.php dan syarat-ketentuan.blade.php\n";

// Pasang rute
$rf = __DIR__ . '/routes/web.php';
$r  = file_get_contents($rf);
if (strpos($r, "'syarat-ketentuan'") === false) {
    copy($rf, $rf . '.bak-halaman');
    $r .= "\n\nRoute::view('/syarat-ketentuan', 'user-app.syarat-ketentuan')->name('syarat-ketentuan');\n"
        . "Route::view('/tentang-kami', 'user-app.tentang-kami')->name('tentang-kami');\n";
    file_put_contents($rf, $r);
    echo "Rute ditambahkan ke routes/web.php (cadangan: web.php.bak-halaman)\n";
} else {
    echo "Rute sudah ada, dilewati.\n";
}

// Cari file yang berisi tulisan menunya
echo "\nFile yang memuat tulisan menu:\n";
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/resources/views'));
foreach ($it as $file) {
    if (!$file->isFile() || !str_ends_with($file->getFilename(), '.php')) continue;
    foreach (file($file->getPathname()) as $no => $baris) {
        if (preg_match('/syarat\s*(dan|&)\s*ketentuan|tentang\s*kami/i', $baris) && !str_contains($file->getFilename(), 'syarat-ketentuan') && !str_contains($file->getFilename(), 'tentang-kami')) {
            echo ' ' . str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $file->getPathname()) . ':' . ($no + 1) . '  ' . trim($baris) . "\n";
        }
    }
}