@extends('layouts.pdf-alfa-eja')

@php
    use Carbon\Carbon;
    $palette = ['#421944', '#008BBC', '#FDB913', '#E62270', '#2EB57D', '#601F69', '#6C345E', '#9602C7'];
    $contextoConsolidacao = match($agrupamento) {
        'regiao'    => 'Respostas consolidadas e agrupadas por <strong>região</strong>.',
        'municipio' => 'Respostas consolidadas e agrupadas por <strong>município</strong>.',
        default     => 'Respostas consolidadas de <strong>todos os municípios</strong>.',
    };
@endphp

@section('title', 'Consolidação de Avaliações — ' . ($evento->nome ?? ''))

@section('styles')
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; margin: 0; padding: 0; background: #ffffff; }
    .sheet { background: #ffffff; border: 0; border-radius: 0; overflow: hidden; }
    .content { padding: 18px 18px 16px 18px; }

    .title-row { width: 100%; border-collapse: collapse; margin-bottom: 14px; border-bottom: 2px solid #421944; padding-bottom: 10px; }
    .title-row td { border: 0; vertical-align: bottom; }
    .report-title { font-size: 16px; font-weight: 700; margin: 0 0 3px 0; color: #421944; }
    .report-subtitle { color: #6b7280; font-size: 11px; }
    .report-generated { text-align: right; color: #9ca3af; font-size: 10px; }

    .intro-box { border: 1px solid #e8dff0; border-radius: 6px; padding: 10px 12px; margin-bottom: 14px; background: #fff; }
    .intro-box p { margin: 0 0 4px 0; }
    .intro-box strong { color: #421944; }
    .grupo-title { font-size: 14px; font-weight: bold; color: #421944; margin: 18px 0 8px 0; padding: 6px 10px; background: #f3eaf4; border-left: 5px solid #421944; border-radius: 0 4px 4px 0; page-break-after: avoid; }
    .template-header { border: 1px solid #e8dff0; border-radius: 6px; padding: 8px 12px; margin-bottom: 10px; background: #fafafa; page-break-inside: avoid; }
    .template-badge { font-size: 8px; text-transform: uppercase; letter-spacing: 0.08em; color: #421944; background: #f3eaf4; border: 1px solid #d9bfdc; border-radius: 10px; padding: 2px 6px; display: inline-block; margin-bottom: 4px; }
    .template-name { font-size: 12px; font-weight: bold; color: #1f2937; margin: 2px 0; }
    .template-meta { font-size: 10px; color: #6b7280; }
    .template-media { font-size: 15px; font-weight: bold; color: #421944; }
    .dim-title { font-size: 12px; font-weight: bold; color: #421944; margin: 12px 0 6px 0; padding-bottom: 3px; border-bottom: 1px solid #edd7fc; page-break-after: avoid; }
    .ind-title { font-size: 10px; font-weight: bold; color: #444; margin: 8px 0 5px 0; page-break-after: avoid; }
    .question-block { border: 1px solid #ddd; border-radius: 4px; padding: 7px 10px; margin-bottom: 9px; page-break-inside: avoid; background: #fff; }
    .question-num { color: #666; font-size: 10px; margin-right: 4px; }
    .question-text { font-weight: bold; color: #222; margin-bottom: 5px; }
    .muted { color: #666; font-size: 10px; }
    .bar-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .bar-table td { padding: 2px 4px; vertical-align: middle; border: none; font-size: 10px; }
    .bar-label { width: 30%; word-break: break-word; }
    .bar-cell { width: 58%; }
    .bar-fill-wrap { width: 100%; height: 11px; background: #eee; border-radius: 2px; overflow: hidden; }
    .bar-fill { height: 11px; border-radius: 2px; }
    .bar-count { width: 12%; text-align: right; font-weight: bold; }
    .text-answer { border-left: 3px solid #421944; padding: 3px 7px; margin: 3px 0; background: #fafafa; font-size: 10px; word-break: break-word; }
    .empty-state { border: 1px dashed #ccc; padding: 14px; text-align: center; color: #777; margin: 12px 0; border-radius: 6px; }
    .separator { margin: 16px 0; border-top: 1px dashed #d1d5db; }
@endsection

@section('content')
    <div class="sheet">
        <div class="content">
            <x-pdf.header
                title="Consolidação de Avaliações"
                :subtitle="$evento->nome"
            >
                {!! $contextoConsolidacao !!}
            </x-pdf.header>

    @if(empty($grupos))
        <div class="empty-state">Nenhum dado de avaliação encontrado para esta ação pedagógica.</div>
    @else
        @foreach($grupos as $grupo)
            <div class="grupo-title">{{ $grupo['nome'] }} &nbsp;<span style="font-size:10px; font-weight:normal;">({{ count($grupo['templates']) }} modelo(s))</span></div>

            @foreach($grupo['templates'] as $tpl)
                <div class="template-header">
                    <div class="template-badge">Modelo de Avaliação</div>
                    <div class="template-name">{{ $tpl['template_nome'] }}</div>
                    <table style="width:100%; border-collapse:collapse; margin-top:4px;">
                        <tr>
                            <td class="template-meta">Submissões: <strong>{{ number_format($tpl['submissoes'] ?? 0, 0, ',', '.') }}</strong></td>
                            <td class="template-meta">Respostas: <strong>{{ number_format($tpl['respostas'] ?? 0, 0, ',', '.') }}</strong></td>
                            <td class="template-meta" style="text-align:right;">
                                Média geral:
                                <span class="template-media">{{ $tpl['media_geral'] !== null ? number_format($tpl['media_geral'], 2, ',', '.') : '—' }}</span>
                            </td>
                        </tr>
                    </table>
                </div>

                @php
                    $dimAtual = null;
                    $indAtual = null;
                    $numQ = 0;
                @endphp

                @foreach($tpl['perguntas'] ?? [] as $p)
                    @php
                        $dim = $p['dimensao'] ?? 'Sem dimensão';
                        $ind = $p['indicador'] ?? 'Sem indicador';
                    @endphp
                    @if($dim !== $dimAtual)
                        @php $dimAtual = $dim; $indAtual = null; @endphp
                        <div class="dim-title">Dimensão — {{ $dim }}</div>
                    @endif
                    @if($ind !== $indAtual)
                        @php $indAtual = $ind; @endphp
                        <div class="ind-title">Indicador — {{ $ind }}</div>
                    @endif
                    @php
                        $numQ++;
                        $tipo   = $p['tipo'] ?? 'texto';
                        $labels = array_values(collect($p['labels'] ?? [])->all());
                        $values = array_values(collect($p['values'] ?? [])->all());
                    @endphp

                    <div class="question-block">
                        <div class="question-text">
                            <span class="question-num">{{ $numQ }}.</span>{{ $p['texto'] ?? 'Questão' }}
                        </div>
                        <div class="muted">{{ $p['total'] ?? 0 }} resposta(s)
                            @if(!empty($p['resumo'])) · {{ $p['resumo'] }} @endif
                        </div>

                        @if($tipo === 'texto')
                            @php $lista = $p['respostas'] ?? []; @endphp
                            @if(empty($lista))
                                <p class="muted" style="margin-top:5px;">Sem respostas de texto.</p>
                            @else
                                @foreach($lista as $txt)
                                    <div class="text-answer">{{ $txt }}</div>
                                @endforeach
                                @if(!empty($p['respostas_truncadas']))
                                    <p class="muted" style="margin-top:6px;">Nota: lista truncada ({{ $p['respostas_total'] ?? count($lista) }} total).</p>
                                @endif
                            @endif
                        @else
                            @php $totalBarras = !empty($values) ? max(1, array_sum($values)) : 1; @endphp
                            <table class="bar-table">
                                @foreach($labels as $idx => $label)
                                    @php
                                        $rawLabel = trim(strip_tags((string) $label));
                                        $labelShow = $rawLabel === 'Nao' ? 'Não' : $rawLabel;
                                        $val  = (int) ($values[$idx] ?? 0);
                                        $pct  = round(($val / $totalBarras) * 100);
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

                @if(!$loop->last)<div class="separator"></div>@endif
            @endforeach
        @endforeach
    @endif
        </div>
    </div>
@endsection
