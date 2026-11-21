<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * CrudBooster tidak memeriksa kolom cms_users.status saat login. Middleware ini memblokir akun CMS
 * yang berstatus "Inactive" (BSU, BSI, dll.): sesi dihentikan dan pengguna diarahkan ke halaman login.
 * Hanya nilai "Inactive" yang diblokir, sehingga akun lama dengan status lain tidak ikut terkunci.
 */
class BlokirAkunCmsNonaktif
{
    public function handle($request, Closure $next)
    {
        $id = $request->session()->get('admin_id');
        if ($id) {
            // Kolom dihapus_at baru ada setelah migrasi hapus lunak; sebelum itu hanya status yang diperiksa.
            static $adaKolom = null;
            if ($adaKolom === null) {
                $adaKolom = \Illuminate\Support\Facades\Schema::hasColumn('cms_users', 'dihapus_at');
            }
            $akun = DB::table('cms_users')->where('id', $id)->first($adaKolom ? ['status', 'dihapus_at'] : ['status']);
            $status = $akun ? $akun->status : null;
            $dihapus = $akun && !empty($akun->dihapus_at);
            if ($dihapus || ($status !== null && strcasecmp(trim((string) $status), 'Inactive') === 0)) {
                $request->session()->flush();
                return redirect(url(config('crudbooster.ADMIN_PATH', 'admin') . '/login'))
                    ->with(['message' => 'Akun Anda dinonaktifkan. Silakan hubungi administrator.', 'message_type' => 'danger']);
            }
        }
        return $next($request);
    }
}
