# Catatan Penggabungan bank_imam-main.zip + bank_imam-main_1.zip
Dasar: zip 1 (bank_imam-main.zip, lengkap dengan vendor, ledger saldo, penarikan, BSU, aksesibilitas).
Ditambahkan dari zip 2 (bank_imam-main_1.zip): modul Kelurahan (menggantikan Kecamatan), kolom/filter Kelurahan di
transaksi & laporan, form tambah transaksi multi-item, penyesuaian path aset ecobuana.
Dipertahankan dari zip 1: SaldoService pada AdminTransactionsController & AdminTukarSaldoController, serta halaman
register (BSU dikelompokkan per wilayah), Riwayat, Sampah, TukarSaldo user, routes, config, docker.
Dihapus (digantikan): AdminKecamatansController, Models/Kecamatan, filter_kecamatan.blade.php.
Dump SQL zip 2 disimpan sebagai database_zip2_kelurahan.sql (database.sql zip 1 tidak ditimpa).
Setelah deploy jalankan: php artisan migrate  (migrasi rename_kecamatan_to_kelurahan akan berjalan).

## Pembaruan v2 (14 Nov 2026)
1. Users Management (khusus Super Admin): kolom Total Transaksi (Rp), Total Berat (kg), dan Transaksi Terakhir per BSU.
   Sumber: tabel transactions. Akun non-BSU tampil "-"; BSU tanpa transaksi tampil 0 dan "Belum ada transaksi".
2. Data Sampah (akun BSU): tombol "Impor Katalog dari BSU Mercu Buana" -> pratinjau -> konfirmasi.
   Menyalin kategori, nama, harga/kg, poin/kg, deskripsi. Jenis yang namanya sudah ada dilewati. Gambar tidak disalin.
   Akun sumber: KATALOG_SUMBER_BSU_ID di .env (contoh: 5). Bila kosong, dicari otomatis BSU yang namanya memuat "mercu".
   File baru: app/Services/ImporKatalogBsuService.php, resources/views/admin/sampah_impor_katalog.blade.php.
   File diubah: AdminCmsUsersController, AdminSampahController, config/services.php, .env.example.

## Pembaruan v3 (14 Nov 2026) - menyatukan salinan kerja terbaru
Dari folder kerja (bank_imam-main__2_.zip) yang belum ada di v2:
1. Users Management (Super Admin): kolom Total Transaksi (Rp), Total Berat (kg), Transaksi Terakhir, Aktivitas
   (Aktif = ada transaksi <= 30 hari, Tidak aktif, Belum pernah). Menggantikan versi kolom v2. Memakai AdminCmsUsersController::bsuStat().
2. Data Nasabah: kolom yang sama (nasabahStat); pengelola hanya melihat transaksi di BSU-nya sendiri.
3. Perintah artisan sampah:import-mercu-buana (--bsu=ID / --all / --dry-run) untuk menyalin katalog Mercu Buana ke banyak BSU sekaligus.
4. Migrasi diperbaiki: 2026_11_08_014347 (picture -> LONGTEXT) kini benar; dihapus 2 migrasi kosong/duplikat yang membuat
   "php artisan migrate" gagal: 2023_04_09_173280_create_sampah_table.php dan fitur_baru/2026_11_07_234102_create_saldo_mutasi_table.php.
Tombol "Impor Katalog dari BSU Mercu Buana" (v2) tetap ada untuk impor per akun BSU.

## Pembaruan v4 (14 Nov 2026) - poin reviewer yang tidak menunggu keputusan
1. Penarikan saldo dinonaktifkan sementara (kode tetap utuh). Saklar: WITHDRAWAL_ENABLED (default sekarang false).
   Semua rute /tarik-saldo ditutup oleh middleware penarikan.aktif; menu Tarik Saldo di beranda dan di halaman Tukar Poin disembunyikan.
   Webhook Xendit (/api/webhooks/xendit/payout) tidak diubah supaya penarikan lama tetap bisa tuntas.
   Untuk mengaktifkan lagi: WITHDRAWAL_ENABLED=true lalu php artisan config:clear.
