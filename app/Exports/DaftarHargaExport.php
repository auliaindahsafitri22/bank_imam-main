<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Daftar harga acuan BSI sebagai file Excel (template impor atau lampiran pengumuman). */
class DaftarHargaExport implements FromArray, WithHeadings, ShouldAutoSize, WithTitle, WithStrictNullComparison
{
    private $judul;
    private $heads;
    private $rows;

    public function __construct($judul, array $heads, array $rows)
    {
        $this->judul = $judul;
        $this->heads = $heads;
        $this->rows = $rows;
    }

    public function title(): string
    {
        return $this->judul;
    }

    public function headings(): array
    {
        return $this->heads;
    }

    public function array(): array
    {
        return $this->rows;
    }
}
