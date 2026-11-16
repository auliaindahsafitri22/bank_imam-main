<?php
use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$simpan = in_array('simpan', $argv, true);   // tanpa "simpan" = simulasi saja
$paksa  = in_array('paksa', $argv, true);    // "paksa" = timpa SEMUA deskripsi yang sudah terisi

$tabel = 'sampah';
$cn    = 'name';
$cd    = 'deskripsi';

// Nama yang deskripsinya selalu diperbarui walau sudah terisi
$timpaKunci = ['botol beling'];

// Kata kunci (huruf kecil) => deskripsi. Yang lebih panjang dicocokkan lebih dulu.
$kamus = [
  'bodong a'        => 'botol plastik bening tanpa label dan tutup',
  'bodong b'        => 'botol plastik berwarna tanpa label dan tutup',
  'bodong'          => 'botol plastik tanpa label dan tutup',
  'tutup galon'     => 'tutup botol galon air mineral berbahan plastik, dipisahkan dari botolnya',
  'tutup botol'     => 'tutup botol plastik yang sudah dilepas dari botolnya',
  'gelas bening'    => 'gelas plastik bening bekas minuman, sudah dikosongkan dan tidak berlapis',
  'gelas'           => 'gelas plastik bekas minuman, sudah dikosongkan dan dibilas',
  'botol pet'       => 'botol plastik bekas minuman kemasan (PET), kosong dan bersih',
  'botol bening'    => 'botol plastik bening bekas minuman, kosong dan bersih',
  'botol warna'     => 'botol plastik berwarna bekas minuman atau pembersih, kosong dan bersih',
  'botol plastik'   => 'botol plastik bekas kemasan, kosong dan bersih',
  'botol kaca'      => 'botol kaca bekas minuman atau sirup, utuh dan sudah dibilas',
  'botol beling'    => 'botol kaca bekas minuman, sirup, atau kecap, utuh dan sudah dibilas',
  'beling'          => 'pecahan kaca atau botol pecah, dikumpulkan dalam wadah yang aman',
  'le mineral'      => 'botol air mineral merek Le Minerale bekas, kosong dan bersih',
  'galon'           => 'galon air mineral bekas, kosong dan bersih',
  'ember'           => 'ember atau baskom plastik bekas dalam kondisi apa pun',
  'kresek'          => 'kantong plastik kresek bersih dan kering',
  'plastik campur'  => 'berbagai jenis plastik yang tercampur, bersih dan kering',
  'plastik'         => 'plastik bekas yang bersih dan kering',
  'ps bening'       => 'plastik jenis PS (polistirena) bening, seperti wadah makanan atau tutup gelas, bersih dan kering',
  'sachet'          => 'kemasan saset bekas (kopi, sampo, dll.), kering dan dikumpulkan terpisah',
  'styrofoam'       => 'kemasan atau wadah styrofoam bekas, bersih dan tidak berminyak',
  'akrilik'         => 'lembaran atau potongan akrilik bekas, seperti papan nama atau kotak display',
  'paralon'         => 'pipa paralon (PVC) bekas, bersih dari sisa semen atau lem',
  'kardus'          => 'kardus atau karton bekas, kering dan dilipat/ditumpuk rapi',
  'hvs'             => 'kertas putih bekas (HVS), dari kantor atau sekolah, tanpa lemak dan tidak basah',
  'koran'           => 'koran bekas, kering dan diikat rapi',
  'majalah'         => 'majalah atau tabloid bekas, kering',
  'buku'            => 'buku atau kertas tulis bekas, kering',
  'duplex'          => 'kertas karton tipis bekas kemasan (duplex), misalnya kotak makanan atau sepatu',
  'kertas'          => 'kertas bekas yang kering dan tidak berminyak',
  'minyak jelantah' => 'minyak goreng bekas pakai, disaring dan ditampung dalam botol tertutup',
  'jelantah'        => 'minyak goreng bekas pakai, disaring dan ditampung dalam botol tertutup',
  'kaleng'          => 'kaleng bekas minuman atau makanan, kosong dan dibilas',
  'almuniun'        => 'panci atau peralatan masak berbahan aluminium bekas, tanpa gagang plastik atau besi',
  'alumunium'       => 'barang berbahan aluminium bekas, seperti kaleng minuman, panci, atau tutup',
  'aluminium'       => 'barang berbahan aluminium bekas, seperti kaleng minuman, panci, atau tutup',
  'besi'            => 'besi bekas atau rongsokan, seperti paku, pipa, dan pagar',
  'tembaga'         => 'logam tembaga bekas, seperti kabel kupas atau pipa',
  'kuningan'        => 'logam kuningan bekas, seperti keran atau selongsong',
  'seng'            => 'lembaran seng atau atap bekas',
  'kabel'           => 'kabel listrik bekas, boleh masih terbungkus atau sudah dikupas',
  'elektronik'      => 'barang elektronik bekas yang sudah rusak, seperti charger dan komponen',
  'aki'             => 'aki atau baterai kendaraan bekas, simpan dengan hati-hati',
  'karung'          => 'karung plastik atau goni bekas, bersih dan kering',
  'kain'            => 'kain atau pakaian bekas yang bersih dan kering',
  'stainless steel' => 'barang berbahan baja tahan karat (stainless steel) bekas, seperti panci, sendok, atau wadah, tidak berkarat dan tidak ada sisa makanan',
  'stainless'       => 'barang berbahan baja tahan karat (stainless) bekas, seperti panci, sendok, atau wadah, bersih dan tidak ada sisa makanan',
  'stainles'        => 'barang berbahan baja tahan karat (stainless) bekas, seperti panci, sendok, atau wadah, bersih dan tidak ada sisa makanan',
  'stenlis'         => 'barang berbahan baja tahan karat (stainless) bekas, seperti panci, sendok, atau wadah, bersih dan tidak ada sisa makanan',
];
uksort($kamus, fn($a, $b) => strlen($b) <=> strlen($a));

