# Fitur Baru — Panduan Singkat

## Pasang (Laragon, PHP 7.4)
1. Salin semua file paket ini ke folder proyek (timpa file yang sama).
2. Laragon Terminal: `setup-laragon.bat` — menjalankan migrasi (hanya menambah tabel/kolom) dan membuat akun BSI.
   Manual: `php artisan migrate --path=database/migrations/pembayaran --force`, lalu
   `php artisan migrate --path=database/migrations/fitur_baru --force`, lalu `php artisan bsi:buat-akun`.
3. Akun BSI berikutnya dibuat oleh **Super Admin** lewat menu **Akun BSI** (tambah, ubah, nonaktifkan; password boleh dikosongkan saat edit bila tidak diganti). Akun berstatus *Inactive* otomatis dikeluarkan dan tidak bisa masuk.
   Akun BSI pertama dibuat lewat perintah di atas. **Catat password BSI yang tampil sekali** (login di `/admin/login`, email `bsi@bankimam.local`). Reset: `php artisan bsi:buat-akun --reset`.
4. Production: `APP_DEBUG=false`.

## Peran
| Peran | Akses |
|---|---|
| BSI | Harga Acuan, Riwayat harga, Pengumuman ke BSU, Dashboard (ringkasan) |
| BSU | Harga & Margin, Setoran Massal, Buku Kas, Kategori Kas, Jadwal, Dashboard (ringkasan) |
| Admin/Super Admin | Harga Acuan; lihat Kas & Jadwal; Dashboard (ringkasan). **Akun BSI: hanya Super Admin** |
| Nasabah | Dashboard (ringkasan, harga terbaru, jadwal), Katalog (cari, ⓘ, istilah), Riwayat dengan filter periode |

## Alur harga
BSI mengisi **Harga Acuan** → BSU buka **Harga & Margin** → *Aktifkan* jenis (atau *Hubungkan* sampah lama; harga nasabah tetap).
Harga beli nasabah = Harga BSI − margin (Rp/kg atau %). Saat BSI mengubah harga: harga nasabah semua BSU yang "Ikuti BSI" dihitung ulang, dicatat di riwayat, dan BSU dapat notifikasi (lonceng CMS).

## Pengumuman BSI ke BSU
BSI membuka **Pengumuman ke BSU** (atau tombol *Kirim Pengumuman ke BSU* di Harga Acuan BSI) → isi judul dan pesan → pilih **satu atau banyak BSU** (cari nama, saring kecamatan, pilih semua yang tampil) → opsional *Lampirkan daftar harga acuan terbaru* (dicatat apa adanya saat dikirim) → Kirim.
BSU menerima notifikasi di lonceng dan membuka menu **Pengumuman dari BSI**; BSI dapat melihat siapa saja yang sudah membaca. BSU hanya dapat membuka pengumuman yang ditujukan kepadanya.


## Aksesibilitas & Tampilan
- Nasabah: *Pengaturan → Tampilan*. CMS (semua peran): menu *Pengaturan Tampilan*. Tombol **Aa** di pojok kanan atas = pintasan ke halaman ini.
- **Ukuran huruf:** Kecil, Sedang, Besar. **Ukuran ikon:** Kecil, Sedang, Besar (diatur terpisah dari huruf).
- **Kontras warna:** Standar, Kontras Tinggi, Mode Gelap, atau *Atur sendiri* (warna tulisan, latar, tombol, tautan; ada indikator rasio kontras).
- Tersimpan di peramban/perangkat tiap pengguna; tombol *Kembalikan ke standar* tersedia. Pilihan lama "Sangat Besar" otomatis menjadi "Besar" dan "Kuning di Hitam" menjadi "Mode Gelap".

