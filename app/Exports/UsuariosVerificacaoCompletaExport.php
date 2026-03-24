<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class UsuariosVerificacaoCompletaExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(private Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows->map(function ($row) {
            return [
                'nome'             => $row['nome'] ?? null,
                'email'            => $row['email'] ?? null,
                'cpf'              => $row['cpf'] ?? null,
                'telefone'         => $row['telefone'] ?? null,
                'municipio'        => $row['municipio'] ?? null,
                'tipo_organizacao' => $row['tipo_organizacao'] ?? null,
                'organizacao'      => $row['escola_unidade'] ?? null,
                'tag'              => $row['tag'] ?? null,
                'ja_existe'        => $row['ja_existe'] ?? 'Nao',
                'duplicado_planilha' => $row['duplicado_planilha'] ?? 'Nao',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Nome',
            'Email',
            'CPF',
            'Telefone',
            'Municipio',
            'Tipo de Organizacao',
            'Organizacao',
            'Tag',
            'Ja existe no Engaja',
            'Duplicado na planilha',
        ];
    }
}
