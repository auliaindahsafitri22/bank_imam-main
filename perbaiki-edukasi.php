<?php
$root = __DIR__;
function cadang($f) { if (is_file($f) && !is_file($f . '.bak-edukasi')) copy($f, $f . '.bak-edukasi'); }

// ---------- 1) Migrasi kolom isi ----------
$mig = <<<'MIG'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsiToEducationTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('education', 'isi')) {
            Schema::table('education', function (Blueprint $table) {
                $table->longText('isi')->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('education', 'isi')) {
            Schema::table('education', function (Blueprint $table) {
                $table->dropColumn('isi');
            });
        }
    }
}
MIG;
file_put_contents($root . '/database/migrations/2026_11_16_170000_add_isi_to_education_table.php', $mig);
echo "OK  migrasi kolom isi dibuat\n";

// ---------- 2) Controller ----------
$ctrl = <<<'CTRL'
<?php

namespace App\Http\Controllers\UserApp;

use App\Models\EducationalNews;
use Carbon\Carbon;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;

class EducationalNewsController extends Controller
{
    public function index()
    {
        $educationalNews = EducationalNews::orderBy('updated_at', 'desc')->get();

        $educationalNews->transform(function ($news) {
            $this->siapkan($news);
            $news->time_elapsed = Carbon::parse($news->updated_at)->diffForHumans();
            return $news;
        });

        return view('user-app.edukasi', compact('educationalNews'));
    }

    public function show($id)
    {
        $news = EducationalNews::findOrFail($id);
        $this->siapkan($news);
        $tanggal = Carbon::parse($news->created_at)->locale('id')->translatedFormat('d F Y');

        // Teks setelah "Caption Suggestion" (draf caption media sosial) tidak ditampilkan
        $isi = trim(Str::before((string) $news->isi, 'Caption Suggestion'));
        // Teks lama tersimpan tanpa jeda paragraf ("...komunitas.Acara ini"), jadi dipisah di titik yang menempel ke huruf besar
        $isi = preg_replace('/([a-z\)”"])\.(?=[A-Z0-9])/u', '$1.' . "\n\n", $isi);
        $isi = preg_replace('/:(?=\d\.\s)/', ":\n\n", $isi);
        $paragraf = array_values(array_filter(array_map('trim', preg_split('/\R+/', $isi))));

        return view('user-app.edukasi-detail', compact('news', 'paragraf', 'tanggal'));
    }

    // Tentukan tujuan kartu: alamat web asli (buka sumber) atau halaman detail di aplikasi
    private function siapkan($news)
    {
        $u = trim((string) $news->url);
        $eksternal = $u !== ''
            && !preg_match('/\s/', $u)
            && strlen($u) <= 500
            && preg_match('~^(https?://|[\w-]+(\.[\w-]+)+)~i', $u);

        $news->external = $eksternal;
        if ($eksternal) {
            $news->link = preg_match('~^https?://~i', $u) ? $u : 'https://' . ltrim($u, '/');
            $news->domain = $this->getHostFromUrl($u);
        } else {
            $news->link = route('edukasi.show', $news->id);
            $news->domain = 'EcoBuana Edukasi';
        }
    }

    private function getHostFromUrl($url)
    {
        if (empty($url) || trim($url) === '') {
            return 'EcoBuana Edukasi';
        }
        if (!preg_match('~^(?:f|ht)tps?://~i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        $parsedUrl = parse_url($url);
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : 'EcoBuana Edukasi';
        if (empty($host) || $host === 'undefined') {
            return 'EcoBuana Edukasi';
        }
        return preg_replace('/^www\./', '', $host);
    }
}
CTRL;
$cf = $root . '/app/Http/Controllers/UserApp/EducationalNewsController.php';
cadang($cf);
file_put_contents($cf, $ctrl);
echo "OK  controller diperbarui (cadangan: EducationalNewsController.php.bak-edukasi)\n";

// ---------- 3) Halaman detail ----------
$view = <<<'VIEW'
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
VIEW;
file_put_contents($root . '/resources/views/user-app/edukasi-detail.blade.php', $view);
echo "OK  halaman detail dibuat (edukasi-detail.blade.php)\n";

// ---------- 4) Daftar berita: ganti tujuan kartu ----------
$lf = $root . '/resources/views/user-app/edukasi.blade.php';
$l  = file_get_contents($lf);
if (strpos($l, '$news->link') === false) {
    $n1 = 0; $n2 = 0;
    $l2 = preg_replace(
        '/<a\s+href="\{\{\s*\$news->url\s*\}\}"\s+target="_blank"\s+rel="noopener noreferrer"/',
        '<a href="{{ $news->link }}" {!! $news->external ? \'target="_blank" rel="noopener noreferrer"\' : \'\' !!}',
        $l, 1, $n1
    );
    $l2 = preg_replace(
        '/<i class="bi bi-box-arrow-up-right"/',
        '<i class="bi {{ $news->external ? \'bi-box-arrow-up-right\' : \'bi-chevron-right\' }}"',
        $l2, 1, $n2
    );
    if ($n1 === 0) {
        echo "GAGAL  baris <a href=\"{{ \$news->url }}\" ...> di edukasi.blade.php tidak ketemu, kirim baris itu ke saya\n";
    } else {
        cadang($lf);
        file_put_contents($lf, $l2);
        echo "OK  edukasi.blade.php diperbarui (link: $n1, ikon: $n2)\n";
    }
} else {
    echo "LEWAT  edukasi.blade.php sudah diperbarui\n";
}

// ---------- 5) Rute ----------
$rf = $root . '/routes/web.php';
$r  = file_get_contents($rf);
if (strpos($r, 'edukasi.show') === false) {
    $cari = "->name('edukasi.index');";
    if (strpos($r, $cari) === false) {
        echo "GAGAL  rute edukasi.index tidak ketemu di routes/web.php\n";
    } else {
        cadang($rf);
        $baru = $cari . "\nRoute::get('/edukasi/{id}', [EducationalNewsController::class, 'show'])->where('id', '[0-9]+')->name('edukasi.show');";
        file_put_contents($rf, str_replace($cari, $baru, $r));
        echo "OK  rute /edukasi/{id} ditambahkan\n";
    }
} else {
    echo "LEWAT  rute edukasi.show sudah ada\n";
}