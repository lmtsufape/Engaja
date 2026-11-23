@extends('layouts.pdf-alfa-eja')

@php
    use Carbon\Carbon;

    $palette = ['#421944', '#008BBC', '#FDB913', '#E62270', '#2EB57D', '#601F69', '#6C345E', '#9602C7'];

    $titulo = 'Relatório de Avaliações';
    $subtitulo = 'Todas as ações pedagógicas';

    if ($tipo === 'universal') {
        $titulo = 'Avaliações Universais';
        $subtitulo = $avaliacaoUniversal ? ($avaliacaoUniversal->descricao_universal ?? $avaliacaoUniversal->templateAvaliacao->nome) : 'Todas as avaliações';
    } elseif ($atividade) {
        $titulo = 'Avaliação — ' . ($atividade->descricao ?? 'Momento');
        $subtitulo = $atividade->evento->nome ?? 'Ação Pedagógica';
    } elseif ($evento) {
        $titulo = 'Avaliações — ' . ($evento->nome ?? 'Ação Pedagógica');
        $subtitulo = 'Todas as atividades';
    }

    $de = ! empty($filtros['de']) ? Carbon::parse($filtros['de'])->format('d/m/Y') : null;
    $ate = ! empty($filtros['ate']) ? Carbon::parse($filtros['ate'])->format('d/m/Y') : null;

    $respostasTotais = (int) ($totais['respostas'] ?? 0);
@endphp

@section('title', $titulo)

