<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Aba genérica do export do painel gerencial: título, cabeçalho e linhas.
 */
class PainelGerencialSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    /**
     * @param  array<int, string>  $headings
     * @param  array<int, array<int, mixed>>  $rows
     */
    public function __construct(
        private string $title,
        private array $headings,
        private array $rows,
    ) {}

    public function title(): string
    {
        return $this->title;
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return $this->headings;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return $this->rows;
    }
}
