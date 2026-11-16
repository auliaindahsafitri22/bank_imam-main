@extends('layout.main')

@section('title', $news->judul . ' | EcoBuana')

@section('content')
@php
    $rawImg = $news->gambar ?? '';
    if (filter_var($rawImg, FILTER_VALIDATE_URL)) {
        $imgUrl = $rawImg;
    } elseif (!empty($rawImg)) {
        $imgUrl = asset($rawImg);
    } else {
        $imgUrl = asset('images/waste-management.jpg');
    }
@endphp

<header style="background: linear-gradient(150deg, #00c96e 0%, #005c2e 100%); position:relative; overflow:hidden; padding-bottom: 34px;">
    <div class="px-4 pt-4 pb-2 d-flex align-items-center gap-3" style="position:relative; z-index:1;">
        <a href="{{ route('edukasi.index') }}" class="text-white text-decoration-none">
            <div style="width:38px; height:38px; background:rgba(255,255,255,0.2); border-radius:12px; display:flex; align-items:center; justify-content:center;">
                <i class="bi bi-arrow-left" style="font-size:18px;"></i>
            </div>
        </a>
        <div class="flex-grow-1">
            <p class="m-0 text-white" style="font-size:11px; opacity:0.85;">Ruang Edukasi</p>
            <h5 class="m-0 text-white fw-bold" style="font-size:18px;">Detail Berita</h5>
        </div>
    </div>
    <svg viewBox="0 0 428 35" xmlns="http://www.w3.org/2000/svg" style="position:absolute; bottom:-1px; left:0; width:100%;">
        <path d="M0,18 C100,35 328,0 428,18 L428,35 L0,35 Z" fill="#f4f6f8"/>
    </svg>
</header>

<main style="background:#f4f6f8; min-height: calc(100vh - 120px); padding-bottom: 95px;">
    <div class="container-body px-3 pt-2">
        <article class="bg-white rounded-4 overflow-hidden" style="box-shadow: 0 3px 14px rgba(0,0,0,0.06); border: 1px solid #f0f0f0;">
            <img src="{{ $imgUrl }}" alt="{{ $news->judul }}"
                 onerror="this.onerror=null; this.src='{{ asset('images/waste-management.jpg') }}';"
                 style="width:100%; max-height:240px; object-fit:cover; display:block;">
            <div class="p-3">
                <div class="d-flex align-items-center gap-2 mb-2" style="font-size:11px;">
                    <span class="badge rounded-pill" style="background:#e6f7ef; color:#00a056; font-weight:600;">{{ $news->domain }}</span>
                    <span style="color:#94a3b8;">{{ $tanggal }}</span>
                </div>
                <h5 class="fw-bold mb-3" style="font-size:17px; line-height:1.4; color:#1a1a2e;">{{ $news->judul }}</h5>

                @forelse($paragraf as $p)
                    <p style="font-size:13.5px; line-height:1.75; color:#334155; margin-bottom:12px;">{{ $p }}</p>
                @empty
                    <p style="font-size:13px; color:#64748b;">Isi berita belum tersedia.</p>
                @endforelse

                @if($news->external)
                    <a href="{{ $news->link }}" target="_blank" rel="noopener noreferrer"
                       class="d-inline-block mt-2 px-3 py-2 rounded-pill text-decoration-none fw-bold"
                       style="background:#00a056; color:#fff; font-size:12px;">
                        Baca selengkapnya di sumber <i class="bi bi-box-arrow-up-right ms-1"></i>
                    </a>
                @endif
            </div>
        </article>

        <a href="{{ route('edukasi.index') }}" class="d-block text-center text-decoration-none mt-3" style="font-size:12px; color:#008744; font-weight:600;">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Ruang Edukasi
        </a>
    </div>
</main>
@endsection