## Pengumuman BSI ke BSU
BSI membuka **Pengumuman ke BSU** (atau tombol *Kirim Pengumuman ke BSU* di Harga Acuan BSI) → isi judul dan pesan → pilih **satu atau banyak BSU** (cari nama, saring kecamatan, pilih semua yang tampil) → opsional *Lampirkan daftar harga acuan terbaru* (dicatat apa adanya saat dikirim) → Kirim.
BSU menerima notifikasi di lonceng dan membuka menu **Pengumuman dari BSI**; BSI dapat melihat siapa saja yang sudah membaca. BSU hanya dapat membuka pengumuman yang ditujukan kepadanya.


## Aksesibilitas & Tampilan
- Nasabah: *Pengaturan → Aksesibilitas & Tampilan*. CMS (semua peran): menu *Pengaturan Tampilan*. Tombol **Aa** di pojok kanan atas = pintasan ke sana.
- **Ukuran huruf** dan **ukuran ikon** dipisah, masing-masing: Kecil, Sedang, Besar, Sangat Besar.
- **Warna:** Standar, Kontras Tinggi, Mode Gelap, Kuning di Hitam, atau **Warna sendiri** (tulisan, latar, tombol, tautan) dengan indikator rasio kontras.
- Tersimpan di peramban/perangkat tiap pengguna; tombol *Kembalikan ke standar* tersedia.

## Harga acuan: isi cepat & impor file (BSI)
Di **Harga Acuan BSI** tersedia tiga cara yang memakai **kategori dan jenis sampah yang sudah ada** (daftar BSI + yang sudah dipakai BSU):
- **Isi Harga Cepat** — satu tabel berisi semua jenis per kategori. Isi kolom harga; kolom *Acuan di BSU* menampilkan kisaran dan rata-rata harga di BSU, tombol *Pakai rata-rata* mengisinya otomatis. Kosong = dilewati. Jenis baru bisa ditambah di bawah tabel.
- **Impor dari File** — *Unduh template* (Excel berisi seluruh kategori/jenis yang ada), isi kolom Harga, unggah (.xlsx/.xls/.csv), lihat **pratinjau** (baru/diubah/sama/dilewati/error), lalu *Terapkan*. Tidak ada yang tersimpan sebelum diterapkan. Harga boleh `3500`, `3.500`, atau `Rp 3.500`; kosong = lewati; `0` = gratis.
- **Form tambah/ubah** — kolom Kategori dan Jenis memberi saran dari data yang sudah ada beserta acuan harga di BSU.
Harga yang berubah diteruskan ke BSU yang terhubung; **setiap BSU hanya menerima satu notifikasi ringkasan**.
Setelah impor, BSI bisa langsung lanjut ke *Kirim pengumuman*.

## Lampiran pada Pengumuman ke BSU
Saat mengirim pengumuman, BSI dapat melampirkan **hingga 5 file** (PDF, Excel, CSV, Word, JPG, PNG; maks 5 MB per file) dan/atau mencentang **Lampirkan daftar harga sebagai file Excel** (dibuat otomatis dari harga acuan aktif). BSU mengunduh dari menu *Pengumuman dari BSI*; hanya BSU tujuan yang bisa mengunduh. File disimpan privat di `storage/app/bsi_lampiran`.
Jika unggahan gagal karena batas server, naikkan `upload_max_filesize` dan `post_max_size` di php.ini (Laragon: Menu → PHP → php.ini).

## Catatan
- Menu **Kamus Jenis Sampah**, **Istilah Kamus**, **Manajemen BSU**, dan **Penarikan Saldo** (admin) serta **Tarik Saldo** (nasabah) sudah dihapus. Halaman **Ringkasan** kini menjadi halaman awal (dashboard) admin. Jalankan `php artisan migrate --path=database/migrations/fitur_baru --force` agar menu lama hilang dari sidebar.
- Setoran massal memakai rumus & pencatatan saldo yang sama dengan input lama (poin, saldo, ledger).
- "Nomor identitas" nasabah = ID nasabah / no. HP (belum ada kolom NIK di database).
- Buku kas: *Catat penjualan ke BSI* mengimpor Transaksi BSI lama; isi "Mulai tanggal" bila tidak ingin saldo awal ikut besar.
