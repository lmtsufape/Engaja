@extends('layouts.pdf-alfa-eja')

@section('title', 'Relatório do Momento')

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
    .author-name { color: #421944; font-weight: 700; }

    .card { border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; margin-bottom: 18px; }
    .table-clean { width: 100%; border-collapse: collapse; }
    .table-clean td, .table-clean th { border: 1px solid #e5e7eb; padding: 10px 12px; vertical-align: top; }
    .table-clean th { text-align: left; background: #421944; color: #ffffff; font-weight: 700; }
    .table-clean .value { background: #ffffff; color: #4b5563; }
    .table-clean .value-number { text-align: right; color: #421944; font-weight: 700; background: #f8fafc; width: 90px; }

    .section-title { font-size: 18px; font-weight: 700; color: #111827; margin: 0 0 10px 0; border-left: 6px solid #421944; padding-left: 10px; }
    .qa-item { margin-bottom: 14px; }
    .qa-question { font-size: 12px; font-weight: 700; color: #111827; margin: 0 0 6px 2px; }
    .qa-answer { border: 1px solid #e5e7eb; border-left: 4px solid #421944; background: #f8fafc; border-radius: 0 10px 10px 3px; padding: 10px; color: #374151; word-break: break-word; overflow-wrap: break-word; }

    .checklist-wrap { border-top: 1px solid #e5e7eb; padding-top: 14px; margin-top: 6px; }
    .checklist-list { margin: 0; padding: 12px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f8fafc; list-style: none; }
    .checklist-list li { margin-bottom: 9px; color: #374151; }
    .checklist-list li:last-child { margin-bottom: 0; }
    .check-bullet { display: inline-block; width: 8px; height: 8px; margin-right: 7px; border-radius: 50%; background: #22c55e; }
    .muted { color: #6b7280; }
@endsection

@section('content')
@php
    $atividade = $relatorio->atividade;
    $evento = $atividade?->evento;
    $nomeResponsavel = $relatorio->user?->name ?? $relatorio->nome_educador ?? 'Usuário não identificado';
    $checklistSalvo = $relatorio->checklist_pos_acao ?? [];

    $checklistLabels = [
        'upload_evidencias'       => 'Fez o upload das evidências (fotos, vídeos com depoimentos) na pasta correspondente a essa ação dentro do Drive',
        'lista_presenca_digital'  => 'Conferiu as listas de presença digital (link acima), garantindo que todos os campos estejam devidamente preenchidos',
        'lista_presenca_impressa' => 'Conferiu as listas de presença impressa, garantindo que todos os campos estejam devidamente preenchidos',
        'upload_lista_impressa'   => 'Fez o upload das listas de presença impressas na pasta dentro do Drive, depois de devidamente conferida e ajustada',
    ];
@endphp

<div class="sheet">
    <x-pdf.header title="Relatório do Momento" subtitle="Documento institucional">
        @if(filled($nomeResponsavel))
            Preenchido por <strong>{{ $nomeResponsavel }}</strong>.
        @endif
    </x-pdf.header>

    <div class="content">
        <div class="card">
            <table class="table-clean">
                <tr>
                    <th style="width: 25%;">Ação pedagógica</th>
                    <td class="value">{{ $evento?->nome ?? '—' }}</td>
                    <th style="width: 15%;">Momento</th>
                    <td class="value">{{ $atividade?->descricao ?? '—' }}</td>
                </tr>
                <tr>
                    <th>Data</th>
                    <td class="value">{{ $atividade?->dia ? \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') : '—' }}</td>
                    <th>Horário</th>
                    <td class="value">
                        {{ $atividade?->hora_inicio ? \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') : '?' }} -
                        {{ $atividade?->hora_fim ? \Carbon\Carbon::parse($atividade->hora_fim)->format('H:i') : '?' }}
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

        <h2 class="section-title">Perguntas e Respostas</h2>
        @foreach($camposPerguntas as $campo => $pergunta)
            <div class="qa-item">
                <div class="qa-question">{{ $pergunta }}</div>
                <div class="qa-answer">{!! $relatorio->$campo ?: '<span class="muted">—</span>' !!}</div>
            </div>
        @endforeach

        <div class="checklist-wrap">
            <h2 class="section-title">Checklist Pós-ação</h2>
            @if(empty($checklistSalvo))
                <p class="muted">Nenhum item marcado.</p>
            @else
                <ul class="checklist-list">
                    @foreach($checklistLabels as $valor => $label)
                        @if(in_array($valor, $checklistSalvo, true))
                            <li><span class="check-bullet"></span>{{ $label }}</li>
                        @endif
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection
