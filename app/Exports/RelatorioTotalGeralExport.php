<?php

namespace App\Exports;

use App\Http\Controllers\RelatorioQuantitativoController;
use App\Models\Evento;
use App\Models\Regiao;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Events\BeforeSheet;

class RelatorioTotalGeralExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
{
    public function __construct(private Request $request) {}

    public function registerEvents(): array
    {
        return [
            BeforeSheet::class => function (BeforeSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $filtros = $this->getFiltersSummary();
                $row = 1;

                if (count($filtros) > 0) {
                    $sheet->setCellValue('A'.$row, 'Filtros Aplicados:');
                    $sheet->getStyle('A'.$row)->getFont()->setBold(true);
                    $row++;

                    foreach ($filtros as $filtro) {
                        $sheet->setCellValue('A'.$row, $filtro);
                        $row++;
                    }

                    // Adicionar linha em branco
                    $row++;

                    // Inserir 3 linhas vazias antes dos dados
                    $sheet->insertNewRowBefore($row, 3);
                }
            },
        ];
    }

    public function getFiltersSummary(): array
    {
        $filtros = [];

        if ($this->request->integer('evento_id')) {
            $evento = Evento::find($this->request->integer('evento_id'));
            if ($evento) {
                $filtros[] = 'Ação: '.$evento->nome;
            }
        }

        if ($this->request->integer('regiao_id')) {
            $regiao = Regiao::find($this->request->integer('regiao_id'));
            if ($regiao) {
                $filtros[] = 'Região: '.$regiao->nome;
            }
        }

        // O Total Geral não aplica filtro de município (é uma quebra por município);
        // não listamos "Município" para não anunciar um filtro que não foi usado.

        if ($this->request->get('de') || $this->request->get('ate')) {
            $de = $this->request->get('de') ? Carbon::parse($this->request->get('de'))->format('d/m/Y') : '';
            $ate = $this->request->get('ate') ? Carbon::parse($this->request->get('ate'))->format('d/m/Y') : '';
            $intervalo = ($de && $ate) ? "$de até $ate" : ($de ? "a partir de $de" : "até $ate");
            $filtros[] = 'Período: '.$intervalo;
        }

        return $filtros;
    }

    public function collection(): Collection
    {
        $controller = new RelatorioQuantitativoController;
        $totalGeral = $this->callBuildTotalGeralData($controller);

        return $totalGeral->filter(fn ($r) => ! isset($r['_is_total']));
    }

    public function headings(): array
    {
        $dimensoes = $this->request->input('dimensoes', []);

        $cols = ['Região', 'Município', 'Previstos', 'Participantes distintos'];

        if (in_array('cpf', $dimensoes)) {
            array_push($cols, 'Com CPF', 'Sem CPF', '% Com CPF');
        }
        if (in_array('raca_cor', $dimensoes)) {
            array_push($cols, 'Branca', '% Branca', 'Parda', '% Parda', 'Preta', '% Preta', 'Amarela', '% Amarela', 'Indígena', '% Indígena');
        }
        if (in_array('genero', $dimensoes)) {
            array_push($cols, 'Mulheres', '% Mulheres', 'Homens', '% Homens', 'Outros/NB', '% Outros/NB');
        }
        if (in_array('pcd', $dimensoes)) {
            array_push($cols, 'PcD', '% PcD');
        }
        if (in_array('certificados', $dimensoes)) {
            array_push($cols, 'Certificados', '% Certificados');
        }
        if (in_array('tag', $dimensoes)) {
            array_push($cols, 'Rede de Ensino', '% Rede Ensino', 'Mov. Social', '% Mov. Social');
        }

        return $cols;
    }

    public function map($row): array
    {
        $dimensoes = $this->request->input('dimensoes', []);
        $tp = $row['metricas']['total_presentes'];
        $fmtPct = fn ($v) => $v > 0 ? number_format($v, 1, ',', '.').'%' : '—';

        $data = [
            $row['regiao'] ?? '—',
            $row['municipio_nome'] ?? '—',
            $row['previstos'] ?: '—',
            $tp ?: '—',
        ];

        if (in_array('cpf', $dimensoes)) {
            array_push($data,
                $row['metricas']['cpf']['com'],
                $row['metricas']['cpf']['sem'],
                $tp > 0 ? $fmtPct($row['metricas']['cpf']['pct']) : '—',
            );
        }
        if (in_array('raca_cor', $dimensoes)) {
            array_push($data,
                $row['metricas']['raca_cor']['branca'], $fmtPct($row['metricas']['raca_cor']['pct_branca']),
                $row['metricas']['raca_cor']['parda'], $fmtPct($row['metricas']['raca_cor']['pct_parda']),
                $row['metricas']['raca_cor']['preta'], $fmtPct($row['metricas']['raca_cor']['pct_preta']),
                $row['metricas']['raca_cor']['amarela'], $fmtPct($row['metricas']['raca_cor']['pct_amarela']),
                $row['metricas']['raca_cor']['indigena'], $fmtPct($row['metricas']['raca_cor']['pct_indigena']),
            );
        }
        if (in_array('genero', $dimensoes)) {
            array_push($data,
                $row['metricas']['genero']['mulheres'], $fmtPct($row['metricas']['genero']['pct_mulheres']),
                $row['metricas']['genero']['homens'], $fmtPct($row['metricas']['genero']['pct_homens']),
                $row['metricas']['genero']['outros'], $fmtPct($row['metricas']['genero']['pct_outros']),
            );
        }
        if (in_array('pcd', $dimensoes)) {
            array_push($data, $row['metricas']['pcd']['n'], $fmtPct($row['metricas']['pcd']['pct']));
        }
        if (in_array('certificados', $dimensoes)) {
            array_push($data, $row['metricas']['certificados']['n'], $fmtPct($row['metricas']['certificados']['pct']));
        }
        if (in_array('tag', $dimensoes)) {
            array_push($data,
                $row['metricas']['tag']['rede_ensino'], $fmtPct($row['metricas']['tag']['pct_rede_ensino']),
                $row['metricas']['tag']['movimento_social'], $fmtPct($row['metricas']['tag']['pct_movimento_social']),
            );
        }

        return $data;
    }

    private function callBuildTotalGeralData($controller): Collection
    {
        $reflection = new \ReflectionMethod(RelatorioQuantitativoController::class, 'buildTotalGeralData');
        $reflection->setAccessible(true);

        return $reflection->invoke($controller, $this->request);
    }
}
