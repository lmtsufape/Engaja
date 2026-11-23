<?php

namespace App\Exports;

use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Municipio;
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

class RelatorioMomentoExport implements FromCollection, ShouldAutoSize, WithEvents, WithHeadings, WithMapping
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

        if ($this->request->integer('municipio_id')) {
            $municipio = Municipio::find($this->request->integer('municipio_id'));
            if ($municipio) {
                $filtros[] = 'Município: '.$municipio->nome;
            }
        }

        if (trim((string) $this->request->get('descricao', ''))) {
            $filtros[] = 'Momento: '.$this->request->get('descricao');
        }

        if ($this->request->get('de') || $this->request->get('ate')) {
            $de = $this->request->get('de') ? Carbon::parse($this->request->get('de'))->format('d/m/Y') : '';
            $ate = $this->request->get('ate') ? Carbon::parse($this->request->get('ate'))->format('d/m/Y') : '';
            $intervalo = ($de && $ate) ? "$de até $ate" : ($de ? "a partir de $de" : "até $ate");
            $filtros[] = 'Período: '.$intervalo;
        }

        if ($this->request->get('periodo')) {
            $periodos = ['manha' => 'Manhã', 'tarde' => 'Tarde', 'noite' => 'Noite'];
            $periodo_label = $periodos[$this->request->get('periodo')] ?? $this->request->get('periodo');
            $filtros[] = 'Período do dia: '.$periodo_label;
        }

        return $filtros;
    }

    public function collection(): Collection
    {
        $eventoId = $this->request->integer('evento_id');
        $descricao = trim((string) $this->request->get('descricao', ''));
        $municipioId = $this->request->integer('municipio_id');
        $regiaoId = $this->request->integer('regiao_id');
        $de = $this->request->date('de');
        $ate = $this->request->date('ate');
        $periodo = $this->request->get('periodo', '');

        $query = Atividade::query()
            ->select([
                'atividades.id',
                'atividades.evento_id',
                'atividades.municipio_id',
                'atividades.abrangencia_nacional',
                'atividades.descricao',
                'atividades.dia',
                'atividades.hora_inicio',
                'atividades.hora_fim',
                'atividades.publico_esperado',
                'eventos.nome as evento_nome',
                'municipios.nome as municipio_nome',
            ])
            ->leftJoin('eventos', 'eventos.id', '=', 'atividades.evento_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'atividades.municipio_id')
            ->leftJoin('estados', 'estados.id', '=', 'municipios.estado_id')
            ->leftJoin('regiaos', 'regiaos.id', '=', 'estados.regiao_id')
            ->withCount([
                'presencas as presentes_count' => fn ($q) => $q->where('status', 'presente'),
                'presencas as avaliacoes_count' => fn ($q) => $q->where('status', 'presente')
                    ->where('avaliacao_respondida', true),
            ])
            ->whereNull('atividades.deleted_at')
            ->whereNotNull('atividades.evento_id');

        $query->when($eventoId, fn ($q) => $q->where('atividades.evento_id', $eventoId));
        $query->when($municipioId, fn ($q) => $q->where('atividades.municipio_id', $municipioId));
        $query->when($regiaoId, fn ($q) => $q->where('regiaos.id', $regiaoId));
        $query->when($descricao, fn ($q) => $q->where('atividades.descricao', $descricao));

        $query->when($de && $ate, fn ($q) => $q->whereBetween('atividades.dia', [$de, $ate]));
        $query->when($de && ! $ate, fn ($q) => $q->where('atividades.dia', '>=', $de));
        $query->when(! $de && $ate, fn ($q) => $q->where('atividades.dia', '<=', $ate));

        $query->when($periodo === 'manha', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) < '12:00:00'"));
        $query->when($periodo === 'tarde', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) >= '12:00:00'")
            ->whereRaw("CAST(atividades.hora_inicio AS time) < '18:00:00'"));
        $query->when($periodo === 'noite', fn ($q) => $q->whereRaw("CAST(atividades.hora_inicio AS time) >= '18:00:00'"));

        $query->orderBy('eventos.nome', 'asc')
            ->orderBy('atividades.dia', 'asc')
            ->orderBy('atividades.id', 'asc');

        return $query->get();
    }

    public function headings(): array
    {
        return [
            'Ação',
            'Momento',
            'Município',
            'Data',
            'Período',
            'Qtd Previstas',
            'Qtd Presentes',
            '% Presentes',
            'Qtd Avaliações',
            '% Avaliações',
        ];
    }

    public function map($row): array
    {
        $horaStr = substr($row->hora_inicio ?? '', 0, 5);
        $hora = (int) substr($horaStr, 0, 2);
        $periodoLabel = $hora < 12 ? 'Manhã' : ($hora < 18 ? 'Tarde' : 'Noite');

        $previstas = (int) $row->publico_esperado;
        $presentes = (int) $row->presentes_count;
        $avaliacoes = (int) $row->avaliacoes_count;

        $propPres = $previstas > 0 ? round($presentes / $previstas * 100, 1) : 0;
        $propAval = $presentes > 0 ? round($avaliacoes / $presentes * 100, 1) : 0;

        return [
            $row->evento_nome ?? '—',
            $row->descricao ?? '—',
            $row->abrangencia_nacional ? 'Brasil' : ($row->municipio_nome ?? '—'),
            $row->dia ? Carbon::parse($row->dia)->format('d/m/Y') : '—',
            $horaStr ? $periodoLabel.' ('.$horaStr.')' : '—',
            $previstas ?: '—',
            $presentes,
            $previstas > 0 ? $propPres.'%' : '—',
            $avaliacoes,
            $presentes > 0 ? $propAval.'%' : '—',
        ];
    }
}
