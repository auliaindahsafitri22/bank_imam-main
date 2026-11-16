<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/** Pembaca mentah file Excel (tanpa judul kolom otomatis; rumus dihitung). */
class HargaImporReader implements ToArray, WithCalculatedFormulas
{
    public function array(array $array)
    {
        // Tidak dipakai: hasil diambil lewat Excel::toArray().
    }
}
