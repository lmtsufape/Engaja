@extends('layouts.pdf-alfa-eja-landscape')

@section('title', 'Relatório de Participação e Avaliação por Encontro')

@section('content')
{{-- Contexto do relatório em prosa natural --}}
@php
    $partes = [];
    if (request('evento_id')) {
        $evento = \App\Models\Evento::find(request('evento_id'));
        if ($evento) $partes[] = 'da ação <strong>' . e($evento->nome) . '</strong>';
    }
    if (request('descricao')) {
        $partes[] = 'do momento <strong>' . e(request('descricao')) . '</strong>';
    }
    if (request('regiao_id')) {
        $regiao = \App\Models\Regiao::find(request('regiao_id'));
        if ($regiao) $partes[] = 'na região <strong>' . e($regiao->nome) . '</strong>';
    }
    if (request('municipio_id')) {
        $municipio = \App\Models\Municipio::find(request('municipio_id'));
        if ($municipio) $partes[] = 'no município de <strong>' . e($municipio->nome) . '</strong>';
    }
    if (request('de') || request('ate')) {
        $de = request('de') ? \Carbon\Carbon::parse(request('de'))->format('d/m/Y') : '';
        $ate = request('ate') ? \Carbon\Carbon::parse(request('ate'))->format('d/m/Y') : '';
        $intervalo = ($de && $ate) ? "de $de a $ate" : ($de ? "a partir de $de" : "até $ate");
        $partes[] = 'no período <strong>' . $intervalo . '</strong>';
    }
    if (request('periodo')) {
        $periodos = ['manha' => 'manhã', 'tarde' => 'tarde', 'noite' => 'noite'];
        $partes[] = 'no turno da <strong>' . ($periodos[request('periodo')] ?? e(request('periodo'))) . '</strong>';
    }

    $total = $atividades->count();
    $contexto = 'Exibindo <strong>'.$total.'</strong> '.($total === 1 ? 'encontro' : 'encontros');
    $contexto .= count($partes) ? ' '.implode(', ', $partes) : ' (sem filtros aplicados)';
    $contexto .= '.';
@endphp

<x-pdf.header title="Relatório de Participação e Avaliação por Encontro">
    {!! $contexto !!}
</x-pdf.header>

@if($atividades->isEmpty())
    <p style="text-align: center; color: #666;">Nenhum encontro encontrado com os filtros aplicados.</p>
@else
<table class="pdf-table pdf-table--compact">
    <thead>
        <tr>
            <th>Ação</th>
            <th>Momento</th>
            <th>Município</th>
            <th>Data</th>
            <th>Período</th>
            <th class="text-end">Qtd Previstas</th>
            <th class="text-end">Qtd Presentes</th>
            <th class="text-end">% Presentes</th>
            <th class="text-end">Qtd Avaliações</th>
            <th class="text-end">% Avaliações</th>
        </tr>
    </thead>
    <tbody>
        @foreach($atividades->groupBy('evento_nome') as $nomeAcao => $grupo)
            @foreach($grupo as $a)
            @php
                $horaStr = substr($a->hora_inicio ?? '', 0, 5);
                $hora = (int) substr($horaStr, 0, 2);
                $periodoLabel = $hora < 12 ? 'Manhã' : ($hora < 18 ? 'Tarde' : 'Noite');

                $previstas = (int) $a->publico_esperado;
                $presentes = (int) $a->presentes_count;
                $avaliacoes = (int) $a->avaliacoes_count;

                $propPres = $previstas > 0 ? round($presentes / $previstas * 100, 1) : 0;
                $propAval = $presentes > 0 ? round($avaliacoes / $presentes * 100, 1) : 0;
            @endphp
            <tr>
                <td>{{ $a->evento_nome ?? '—' }}</td>
                <td>{{ $a->descricao ?? '—' }}</td>
                <td>{{ $a->municipio_nome ?? '—' }}</td>
                <td>{{ $a->dia ? \Carbon\Carbon::parse($a->dia)->format('d/m/Y') : '—' }}</td>
                <td>{{ $horaStr ? $periodoLabel . ' (' . $horaStr . ')' : '—' }}</td>
                <td class="text-end">{{ $previstas ?: '—' }}</td>
                <td class="text-end">{{ $presentes }}</td>
                <td class="text-end">{{ $previstas > 0 ? $propPres . '%' : '—' }}</td>
                <td class="text-end">{{ $avaliacoes }}</td>
                <td class="text-end">{{ $presentes > 0 ? $propAval . '%' : '—' }}</td>
            </tr>
            @endforeach

            @php
                $totalPrevistas = $grupo->sum('publico_esperado');
                $totalPresentes = $grupo->sum('presentes_count');
                $totalAvaliacoes = $grupo->sum('avaliacoes_count');
                $propTotPres = $totalPrevistas > 0 ? round($totalPresentes / $totalPrevistas * 100, 1) : 0;
                $propTotAval = $totalPresentes > 0 ? round($totalAvaliacoes / $totalPresentes * 100, 1) : 0;
            @endphp
            <tr class="subtotal-row">
                <td colspan="5" style="text-align: right;">Subtotal — {{ $nomeAcao ?? 'Sem ação' }}</td>
                <td class="text-end">{{ $totalPrevistas ?: '—' }}</td>
                <td class="text-end">{{ $totalPresentes }}</td>
                <td class="text-end">{{ $totalPrevistas > 0 ? $propTotPres . '%' : '—' }}</td>
                <td class="text-end">{{ $totalAvaliacoes }}</td>
                <td class="text-end">{{ $totalPresentes > 0 ? $propTotAval . '%' : '—' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endif
@endsection
