<?php namespace App\Http\Controllers;

use CRUDBooster;
use DB;
use App\Http\Controllers\Concerns\CekPeran;
use App\Services\WilayahBsiService;

/**
 * Pantau BSI (khusus Super Admin): BSI mana memegang kelurahan mana, BSU mana yang tercakup, dari BSI mana
 * harga acuan tiap BSU berasal, serta kelurahan/BSU yang belum punya BSI. Juga alat satu kali untuk
 * memindahkan daftar "Harga Bersama" (lama) ke BSI dan menyalin daftar harga antar BSI.
 */
class AdminPantauBsiController extends \crocodicstudio\crudbooster\controllers\CBController
{
    use CekPeran;

    public function cbInit()
    {
        $this->title_field = "name";
        $this->global_privilege = true;
        $this->button_add = false;
        $this->button_edit = false;
        $this->button_delete = false;
        $this->button_detail = false;
        $this->button_show = false;
        $this->button_filter = false;
        $this->button_import = false;
        $this->button_export = false;
        $this->table = "cms_users";
        $this->col = [];
        $this->form = [];
    }

    public function getIndex()
    {
        $this->wajibPeran([1]);
        $this->cbLoader();

        $d = app(WilayahBsiService::class)->pantau();

        $q = trim((string) request('q'));
        $bsiF = (string) request('bsi', '');
        $kondF = (string) request('kondisi', '');

        $bsu = array_values(array_filter($d['bsu'], function ($u) use ($q, $bsiF, $kondF) {
            if ($q !== '') {
                $teks = implode(' ', [$u->name, $u->kelurahan, $u->kelurahan_teks, $u->kecamatan, $u->bsi_nama]);
                if (mb_stripos($teks, $q) === false) {
                    return false;
                }
            }
            if ($bsiF === 'none' && $u->bsi_id !== null) {
                return false;
            }
            if ($bsiF !== '' && $bsiF !== 'none' && (int) $bsiF !== (int) $u->bsi_id) {
                return false;
            }
            return $kondF === '' || $u->kondisi === $kondF;
        }));

        return view('admin.pantau_bsi', [
            'page_title' => 'Pantau BSI',
            'bsi' => $d['bsi'], 'bsu' => $bsu, 'kelurahanTanpaBsi' => $d['kelurahanTanpaBsi'],
            'ringkas' => $d['ringkas'], 'bersama' => $d['bersama'], 'bsiDihapus' => $d['bsiDihapus'],
            'q' => $q, 'bsiF' => $bsiF, 'kondF' => $kondF, 'totalBsu' => count($d['bsu']),
        ]);
    }

    /** Menyerahkan daftar Harga Bersama (lama) menjadi milik satu BSI. */
    public function postSerahkan()
    {
        $this->wajibPeran([1]);
        try {
            $n = app(WilayahBsiService::class)->serahkanHargaBersama((int) request('bsi_id'));
        } catch (\InvalidArgumentException $e) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), $e->getMessage(), 'warning');
        }
        CRUDBooster::insertLog('Harga Bersama (' . $n . ' jenis) diserahkan ke BSI #' . (int) request('bsi_id'));
        CRUDBooster::redirect(CRUDBooster::mainpath(), $n . ' jenis harga kini milik BSI tersebut. Harga dan sambungan BSU tidak berubah.', 'success');
    }

    /** Menyalin daftar harga dari Harga Bersama / BSI lain ke satu BSI. */
    public function postSalin()
    {
        $this->wajibPeran([1]);
        $dari = request('dari') === 'bersama' ? null : (int) request('dari');
        try {
            $r = app(WilayahBsiService::class)->salinHarga($dari, (int) request('bsi_id'), (int) CRUDBooster::myId());
        } catch (\InvalidArgumentException $e) {
            CRUDBooster::redirect(CRUDBooster::mainpath(), $e->getMessage(), 'warning');
        }
        CRUDBooster::insertLog('Salin daftar harga ' . ($dari === null ? 'Harga Bersama' : 'BSI #' . $dari) . ' ke BSI #' . (int) request('bsi_id') . ': ' . json_encode($r));
        CRUDBooster::redirect(CRUDBooster::mainpath(), $r['disalin'] . ' jenis disalin, ' . $r['dilewati'] . ' dilewati (nama sudah ada). BSU belum dipindah otomatis: BSU menghubungkan ulang di menu Harga & Margin.', 'success');
    }
}
