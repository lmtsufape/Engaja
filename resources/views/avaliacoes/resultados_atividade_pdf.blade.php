@extends('layouts.pdf-alfa-eja')

@php
    use Carbon\Carbon;
    $palette = ['#421944', '#008BBC', '#FDB913', '#E62270', '#2EB57D', '#601F69', '#6C345E', '#9602C7'];
    $eventoNome = $atividade->evento->nome ?? '—';
    $diaFmt = $atividade->dia ? Carbon::parse($atividade->dia)->format('d/m/Y') : '—';
    $municipiosTxt = $atividade->municipios->isNotEmpty()
        ? $atividade->municipios->map(fn ($m) => $m->nome_com_estado ?? $m->nome)->join(', ')
        : '—';
    $templateNome = $avaliacao->templateAvaliacao->nome ?? '—';
@endphp

@section('title', 'Avaliação — ' . ($atividade->descricao ?? ''))

@section('styles')
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 12px 0 24px; }
    .intro-box { border: 1px solid #e8dff0; border-radius: 6px; padding: 10px 12px; margin-bottom: 12px; background: #fff; }
    .intro-box p { margin: 0 0 4px 0; }
    .intro-box strong { color: #421944; }
    .metrics { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .metrics td { border: 1px solid #edd7fc; background: #fcfaff; padding: 8px 10px; width: 25%; vertical-align: top; }
    .metric-label { font-size: 9px; text-transform: uppercase; letter-spacing: 0.35px; color: #6b7a99; display: block; margin-bottom: 2px; }
    .metric-value { font-size: 15px; font-weight: bold; color: #421944; }
    .dim-title { font-size: 13px; font-weight: bold; color: #421944; margin: 14px 0 8px 0; padding-bottom: 4px; border-bottom: 1px solid #edd7fc; page-break-after: avoid; }
    .ind-title { font-size: 11px; font-weight: bold; color: #333; margin: 10px 0 6px 0; page-break-after: avoid; }
    .question-block { border: 1px solid #ddd; border-radius: 4px; padding: 8px 10px; margin-bottom: 10px; page-break-inside: avoid; background: #fff; }
    .question-num { color: #666; font-size: 10px; margin-right: 4px; }
    .question-text { font-weight: bold; color: #222; margin-bottom: 6px; }
    .muted { color: #666; font-size: 10px; }
    .bar-table { width: 100%; border-collapse: collapse; margin-top: 4px; }
    .bar-table td { padding: 3px 4px; vertical-align: middle; border: none; font-size: 10px; }
    .bar-label { width: 30%; word-break: break-word; }
    .bar-cell { width: 58%; }
    .bar-fill-wrap { width: 100%; height: 12px; background: #eee; border-radius: 2px; overflow: hidden; }
    .bar-fill { height: 12px; border-radius: 2px; }
    .bar-count { width: 12%; text-align: right; font-weight: bold; }
    .text-answer { border-left: 3px solid #421944; padding: 4px 8px; margin: 4px 0; background: #fafafa; font-size: 10px; word-break: break-word; }
    .empty-state { border: 1px dashed #ccc; padding: 16px; text-align: center; color: #777; margin: 12px 0; border-radius: 6px; }
@endsection

@section('content')
    <x-pdf.header
        :title="'Avaliação — ' . $atividade->descricao"
        subtitle="Relatório de respostas · Avaliação anónima"
    />

    <div class="intro-box">
        <p><strong>Ação pedagógica:</strong> {{ $eventoNome }}</p>
        <p><strong>Data:</strong> {{ $diaFmt }} &nbsp;·&nbsp; <strong>Município(s):</strong> {{ $municipiosTxt }}</p>
        <p><strong>Modelo de formulário:</strong> {{ $templateNome }}</p>
    </div>

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
                <span class="metric-value">{{ number_format($totais['respostas'] ?? 0, 0, ',', '.') }}</span>
            </td>
            <td>
                <span class="metric-label">Última resposta</span>
                <span class="metric-value" style="font-size: 11px;">{{ $totais['ultima'] ?? '—' }}</span>
            </td>
        </tr>
    </table>

    @if(empty($perguntas))
        <div class="empty-state">Nenhuma resposta agregada para este momento no momento da geração do PDF.</div>
    @else
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
                <div class="dim-title">Dimensão — {{ $dim }}</div>
            @endif
            @if($ind !== $indAtual)
                @php $indAtual = $ind; @endphp
                <div class="ind-title">Indicador — {{ $ind }}</div>
            @endif
            @php
                $numQuestao++;
                $tipo = $p['tipo'] ?? 'texto';
                $labels = array_values(collect($p['labels'] ?? [])->all());
                $values = array_values(collect($p['values'] ?? [])->all());
            @endphp

            <div class="question-block">
                <div class="question-text">
                    <span class="question-num">{{ $numQuestao }}.</span>
                    {{ $p['texto'] ?? 'Questão' }}
                </div>
                <div class="muted">{{ $p['total'] ?? 0 }} resposta(s)
                    @if(!empty($p['resumo']))
                        · {{ $p['resumo'] }}
                    @endif
                </div>

                @if($tipo === 'texto')
                    @php $lista = $p['respostas'] ?? []; @endphp
                    @if(empty($lista))
                        <p class="muted" style="margin-top:6px;">Sem respostas de texto.</p>
                    @else
                        @foreach($lista as $txt)
                            <div class="text-answer">{{ $txt }}</div>
                        @endforeach
                        @if(!empty($p['respostas_truncadas']))
                            <p class="muted" style="margin-top:8px;">Nota: lista truncada ao gerar o resumo ({{ $p['respostas_total'] ?? count($lista) }} resposta(s) no total).</p>
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
    @endif
@endsection
