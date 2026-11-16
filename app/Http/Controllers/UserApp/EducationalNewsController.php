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