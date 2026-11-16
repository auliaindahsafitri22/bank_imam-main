@extends('layout.main')
@section('title', 'Tampilan | EcoBuana')

@section('content')
<header style="background: linear-gradient(150deg, #00c96e 0%, #005c2e 100%); padding-bottom: 18px;">
    <div class="px-4 pt-4 pb-2 d-flex align-items-center gap-3">
        <a href="{{ route('showProfile') }}" class="text-white text-decoration-none" aria-label="Kembali">
            <div style="width:38px; height:38px; background:rgba(255,255,255,0.2); border-radius:12px; display:flex; align-items:center; justify-content:center;">
                <i class="bi bi-arrow-left" style="font-size:18px;"></i>
            </div>
        </a>
        <div class="flex-grow-1">
            <p class="m-0 text-white" style="font-size:11px; opacity:0.85;">Pengaturan</p>
            <h5 class="m-0 text-white fw-bold" style="font-size:17px;">Tampilan</h5>
        </div>
        <i class="bi bi-universal-access text-white" style="font-size:22px;"></i>
    </div>
</header>

<main class="main-container px-3 pt-3 pb-5">
    <p style="font-size:13px; color:#4b5563;">Atur <b>ukuran huruf</b>, <b>ukuran ikon</b> (kecil, sedang, besar), dan <b>kontras warna</b> agar nyaman dibaca. Perubahan langsung berlaku.</p>
    <div id="a11y-full"></div>
</main>
<script>window.A11Y_NO_WIDGET = true; A11Y.panel(document.getElementById('a11y-full'));</script>
@endsection
