@extends('layout.main')

@section('title', 'Tentang Kami | EcoBuana')
@section('content')

<div style="background: linear-gradient(135deg, #00c96e 0%, #005c2e 100%); padding: 1.5rem 1rem 4rem;">
    <div class="d-flex align-items-center gap-3">
        <a href="/settings" class="d-flex align-items-center justify-content-center text-decoration-none"
           style="width:36px; height:36px; background:rgba(255,255,255,0.2); border-radius:12px;">
            <i class="bi bi-arrow-left text-white" style="font-size:18px;"></i>
        </a>
        <h5 class="m-0 text-white fw-bold" style="font-size:18px;">Tentang Kami</h5>
    </div>
</div>

<main class="px-3 pb-5" style="margin-top:-40px; position:relative; z-index:10;">

    <div class="px-4 py-4 rounded-4 bg-white shadow-sm text-center">
        <div style="width:64px; height:64px; background:#e6f7ef; border-radius:20px; display:flex; align-items:center; justify-content:center; margin:0 auto 12px;">
            <i class="bi bi-recycle" style="font-size:30px; color:#00a056;"></i>
        </div>
        <h5 class="fw-bold mb-1" style="font-size:18px; color:#1a1a2e;">EcoBuana</h5>
        <p class="mb-3" style="font-size:12px; color:#00a056; font-weight:600;">Setor sampah, tabung rupiah</p>
        <p class="mb-0" style="font-size:13px; color:#4b5563; line-height:1.7;">
            EcoBuana adalah aplikasi bank sampah digital yang membantu nasabah mencatat setoran sampah,
            memantau saldo dan poin, menarik tabungan, serta menukarkan poin dengan reward menarik.
            Semua tercatat rapi, transparan, dan bisa dipantau kapan saja dari ponselmu.
        </p>
    </div>

    <div class="px-4 py-3 rounded-3 bg-white shadow-sm mt-3">
        <h6 class="fw-bold mb-2" style="font-size:15px;">Tujuan Kami</h6>
        <p class="mb-0" style="font-size:13px; color:#4b5563; line-height:1.7;">
            Mengajak masyarakat memilah dan menyetor sampah secara rutin, sehingga lingkungan lebih bersih
            dan setiap sampah punya nilai ekonomi bagi nasabah.
        </p>
    </div>

    <div class="px-4 py-3 rounded-3 bg-white shadow-sm mt-3">
        <h6 class="fw-bold mb-3" style="font-size:15px;">Cara Kerja</h6>
        <p class="fw-semibold mb-0" style="font-size:13px;">1. Pilah dan setor sampah</p>
        <p class="mb-3" style="font-size:12px; color:#6b7280;">Pilah sampah sesuai kategori, lalu setorkan ke petugas Bank Sampah Unit.</p>
        <p class="fw-semibold mb-0" style="font-size:13px;">2. Ditimbang dan dicatat</p>
        <p class="mb-3" style="font-size:12px; color:#6b7280;">Petugas menimbang dan mencatat setoranmu. Saldo dan poin bertambah otomatis.</p>
        <p class="fw-semibold mb-0" style="font-size:13px;">3. Tarik saldo atau tukar poin</p>
        <p class="mb-0" style="font-size:12px; color:#6b7280;">Tarik tabunganmu atau tukarkan poin dengan reward yang tersedia.</p>
    </div>

    <p class="text-center mt-4 mb-0" style="font-size:11px; color:#9ca3af;">&copy; {{ date('Y') }} EcoBuana. Semua hak dilindungi.</p>

    <div class="mt-4 pb-5">
        <a href="/settings" class="btn w-100 py-3 fw-bold rounded-3"
           style="background:#e6f7ef; color:#00a056; font-size:14px; border:1.5px solid #b7e8d0;">
            Kembali ke Profil
        </a>
    </div>
</main>
@endsection