2. Beranda nasabah: menu cepat jadi grid 2x2 (Katalog, Riwayat, Tukar Saldo, Edukasi). Bila penarikan aktif, Tukar Saldo diganti Tarik Saldo.
3. Pengaturan Tampilan dipindah ke halaman Profil (kartu "Tampilan"); dihapus dari Pengaturan. Halaman tampilan tidak diubah.

Belum dikerjakan (menunggu keputusan): rumus harga BSI + margin,
rombak Laporan Keuangan (termasuk kolom Pemasukan di Laporan Kas Umum), mekanisme notifikasi pengumuman, panduan BSI & BSU.

## Pembaruan v5 (14 Nov 2026) - tampilan nasabah lebih ramah
1. Katalog sampah: tombol info (i) diperkecil; penjelasan kini berupa kartu kecil (maks. lebar 380px, tinggi maks. 40% layar, bisa digulir)
   dengan latar redup. Ditutup dengan ketuk di luar kartu, tombol silang, atau Esc. Ukurannya tetap terbatas walau ukuran huruf aksesibilitas "besar".
2. Beranda: menu cepat 2 baris x 2 kolom (kartu horizontal: ikon di kiri, teks di kanan, tinggi seragam).
3. Beranda: banner pengumuman memakai ikon info kecil, dibatasi 2 baris, ketuk untuk membuka seluruh teks.
4. Ringkasan Bulan Ini: keterangan dipersingkat dan tidak membungkus baris.

## Pembaruan v6 (14 Nov 2026) - Harga & Margin digabung ke Master Sampah
1. Menu "Harga & Margin" dihilangkan dari sidebar (migrasi 2026_11_14_000009). Fungsinya pindah ke menu Sampah (Master Sampah):
   tombol "Harga & Margin" (/admin/sampah/harga) dan "Riwayat Harga" (/admin/sampah/harga-riwayat). Logika di HargaService tidak berubah.
2. Daftar Master Sampah kini punya kolom Harga Acuan BSI, Margin, dan Sumber Harga (Ikut harga BSI / Terhubung (manual) / Manual).
3. Aturan edit harga di Master Sampah: sampah yang terhubung ke BSI dan ikut harga BSI tidak bisa lagi diubah manual
   (harga dihitung harga BSI - margin, tidak tertimpa diam-diam). Selain itu, perubahan manual dan sampah baru dicatat di Riwayat Harga.
4. Alamat lama /admin/harga_bsu dialihkan ke alamat baru, jadi bookmark dan notifikasi lama tetap jalan.
Belum diputuskan: mekanisme notifikasi pengumuman (saat ini hanya lonceng CMS; belum ada push/WhatsApp).

## Pembaruan v7 (16 Nov 2026) - register per Kelurahan + perapian
1. Halaman register: pilih Kelurahan dulu, lalu daftar BSU di kelurahan itu (dari zip 2). Hanya kelurahan yang punya BSU yang tampil;
   BSU yang kelurahannya belum diisi muncul di opsi "Wilayah belum diatur". Memperbaiki error `(string) $b->kelurahan`
   di register lama (relasi Kelurahan tidak bisa dijadikan teks). Validasi server tetap: BSU harus akun privilege 4.
   File: UserApp/RegisterController.php, resources/views/user-app/register.blade.php.
2. Transaksi BSI: berat desimal (2,5 atau 2.5) di form tambah/ubah standar (AdminTransactionsBsiController: validasi + hook_before_add/edit).
3. Perapian: salinan lama AdminTransactionsBsiController.php di folder utama, 3 backup SQL + bank_imam.sql, skrip patch/pasang sekali pakai,
   file hasil*.txt, semua *.bak*, laravel.log, dan 5 file modul Kalkulator (HAPUS-FILE-INI.txt) dipindah ke zip arsip terpisah.
   File kosong bernama aneh, .DS_Store, Thumbs.db dihapus.
