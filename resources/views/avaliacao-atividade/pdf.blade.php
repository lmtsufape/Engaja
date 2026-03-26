<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório da Ação</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 8px 0; color: #421944; }
        h2 { font-size: 14px; margin: 18px 0 8px 0; color: #421944; }
        .muted { color: #666; }
        .box { border: 1px solid #ddd; border-radius: 6px; padding: 10px; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; vertical-align: top; }
        th { background: #f6f6f6; text-align: left; }
        .question { margin-top: 12px; }
        .answer { border: 1px solid #ddd; padding: 8px; border-radius: 4px; background: #fafafa; }
        .small { font-size: 11px; }
    </style>
</head>
<body>
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

<h1>Relatório da Ação</h1>
<p class="muted">Preenchido por: <strong>{{ $nomeResponsavel }}</strong></p>

<div class="box">
    <table>
        <tr>
            <th style="width: 25%;">Ação pedagógica</th>
            <td>{{ $evento?->nome ?? '—' }}</td>
            <th style="width: 15%;">Momento</th>
            <td>{{ $atividade?->descricao ?? '—' }}</td>
        </tr>
        <tr>
            <th>Data</th>
            <td>{{ $atividade?->dia ? \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') : '—' }}</td>
            <th>Horário</th>
            <td>
                {{ $atividade?->hora_inicio ? \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') : '?' }} -
                {{ $atividade?->hora_fim ? \Carbon\Carbon::parse($atividade->hora_fim)->format('H:i') : '?' }}
            </td>
        </tr>
    </table>
</div>

<h2>Quadro Resumo de Público</h2>
<table>
    <tr><th>Quantidade prevista de participantes</th><td>{{ $resumoPublico['prevista'] ?? 0 }}</td></tr>
    <tr><th>Quantidade de inscritos</th><td>{{ $resumoPublico['inscritos'] ?? 0 }}</td></tr>
    <tr><th>Quantidade de presentes na ação</th><td>{{ $resumoPublico['presentes'] ?? 0 }}</td></tr>
    <tr><th>Participantes ligados aos movimentos sociais</th><td>{{ $resumoPublico['movimentos'] ?? 0 }}</td></tr>
    <tr><th>Participantes com vínculo com a prefeitura</th><td>{{ $resumoPublico['prefeitura'] ?? 0 }}</td></tr>
</table>

<h2>Perguntas e Respostas</h2>
@foreach($camposPerguntas as $campo => $pergunta)
    <div class="question">
        <div><strong>{{ $pergunta }}</strong></div>
        <div class="answer">{{ $relatorio->$campo ?: '—' }}</div>
    </div>
@endforeach

<h2>Checklist Pós-ação</h2>
@if(empty($checklistSalvo))
    <p class="muted">Nenhum item marcado.</p>
@else
    <ul>
        @foreach($checklistLabels as $valor => $label)
            @if(in_array($valor, $checklistSalvo, true))
                <li class="small">{{ $label }}</li>
            @endif
        @endforeach
    </ul>
@endif

</body>
</html>
