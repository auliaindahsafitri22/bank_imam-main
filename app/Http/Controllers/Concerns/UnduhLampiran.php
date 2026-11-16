<?php

namespace App\Http\Controllers\Concerns;

use CRUDBooster;
use Illuminate\Support\Facades\Storage;

/** Pengiriman file lampiran pengumuman dari penyimpanan privat (hanya setelah otorisasi oleh controller). */
trait UnduhLampiran
{
    protected function kirimLampiran($f)
    {
        $disk = Storage::disk('local');
        if (!$f || strpos($f->path, '..') !== false || !$disk->exists($f->path)) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), 'File tidak ditemukan.', 'warning');
        }
        return response()->download($disk->path($f->path), $f->nama_asli, [
            'Content-Type' => 'application/octet-stream',      // selalu diunduh, tidak dirender peramban
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    protected function ukuranBaca($b)
    {
        return $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB' : max(1, (int) round($b / 1024)) . ' KB';
    }
}
