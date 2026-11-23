@extends('layouts.pdf-alfa-eja')

@section('title', 'Relatórios do Momento — ' . ($atividade->descricao ?? 'Momento'))

@section('styles')
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1f2937; background: #ffffff; margin: 0; padding: 0; }
    .sheet { background: #ffffff; border: 0; border-radius: 0; overflow: hidden; }
    .content { padding: 20px 18px 16px 18px; }

    .report-header { background: #421944; color: #ffffff; padding: 14px 18px; }
    .report-header-title { font-size: 24px; font-weight: 700; margin: 0; }
    .report-header-subtitle { font-size: 11px; opacity: 0.92; margin-top: 4px; }

    .title-row { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
    .title-row td { border: 0; vertical-align: bottom; }
    .report-title { font-size: 16px; font-weight: 700; margin: 0 0 3px 0; color: #111827; }
    .author { color: #6b7280; font-size: 12px; }

    .card { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 18px; }
    .table-clean { width: 100%; border-collapse: collapse; }
    .table-clean td, .table-clean th { border: 1px solid #e5e7eb; padding: 10px 12px; vertical-align: top; }
    .table-clean th { text-align: left; background: #421944; color: #ffffff; font-weight: 700; }
    .table-clean .value { background: #ffffff; color: #4b5563; }
    .table-clean .value-number { text-align: right; color: #421944; font-weight: 700; background: #f8fafc; width: 90px; }

    .section-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 10px 0; border-left: 6px solid #421944; padding-left: 10px; }
    .section-subtitle { color: #6b7280; margin: 0 0 10px 0; }
    .qa-item { margin-bottom: 14px; }
    .qa-question { font-size: 12px; font-weight: 700; color: #111827; margin: 0 0 6px 2px; }
    .answer { border: 1px solid #e5e7eb; border-left: 4px solid #421944; background: #f8fafc; border-radius: 0 10px 10px 3px; padding: 10px; color: #374151; word-break: break-word; overflow-wrap: break-word; }
    .answer-meta { font-size: 11px; color: #6b7280; margin-bottom: 4px; }
    .separator { margin: 14px 0; border-top: 1px dashed #cbd5e1; }
    .muted { color: #6b7280; }
@endsection

@section('content')
@php
    $evento = $atividade->evento;
@endphp

<div class="sheet">
    <x-pdf.header
        title="Relatórios do Momento"
        subtitle="Documento institucional consolidado · Todos os relatórios pós-ação do mesmo momento"
    >
        @php $totalRelatorios = $relatorios->count(); @endphp
        Consolidando <strong>{{ $totalRelatorios }}</strong> {{ $totalRelatorios === 1 ? 'relatório' : 'relatórios' }} deste momento.
    </x-pdf.header>

    <div class="content">
        <div class="card">
            <table class="table-clean">
                <tr>
                    <th style="width: 25%;">Ação pedagógica</th>
                    <td class="value">{{ $evento->nome ?? '—' }}</td>
                    <th style="width: 15%;">Momento</th>
                    <td class="value">{{ $atividade->descricao ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Data</th>
                    <td class="value">{{ $atividade->dia ? \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') : '—' }}</td>
                    <th>Horário</th>
                    <td class="value">
                        {{ $atividade->hora_inicio ? \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') : '?' }} -
                        {{ $atividade->hora_fim ? \Carbon\Carbon::parse($atividade->hora_fim)->format('H:i') : '?' }}
                    </td>
                </tr>
            </table>
        </div>

        <h2 class="section-title">Quadro Resumo de Público</h2>
        <div class="card">
            <table class="table-clean">
                <tr><td>Quantidade prevista de participantes</td><td class="value-number">{{ $resumoPublico['prevista'] ?? 0 }}</td></tr>
                <tr><td>Quantidade de inscritos</td><td class="value-number">{{ $resumoPublico['inscritos'] ?? 0 }}</td></tr>
                <tr><td>Quantidade de presentes na ação</td><td class="value-number">{{ $resumoPublico['presentes'] ?? 0 }}</td></tr>
                <tr><td>Participantes ligados aos movimentos sociais</td><td class="value-number">{{ $resumoPublico['movimentos'] ?? 0 }}</td></tr>
                <tr><td>Participantes com vínculo com a prefeitura</td><td class="value-number">{{ $resumoPublico['prefeitura'] ?? 0 }}</td></tr>
                <tr><td>Participantes com vínculo não informado</td><td class="value-number">{{ $resumoPublico['sem_vinculo'] ?? 0 }}</td></tr>
            </table>
        </div>

        <h2 class="section-title">Perguntas e Respostas Consolidadas</h2>
        <p class="section-subtitle">Cada pergunta abaixo reúne todas as respostas enviadas para este mesmo momento.</p>

        @foreach($respostasPorPergunta as $indexPergunta => $itemPergunta)
            <div class="qa-item">
                <div class="qa-question">{{ $itemPergunta['pergunta'] }}</div>

                @if($itemPergunta['respostas']->isEmpty())
                    <div class="answer muted">Nenhuma resposta registrada.</div>
                @else
                    @foreach($itemPergunta['respostas'] as $resposta)
                        <div class="answer" style="margin-top: 6px;">
                            <div class="answer-meta">
                                <strong>Responsável:</strong> {{ $resposta['responsavel_nome'] }}
                                @if(!empty($resposta['atualizado_em']))
                                    | <strong>Enviado em:</strong> {{ $resposta['atualizado_em']->format('d/m/Y') }}
                                @endif
                            </div>
                            <div>{!! $resposta['resposta'] !!}</div>
                        </div>
                    @endforeach
                @endif
            </div>

            @if(! $loop->last)
                <div class="separator"></div>
            @endif
        @endforeach
    </div>
</div>
@endsection
