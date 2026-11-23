@extends('layouts.pdf-alfa-eja')

@section('title')
    Cronograma – {{ $evento->nome ?? 'Ação Pedagógica' }}
@endsection

@section('styles')
    .content {
        padding: 20px 14px 28px;
    }

    /* ── Bloco de resumo da ação ── */
    .resumo-acao {
        background: #fcfaff;
        border-left: 4px solid #421944;
        padding: 8px 12px;
        margin-bottom: 20px;
        font-size: 10px;
        line-height: 1.55;
        color: #374151;
        page-break-inside: avoid;
    }
    .resumo-acao strong { color: #421944; }
    .resumo-acao .resumo-row {
        display: inline-block;
        margin-right: 18px;
    }

    /* ── Cabeçalho de dia ── */
    .day-header {
        background: #421944;
        color: #fff;
        padding: 7px 10px;
        margin-bottom: 0;
        border-radius: 4px 4px 0 0;
        page-break-inside: avoid;
        page-break-after: avoid;
    }
    .day-header__date {
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 0.03em;
    }
    .day-header__weekday {
        font-size: 9px;
        opacity: 0.85;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }
    .day-header__total {
        font-size: 9px;
        opacity: 0.75;
        float: right;
        margin-top: 4px;
    }

    /* ── Contêiner do dia ── */
    .day-block {
        margin-bottom: 18px;
        page-break-inside: avoid;
    }

    /* ── Tabela de momentos ── */
    .moments-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #d1d5db;
        border-top: none;
        border-radius: 0 0 4px 4px;
    }
    .moments-table th {
        background: #ece3ee;
        color: #421944;
        font-size: 8.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 5px 8px;
        border-bottom: 1px solid #c9a0d0;
        text-align: left;
    }
    .moments-table th.text-center { text-align: center; }
    .moments-table td {
        padding: 6px 8px;
        font-size: 9.5px;
        vertical-align: top;
        border-bottom: 1px solid #f0e8f3;
        color: #1f2937;
    }
    .moments-table tbody tr:last-child td {
        border-bottom: none;
    }
    .moments-table tbody tr:nth-child(even) td {
        background: #fdfbff;
    }

    /* ── Células específicas ── */
    .col-time {
        width: 100px;
        white-space: nowrap;
        font-weight: 700;
        color: #421944;
    }
    .col-index {
        width: 22px;
        text-align: center;
        font-weight: 700;
        color: #9ca3af;
        font-size: 8.5px;
    }
    .col-local   { width: 130px; }
    .col-ch      { width: 70px; text-align: center; }
    .col-publico { width: 70px; text-align: center; }

    .momento-titulo {
        font-weight: 700;
        font-size: 9.5px;
        color: #111827;
        margin-bottom: 2px;
    }
    .momento-meta {
        font-size: 8.5px;
        color: #6b7280;
    }

    /* ── Rodapé de resumo geral ── */
    .totals-box {
        margin-top: 20px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        overflow: hidden;
        page-break-inside: avoid;
    }
    .totals-box__header {
        background: #421944;
        color: #fff;
        padding: 6px 10px;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.03em;
    }
    .totals-table {
        width: 100%;
        border-collapse: collapse;
    }
    .totals-table td {
        padding: 6px 10px;
        font-size: 9.5px;
        border-bottom: 1px solid #f0e8f3;
        vertical-align: middle;
    }
    .totals-table tr:last-child td { border-bottom: none; }
    .totals-table .label { color: #6b7280; width: 180px; }
    .totals-table .value { font-weight: 700; color: #1f2937; }

    .sem-momentos {
        text-align: center;
        color: #9ca3af;
        font-size: 10px;
        padding: 24px;
        border: 1px dashed #d1d5db;
        border-radius: 6px;
        margin-top: 16px;
    }
@endsection

@section('content')
@php
    use Carbon\Carbon;
    use App\Support\CargaHoraria;

    $acoesGerais  = \App\Models\Evento::ACOES_GERAIS;
    $agrupamento  = $agrupamento ?? 'data';

    if ($agrupamento === 'municipio') {
        // ── Agrupa por município (nome) ──────────────────────────────────────
        // Cada atividade pode ter múltiplos municípios; a estratégia adotada é:
        // • Abrangência nacional  → grupo "Abrangência Nacional"
        // • 1 ou mais municípios  → um grupo por município (atividade repete nos grupos)
        // • Sem município         → grupo "Sem Município Definido"
        $porGrupo = collect();

        foreach ($atividades as $at) {
            if ($at->abrangencia_nacional) {
                $keys = ['Abrangência Nacional'];
            } elseif ($at->municipios->isNotEmpty()) {
                $keys = $at->municipios->map(fn ($m) => $m->nome_com_estado ?? $m->nome)->toArray();
            } else {
                $keys = ['Sem Município Definido'];
            }

            foreach ($keys as $key) {
                if (! isset($porGrupo[$key])) {
                    $porGrupo[$key] = collect();
                }
                $porGrupo[$key]->push($at);
            }
        }

        // Ordena: "Abrangência Nacional" primeiro, depois alfabético; cada grupo
        // ordena internamente por dia e hora de início
        $porGrupo = $porGrupo
            ->sortKeys()
            ->map(fn ($lista) =>
                $lista->sortBy(fn ($a) =>
                    Carbon::parse($a->dia)->toDateString() . ' ' . Carbon::parse($a->hora_inicio)->format('H:i')
                )
            );

        $labelGrupo = 'Município';
        $totalGrupos = $porGrupo->count();
    } else {
        // ── Agrupa por data (comportamento original) ─────────────────────────
        $porGrupo = $atividades
            ->sortBy(fn ($a) => Carbon::parse($a->dia)->toDateString() . ' ' . Carbon::parse($a->hora_inicio)->format('H:i'))
            ->groupBy(fn ($a) => Carbon::parse($a->dia)->toDateString());

        $labelGrupo  = 'Data';
        $totalGrupos = $porGrupo->count();
    }

    $totalMomentos = $atividades->count();

    // Carga horária total em minutos (soma dos momentos)
    $totalMinutos = $atividades->sum(fn ($a) => (int) ($a->carga_horaria ?? 0));

    // Período da ação
    $dataInicio = $evento->data_inicio ? Carbon::parse($evento->data_inicio) : null;
    $dataFim    = $evento->data_fim    ? Carbon::parse($evento->data_fim)    : null;
@endphp


<div class="content">

    {{-- Cabeçalho do documento --}}
    <x-pdf.header
        title="Cronograma da Ação Pedagógica"
        subtitle="Engaja"
    >
        <strong>{{ $evento->nome }}</strong>
    </x-pdf.header>

    {{-- Resumo da ação --}}
    <div class="resumo-acao">
        @if($dataInicio || $dataFim)
        <span class="resumo-row">
            <strong>Período:</strong>
            {{ $dataInicio ? $dataInicio->format('d/m/Y') : '—' }}
            @if($dataFim && !$dataInicio?->isSameDay($dataFim))
                até {{ $dataFim->format('d/m/Y') }}
            @endif
        </span>
        @endif
        @if($evento->local)
        <span class="resumo-row"><strong>Local:</strong> {{ $evento->local }}</span>
        @endif
        @if($evento->modalidade)
        <span class="resumo-row"><strong>Modalidade:</strong> {{ ucfirst($evento->modalidade) }}</span>
        @endif
    </div>

    @if($porGrupo->isEmpty())
        <div class="sem-momentos">
            Nenhum momento cadastrado para esta ação pedagógica.
        </div>
    @else

        {{-- Blocos por grupo (data ou município) --}}
        @foreach($porGrupo as $chave => $lista)
        @php
            $totalMinsNoGrupo = $lista->sum(fn ($a) => (int) ($a->carga_horaria ?? 0));
            $chGrupoLabel     = $totalMinsNoGrupo > 0 ? CargaHoraria::formatMinutos($totalMinsNoGrupo) : null;

            if ($agrupamento === 'data') {
                $carbon        = Carbon::parse($chave)->locale('pt_BR');
                $tituloGrupo   = $carbon->translatedFormat('d \d\e F \d\e Y');
                $subtituloGrupo = $carbon->translatedFormat('l');
            } else {
                $tituloGrupo    = $chave; // nome do município
                $subtituloGrupo = null;
            }
        @endphp

        <div class="day-block">

            {{-- Cabeçalho do grupo --}}
            <div class="day-header">
                @if($chGrupoLabel)
                    <span class="day-header__total">&#9200; {{ $chGrupoLabel }}</span>
                @endif
                <div class="day-header__date">{{ $tituloGrupo }}</div>
                @if($subtituloGrupo)
                    <div class="day-header__weekday">{{ $subtituloGrupo }}</div>
                @endif
            </div>

            {{-- Tabela de momentos do grupo --}}
            <table class="moments-table">
                <thead>
                    <tr>
                        <th class="col-index">#</th>
                        <th class="col-time">
                            @if($agrupamento === 'municipio') Data / @endif Horário
                        </th>
                        <th>Momento / Descrição</th>
                        <th class="col-local">Local / Município</th>
                        <th class="col-ch text-center">Carga Horária</th>
                        <th class="col-publico text-center">Público</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lista as $idx => $at)
                    @php
                        $ini    = Carbon::parse($at->hora_inicio);
                        $fimObj = !empty($at->hora_fim) ? Carbon::parse($at->hora_fim) : null;
                        if ($fimObj && $fimObj->lessThanOrEqualTo($ini)) {
                            $fimObj->addDay();
                        }
                        $iniStr = $ini->format('H:i');
                        $fimStr = $fimObj ? $fimObj->format('H:i') : null;

                        $descricao = trim($at->descricao ?? '') !== '' ? $at->descricao : 'Momento sem descrição';

                        // Localização
                        $isNacional     = (bool) $at->abrangencia_nacional;
                        $municipios     = $at->municipios ?? collect();
                        $municipioLabel = null;
                        if ($isNacional) {
                            $municipioLabel = 'Abrangência nacional';
                        } elseif ($municipios->isNotEmpty()) {
                            $municipioLabel = $municipios
                                ->map(fn ($m) => $m->nome_com_estado ?? $m->nome)
                                ->join(', ');
                        }
                        $localLabel = $at->local ?? null;

                        // Carga horária
                        $chLabel = !is_null($at->carga_horaria)
                            ? CargaHoraria::formatMinutos((int) $at->carga_horaria)
                            : '—';

                        // Público esperado
                        $publicoLabel = $at->publico_esperado
                            ? number_format($at->publico_esperado, 0, ',', '.')
                            : '—';
                    @endphp
                    <tr>
                        <td class="col-index">{{ $idx + 1 }}</td>
                        <td class="col-time">
                            @if($agrupamento === 'municipio')
                                <span style="font-size:8px;color:#6b7280;display:block;">
                                    {{ Carbon::parse($at->dia)->locale('pt_BR')->translatedFormat('d/m/Y') }}
                                </span>
                            @endif
                            {{ $iniStr }}
                            @if($fimStr)
                                <br><span style="font-size:8px;font-weight:400;color:#6b7280;">até {{ $fimStr }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="momento-titulo">{{ $descricao }}</div>
                        </td>
                        <td class="col-local">
                            @if($municipioLabel)
                                <div class="momento-meta">{{ $municipioLabel }}</div>
                            @endif
                            @if($localLabel)
                                <div class="momento-meta">{{ $localLabel }}</div>
                            @endif
                            @if(!$municipioLabel && !$localLabel)
                                <span style="color:#d1d5db;">—</span>
                            @endif
                        </td>
                        <td class="col-ch" style="text-align:center;">{{ $chLabel }}</td>
                        <td class="col-publico" style="text-align:center;">{{ $publicoLabel }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
        @endforeach

        {{-- Resumo consolidado --}}
        <div class="totals-box">
            <div class="totals-box__header">Resumo do Cronograma</div>
            <table class="totals-table">
                <tr>
                    <td class="label">Total de {{ strtolower($labelGrupo === 'Data' ? 'dias' : 'municípios') }} com atividades</td>
                    <td class="value">{{ $totalGrupos }}</td>
                </tr>
                <tr>
                    <td class="label">Total de momentos cadastrados</td>
                    <td class="value">{{ $totalMomentos }} {{ $totalMomentos === 1 ? 'momento' : 'momentos' }}</td>
                </tr>
                @if($totalMinutos > 0)
                <tr>
                    <td class="label">Carga horária total dos momentos</td>
                    <td class="value">{{ CargaHoraria::formatMinutos($totalMinutos) }}</td>
                </tr>
                @endif
                @if($dataInicio)
                <tr>
                    <td class="label">Período da ação</td>
                    <td class="value">
                        {{ $dataInicio->format('d/m/Y') }}
                        @if($dataFim && !$dataInicio->isSameDay($dataFim))
                            até {{ $dataFim->format('d/m/Y') }}
                        @endif
                    </td>
                </tr>
                @endif
            </table>
        </div>

    @endif


</div>
@endsection