@section('styles')
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; background: #ffffff; }
    .sheet { background: #ffffff; border: 0; border-radius: 0; overflow: hidden; }
    .content { padding: 18px 18px 16px 18px; }

    .title-row { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .title-row td { border: 0; vertical-align: bottom; }
    .report-title { font-size: 16px; font-weight: 700; margin: 0 0 3px 0; color: #111827; }
    .report-subtitle { color: #6b7280; font-size: 11px; }
    .report-generated { text-align: right; color: #9ca3af; font-size: 10px; }

    .metrics { width: 100%; border-collapse: collapse; margin-bottom: 14px; table-layout: fixed; }
    .metrics td { border: 1px solid #edd7fc; background: #fcfaff; padding: 8px 10px; width: 25%; vertical-align: top; }
    .metric-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.35px; color: #6b7a99; display: block; margin-bottom: 2px; }
    .metric-value { font-size: 15px; font-weight: 700; color: #421944; }

    .section-title { font-size: 13px; font-weight: 700; color: #421944; margin: 14px 0 8px 0; padding-bottom: 4px; border-bottom: 1px solid #edd7fc; page-break-after: avoid; }
    .question-block { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 12px; margin-bottom: 10px; page-break-inside: avoid; background: #fff; }
    .question-num { color: #6b7280; font-size: 10px; margin-right: 4px; }
    .question-text { font-weight: 700; color: #111827; margin-bottom: 6px; }
    .muted { color: #6b7280; font-size: 10px; }
    .empty-state { border: 1px dashed #ccc; padding: 16px; text-align: center; color: #777; margin: 12px 0; border-radius: 8px; }

    .text-answer-list { margin-top: 6px; }
    .text-answer { border-left: 3px solid #421944; padding: 5px 8px; margin: 4px 0; background: #fafafa; font-size: 10px; word-break: break-word; }

    .bar-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .bar-table td { padding: 3px 4px; vertical-align: middle; border: none; font-size: 10px; }
    .bar-label { width: 30%; word-break: break-word; }
    .bar-cell { width: 58%; }
    .bar-fill-wrap { width: 100%; height: 12px; background: #eee; border-radius: 2px; overflow: hidden; }
    .bar-fill { height: 12px; border-radius: 2px; }
    .bar-count { width: 12%; text-align: right; font-weight: 700; }
@endsection

@section('content')
    <div class="sheet">
        <div class="content">
            @php
                $partesContexto = [];
                $rotuloPrincipal = $tipo === 'universal' ? 'a avaliação' : 'a ação pedagógica';
                $partesContexto[] = 'Respostas coletadas para ' . $rotuloPrincipal . ' <strong>' . e($subtitulo) . '</strong>';
                if ($atividade) {
                    $dataMomento = $atividade->dia ? Carbon::parse($atividade->dia)->format('d/m/Y') : null;
                    $municipiosMomento = $atividade->municipios->map(fn($m) => $m->nome_com_estado ?? $m->nome)->join(', ');
                    if ($dataMomento) {
                        $partesContexto[] = 'realizada em <strong>' . $dataMomento . '</strong>';
                    }
                    if ($municipiosMomento) {
                        $partesContexto[] = 'no(s) município(s) de <strong>' . e($municipiosMomento) . '</strong>';
                    }
                }
                if ($de || $ate) {
                    $partesContexto[] = 'no período de <strong>' . ($de ?? 'início') . '</strong> a <strong>' . ($ate ?? 'hoje') . '</strong>';
                }
            @endphp
            <x-pdf.header
                :title="$titulo"
                :subtitle="'Relatório de respostas · ' . ($tipo === 'universal' ? 'Avaliação universal' : 'Avaliação anônima')"
            >
                {!! implode(', ', $partesContexto) !!}.
            </x-pdf.header>

            <table class="metrics">
                <tr>
                    <td>
                        <span class="metric-label">Submissões</span>
                        <span class="metric-value">{{ number_format($totais['submissoes'] ?? 0, 0, ',', '.') }}</span>
                    </td>
                    <td>
                        <span class="metric-label">Questões com resposta</span>
                        <span class="metric-value">{{ number_format($totais['questoes'] ?? 0, 0, ',', '.') }}</span>
                    </td>
                    <td>
                        <span class="metric-label">Respostas (itens)</span>
                        <span class="metric-value">{{ number_format($respostasTotais, 0, ',', '.') }}</span>
                    </td>
                    <td>
                        <span class="metric-label">Última resposta</span>
                        <span class="metric-value" style="font-size: 11px;">{{ $totais['ultima'] ?? '—' }}</span>
                    </td>
                </tr>
            </table>

            @if(! empty($perguntas))
                @php
                    $dimAtual = null;
                    $indAtual = null;
                    $numQuestao = 0;
                @endphp

                @foreach($perguntas as $p)
                    @php
                        $dim = $p['dimensao'] ?? 'Sem dimensão';
                        $ind = $p['indicador'] ?? 'Sem indicador';
                    @endphp

                    @if($dim !== $dimAtual)
                        @php $dimAtual = $dim; $indAtual = null; @endphp
                        <div class="section-title">Dimensão — {{ $dim }}</div>
                    @endif

                    @if($ind !== $indAtual)
                        @php $indAtual = $ind; @endphp
                        <div class="section-title" style="font-size: 12px; color: #333; border-bottom-color: #e5e7eb; margin-top: 10px;">Indicador — {{ $ind }}</div>
                    @endif

                    @php
                        $numQuestao++;
                        $tipoQuestao = $p['tipo'] ?? 'texto';
                        $labels = array_values(collect($p['labels'] ?? [])->all());
                        $values = array_values(collect($p['values'] ?? [])->all());
                    @endphp

                    <div class="question-block">
                        <div class="question-text">
                            <span class="question-num">{{ $numQuestao }}.</span>
                            {{ $p['texto'] ?? 'Questão' }}
                        </div>
                        <div class="muted">{{ $p['total'] ?? 0 }} resposta(s)
                            @if(! empty($p['resumo']))
                                · {{ $p['resumo'] }}
                            @endif
                        </div>

                        @if($tipoQuestao === 'texto')
                            @php $lista = $p['respostas'] ?? []; @endphp
                            @if(empty($lista))
                                <p class="muted" style="margin-top:6px;">Sem respostas de texto.</p>
                            @else
                                <div class="text-answer-list">
                                    @foreach(array_slice($lista, 0, 50) as $txt)
                                        <div class="text-answer">{{ $txt }}</div>
                                    @endforeach
                                </div>
                                @if(count($lista) > 50)
                                    <p class="muted" style="margin-top:8px;">Nota: lista truncada para 50 itens ao gerar o PDF ({{ count($lista) }} resposta(s) no total).</p>
                                @endif
                            @endif
                        @else
                            @php
                                $totalBarras = ! empty($values) ? max(1, array_sum($values)) : 1;
                            @endphp
                            <p class="muted" style="margin:4px 0 2px 0;">Barras: percentagem em relação ao total de respostas nesta questão (soma das opções).</p>
                            <table class="bar-table">
                                @foreach($labels as $idx => $label)
                                    @php
                                        $rawLabel = trim(strip_tags((string) $label));
                                        $labelShow = $rawLabel === 'Nao' ? 'Não' : $rawLabel;
                                        $val = (int) ($values[$idx] ?? 0);
                                        $pct = round(($val / $totalBarras) * 100);
                                        $color = $palette[$idx % count($palette)];
                                    @endphp
                                    <tr>
                                        <td class="bar-label">{{ $labelShow }}</td>
                                        <td class="bar-cell">
                                            <div class="bar-fill-wrap">
                                                <div class="bar-fill" style="width: {{ $pct }}%; background: {{ $color }};"></div>
                                            </div>
                                        </td>
                                        <td class="bar-count">{{ $val }}</td>
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </div>
                @endforeach
            @else
                <div class="empty-state">Nenhuma resposta agregada para os filtros selecionados no momento da geração do PDF.</div>
            @endif
        </div>
    </div>
@endsection
