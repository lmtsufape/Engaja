<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MatrizPresencaCapaSheet implements FromView, WithColumnWidths, WithStyles, WithTitle
{
    protected $evento;

    protected $atividadesPorMunicipio;

    public function __construct($evento, $atividadesPorMunicipio)
    {
        $this->evento = $evento;
        $this->atividadesPorMunicipio = $atividadesPorMunicipio;
    }

    public function view(): View
    {
        $municipiosResumo = [];

        foreach ($this->atividadesPorMunicipio as $municipioId => $atividades) {
            $primeiraAtividade = $atividades->first();
            $municipioNome = $primeiraAtividade->abrangencia_nacional
                ? 'Brasil'
                : ($primeiraAtividade->municipio?->nome_com_estado ?? 'Sem Município');

            $inscritosUnicos = collect();
            foreach ($atividades as $atividade) {
                foreach ($atividade->inscricoes as $inscricao) {
                    $inscritosUnicos->push($inscricao->participante_id);
                }
            }

            $municipiosResumo[] = [
                'nome' => $municipioNome,
                'total_momentos' => $atividades->count(),
                'total_participantes_unicos' => $inscritosUnicos->unique()->count(),
            ];
        }

        // por nome do municipio
        usort($municipiosResumo, fn ($a, $b) => strcmp($a['nome'], $b['nome']));

        return view('exports.matriz_presenca_capa', [
            'evento' => $this->evento,
            'municipiosResumo' => $municipiosResumo,
        ]);
    }

    public function title(): string
    {
        return 'Resumo';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            2 => ['font' => ['italic' => true]],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 45,
            'B' => 25,
            'C' => 25,
        ];
    }
}
