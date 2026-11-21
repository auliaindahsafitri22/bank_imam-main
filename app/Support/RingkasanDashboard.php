<?php

namespace App\Support;

use App\Services\KasService;
use CRUDBooster;
use Illuminate\Support\Facades\DB;

/**
 * Data & tampilan ringkasan (BSU / pusat) yang dipakai di halaman awal dashboard
 * dan di halaman /admin/ringkasan_bsu.
 */
class RingkasanDashboard
{
    /** @return array|null [nama view parsial, data] atau null bila peran tidak berhak. */
    public static function bagian()
    {
        $peran = (int) CRUDBooster::myPrivilegeId();
        $idBsi = (int) DB::table('cms_privileges')->where('name', 'BSI')->value('id');
        $boleh = in_array($peran, [1, 2, 4], true) || ($idBsi > 0 && $peran === $idBsi);
        if (!$boleh) {
            return null;
        }

        $perubahan = DB::table('bsi_harga_riwayat as r')->join('bsi_harga as b', 'b.id', '=', 'r.bsi_harga_id')
            ->whereNull('b.dihapus_at')->whereNotNull('r.harga_lama')->orderByDesc('r.id')->limit(6)->get(['b.nama', 'r.harga_lama', 'r.harga_baru', 'r.tanggal']);

        if ($peran === 4) {
            $id = (int) CRUDBooster::myId();
            $kas = app(KasService::class);
            $bulan = $kas->laporan($id, 'bulanan', date('Y-m'));
            $awal = date('Y-m-01');
            $set = DB::table('transactions')->where('admin_id', $id)->whereDate('created_at', '>=', $awal)
                ->selectRaw('COUNT(*) n, COALESCE(SUM(total_weight),0) kg, COALESCE(SUM(total_income),0) rp')->first();
            return ['admin.partials.ringkasan_bsu_isi', [
                'saldoKas' => $kas->saldo($id), 'bulan' => $bulan, 'set' => $set,
                'nasabah' => DB::table('users')->where('cms_user_id', $id)->count(),
                'saldoNasabah' => (int) DB::table('saldo')->join('users', 'users.id', '=', 'saldo.user_id')
                    ->where('users.cms_user_id', $id)->sum('saldo.total_saldo'),
                'tukar' => DB::table('tukar_saldo')->where('admin_id', $id)->whereDate('created_at', '>=', $awal)->count(),
                'jadwal' => DB::table('jadwal_pengumpulan')->where('admin_id', $id)->where('tanggal', '>=', date('Y-m-d'))
                    ->orderBy('tanggal')->orderBy('jam_mulai')->limit(4)->get(),
                'perubahan' => $perubahan,
                'hargaTerhubung' => DB::table('sampah')->where('admin_id', $id)->whereNotNull('bsi_harga_id')->count(),
            ]];
        }

        return ['admin.partials.ringkasan_pusat_isi', [
            'perubahan' => $perubahan,
            'jmlJenis' => (function () use ($peran, $idBsi) {
                $q = DB::table('bsi_harga')->where('is_active', 1)->whereNull('dihapus_at');
                if ($idBsi > 0 && $peran === $idBsi) { $q->where('bsi_id', (int) CRUDBooster::myId()); }   // BSI hanya menghitung daftarnya sendiri
                return $q->count();
            })(),
            'jmlBsu' => DB::table('cms_users')->where('id_cms_privileges', 4)->count(),
            'jmlNasabah' => DB::table('users')->count(),
            'terhubung' => DB::table('sampah')->whereNotNull('bsi_harga_id')->distinct()->count('admin_id'),
        ]];
    }

    /** HTML ringkasan untuk peran saat ini ('' bila tidak ada / terjadi galat). */
    public static function html()
    {
        try {
            $b = static::bagian();
            return $b ? view($b[0], $b[1])->render() : '';
        } catch (\Throwable $e) {
            \Log::error('Ringkasan dashboard gagal: ' . $e->getMessage());
            return '';
        }
    }
}
