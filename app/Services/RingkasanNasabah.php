<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** Data ringkasan dashboard nasabah: uang masuk/keluar bulan ini, jadwal pengumpulan. */
class RingkasanNasabah
{
    public static function untuk($user)
    {
        $awal = now()->startOfMonth();

        $masuk = DB::table('transactions')->where('user_id', $user->id)->where('created_at', '>=', $awal)
            ->selectRaw('COUNT(*) n, COALESCE(SUM(total_weight),0) kg, COALESCE(SUM(total_income),0) rp')->first();

        $keluar = (int) DB::table('saldo_mutasi')->where('user_id', $user->id)->where('type', 'debit')
            ->where('created_at', '>=', $awal)->sum('amount');

        $jadwal = DB::table('jadwal_pengumpulan')->where('admin_id', $user->cms_user_id)
            ->where('tanggal', '>=', now()->toDateString())->orderBy('tanggal')->orderBy('jam_mulai')->limit(3)
            ->get(['judul', 'tanggal', 'jam_mulai', 'jam_selesai', 'lokasi']);

        return ['masuk' => $masuk, 'keluar' => $keluar, 'jadwal' => $jadwal];
    }
}