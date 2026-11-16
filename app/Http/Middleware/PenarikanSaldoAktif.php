<?php

namespace App\Http\Middleware;

use Closure;

/**
 * Menutup semua halaman Tarik Saldo selama WITHDRAWAL_ENABLED=false.
 * Kode penarikan tidak dihapus; cukup ubah WITHDRAWAL_ENABLED=true di .env untuk mengaktifkannya lagi.
 */
class PenarikanSaldoAktif
{
    public function handle($request, Closure $next)
    {
        if (!config('withdrawal.enabled')) {
            return redirect()->route('loginDashboard')->with('error', 'Penarikan saldo sementara dinonaktifkan.');
        }
        return $next($request);
    }
}