function cocok(string $nama, string $kunci): bool {
    if (strlen($kunci) <= 5) { // kata pendek: harus kata utuh
        return (bool) preg_match('/(?<![a-z])' . preg_quote($kunci, '/') . '(?![a-z])/u', $nama);
    }
    return str_contains($nama, $kunci);
}
function kosong(string $s): bool {
    return in_array(mb_strtolower(trim($s)), ['', '-', '--', '.', 'null', 'n/a', 'belum ada', 'belum ada penjelasan'], true);
}

echo "Tabel: $tabel (nama: $cn, deskripsi: $cd)\n";
echo $simpan ? "MODE SIMPAN\n\n" : "MODE SIMULASI (belum menyimpan apa pun)\n\n";

$diisi = $lewat = 0; $hasil = []; $belum = [];
foreach (DB::table($tabel)->get() as $row) {
    $nama = trim((string) $row->$cn);
    $low  = mb_strtolower($nama);
    $skrg = trim((string) $row->$cd);

    $desk = null;
    foreach ($kamus as $kunci => $isi) { if (cocok($low, $kunci)) { $desk = $isi; break; } }

    $wajib = false;
    foreach ($timpaKunci as $k) { if (str_contains($low, $k)) { $wajib = true; break; } }

    if (!kosong($skrg) && !$paksa && !$wajib) { $lewat++; continue; }
    if ($desk === null) { $belum[$nama] = ($belum[$nama] ?? 0) + 1; continue; }
    if ($skrg === $desk) { $lewat++; continue; }

    $kunciHasil = $nama . '|' . $desk . '|' . (kosong($skrg) ? '' : $skrg);
    $hasil[$kunciHasil] = ($hasil[$kunciHasil] ?? 0) + 1;

    if ($simpan) {
        $q = DB::table($tabel);
        isset($row->id) ? $q->where('id', $row->id) : $q->where($cn, $row->$cn);
        $q->update([$cd => $desk]);
    }
    $diisi++;
}

foreach ($hasil as $k => $jml) {
    [$nama, $desk, $lama] = explode('|', $k, 3);
    echo ($lama === '' ? '✔' : '↻') . " $nama (x$jml)  →  $desk\n";
    if ($lama !== '') echo "     (lama: $lama)\n";
}
echo "\nDiisi/diperbarui: $diisi | Dilewati: $lewat | Belum dikenali: " . count($belum) . " jenis\n";
if ($belum) {
    echo "Nama yang belum dikenali:\n";
    foreach ($belum as $n => $j) echo " - $n (x$j)\n";
}