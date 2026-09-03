<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MatrizPresencaMunicipioSheet implements FromView, WithTitle, WithEvents
{
    protected $municipioId;
    protected $atividades;
    protected $municipioNome;
    protected $totalColunas;

    public function __construct($municipioId, $atividades)
    {
        $this->municipioId = $municipioId;
        $this->atividades = $atividades; // já ordenadas cronologicamente pelo controller/export
        $this->municipioNome = $atividades->first()->municipio?->nome ?? 'Sem Município';
    }

    public function view(): View
    {
        //participantes unicos deste município
        $participantes = [];

        foreach ($this->atividades as $atividade) {
            $presentesIds = $atividade->presencas->pluck('inscricao_id');

            foreach ($atividade->inscricoes as $inscricao) {
                $partId = $inscricao->participante_id;

                if (!isset($participantes[$partId])) {
                    $participantes[$partId] = [
                        'nome' => $inscricao->participante?->user?->name ?? 'Participante #'.$partId,
                        'usuario_id' => $inscricao->participante?->user?->id ?? '-',
                        'cpf' => $inscricao->participante?->cpf ?? '-',
                        'email' => $inscricao->participante?->user?->email ?? '-',
                        'vinculo' => $inscricao->participante?->tag ?? '-',
                        'momentos' => [],
                        'presente_count' => 0,
                        'ausente_count' => 0,
                    ];
                }

                $isPresente = $presentesIds->contains($inscricao->id);

                if ($isPresente) {
                    $status = ($inscricao->ouvinte ?? false) ? 'Ouvinte' : 'Presente';
                    $participantes[$partId]['presente_count']++;
                } else {
                    $status = 'Ausente';
                    $participantes[$partId]['ausente_count']++;
                }

                $participantes[$partId]['momentos'][$atividade->id] = $status;
            }
        }

        //ordena alfabeticamente
        usort($participantes, fn($a, $b) => strcmp(strtolower($a['nome']), strtolower($b['nome'])));

        $this->totalColunas = 5 + $this->atividades->count() + 3; // 5 colunas de info + N momentos + 3 totalizadores

        return view('exports.matriz_presenca_municipio', [
            'atividades' => $this->atividades,
            'participantes' => $participantes,
            'municipioNome' => $this->municipioNome,
        ]);
    }

    public function title(): string
    {
        //limite de caracteres do nome da aba do Excel
        return substr($this->municipioNome, 0, 31);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();


                $sheet->freezePane('F3');

                $highestRow = $sheet->getHighestRow();
                $lastCol = $sheet->getHighestColumn();

                //coloca os negritos no cabeçalho, alinhamento, etc
                $sheet->getStyle('A2:' . $lastCol . '2')->getFont()->setBold(true);
                $sheet->getStyle('A2:' . $lastCol . '2')->getAlignment()->setWrapText(true);
                $sheet->getStyle('A2:' . $lastCol . '2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $sheet->getStyle('A2:' . $lastCol . '2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT); // Nome column

                //largura fixa das colunas
                $sheet->getColumnDimension('A')->setWidth(40);
                $sheet->getColumnDimension('B')->setWidth(12);
                $sheet->getColumnDimension('C')->setWidth(16);
                $sheet->getColumnDimension('D')->setWidth(35);
                $sheet->getColumnDimension('E')->setWidth(20);

                //carrega as colunas no loop
                $highestCol = $lastCol;
                $highestCol++;
                for ($col = 'F'; $col !== $highestCol; $col++) {
                    $sheet->getColumnDimension($col)->setWidth(16);
                    $sheet->getStyle($col . '3:' . $col . $highestRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }

                //cores de fundo para os status
                for ($row = 3; $row <= $highestRow; $row++) {
                    for ($col = 'F'; $col !== $highestCol; $col++) {
                        $cell = $sheet->getCell($col . $row);
                        $val = $cell->getValue();

                        if ($val === 'Presente') {
                            $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD4EDDA'); // green
                            $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FF155724');
                        } elseif ($val === 'Ausente') {
                            $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF3CD'); // yellow
                            $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FF856404');
                        } elseif ($val === 'Ouvinte') {
                            $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1ECF1'); // blue
                            $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FF0C5460');
                        } elseif ($val === 'Não Inscrito') {
                            $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE9ECEF'); // gray
                            $sheet->getStyle($col . $row)->getFont()->getColor()->setARGB('FF6C757D');
                        }
                    }
                }
            },
        ];
    }
}
