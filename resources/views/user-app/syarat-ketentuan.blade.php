@extends('layout.main')

@section('title', 'Syarat dan Ketentuan | EcoBuana')
@section('content')

<div style="background: linear-gradient(135deg, #00c96e 0%, #005c2e 100%); padding: 1.5rem 1rem 3.5rem;">
    <div class="d-flex align-items-center gap-3">
        <a href="/settings" class="d-flex align-items-center justify-content-center text-decoration-none"
           style="width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:12px;">
            <i class="bi bi-arrow-left text-white" style="font-size:18px;"></i>
        </a>
        <h5 class="m-0 text-white fw-bold" style="font-size:18px;">Syarat dan Ketentuan</h5>
    </div>
</div>

@php
    $sections = [
        ['1. Ketentuan Umum', 'Dengan membuat akun dan menggunakan aplikasi ini, kamu menyatakan telah membaca dan menyetujui seluruh syarat dan ketentuan di halaman ini. Aplikasi digunakan untuk mencatat setoran sampah, tabungan, poin, dan penarikan saldo nasabah Bank Sampah Unit (BSU).'],
        ['2. Akun Nasabah', 'Kamu wajib memberikan data yang benar dan lengkap saat mendaftar, serta menjaga kerahasiaan kata sandi. Segera hubungi pengelola BSU jika akunmu digunakan tanpa izin.'],
        ['3. Setoran Sampah', 'Setoran dicatat oleh petugas BSU berdasarkan jenis dan berat sampah yang ditimbang. Sampah harus sesuai kategori yang berlaku. Petugas berhak menolak sampah yang tidak sesuai atau terlalu kotor.'],
        ['4. Harga, Saldo, dan Poin', 'Nilai setoran dihitung dari harga sampah per kilogram yang ditetapkan BSU dan masuk ke saldo tabunganmu. Harga sampah, jumlah poin, dan daftar reward dapat berubah sesuai kebijakan BSU.'],
        ['5. Penarikan Saldo', 'Penarikan saldo dilakukan melalui menu Tarik Saldo dan diproses oleh pengelola BSU. Jumlah yang ditarik tidak boleh melebihi saldo yang tersedia. Waktu proses dan batas minimum mengikuti kebijakan BSU.'],
        ['6. Penukaran Poin', 'Poin yang sudah ditukarkan dengan reward tidak dapat dikembalikan atau diuangkan. Ketersediaan reward bergantung pada stok di BSU.'],
        ['7. Privasi Data', 'Data pribadimu, seperti nama, email, dan foto profil, hanya digunakan untuk keperluan layanan bank sampah dan tidak dijual kepada pihak ketiga.'],
        ['8. Larangan', 'Kamu dilarang memberikan data palsu, menyalahgunakan akun orang lain, atau mencoba mengubah data transaksi. Pelanggaran dapat berakibat pada pembekuan atau penghapusan akun.'],
        ['9. Perubahan Ketentuan', 'Syarat dan ketentuan dapat diperbarui sewaktu-waktu. Penggunaan aplikasi setelah perubahan berarti kamu menyetujui ketentuan yang baru.'],
        ['10. Bantuan', 'Jika ada pertanyaan atau kendala, silakan hubungi pengelola BSU tempat kamu terdaftar.'],
    ];
@endphp

<main class="px-3 pb-5" style="margin-top:-30px; position:relative; z-index:10;">
    @foreach ($sections as $s)
        <div class="px-4 py-3 mb-2 rounded-3 bg-white shadow-sm">
            <p class="fw-bold mb-1" style="font-size:14px; color:#00a056;">{{ $s[0] }}</p>
            <p class="mb-0" style="font-size:13px; color:#4b5563; line-height:1.6;">{{ $s[1] }}</p>
        </div>
    @endforeach

    <p class="text-center mt-3 mb-0" style="font-size:11px; color:#9ca3af;">Terakhir diperbarui: 8 November 2026</p>

    <div class="mt-4 pb-5">
        <a href="/settings" class="btn w-100 py-3 fw-bold rounded-3"
           style="background:#e6f7ef; color:#00a056; font-size:14px; border:1.5px solid #b7e8d0;">
            Kembali ke Profil
        </a>
    </div>
</main>
@endsection