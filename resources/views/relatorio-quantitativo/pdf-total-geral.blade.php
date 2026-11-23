@extends('layouts.pdf-alfa-eja-landscape')

@section('title', 'Total Geral de Participantes')

@section('styles')
    .table-header {
        font-size: 10px;
        font-weight: bold;
        background-color: #421944;
        color: #ffffff;
        padding: 5px 6px;
    }
    .table-header-group {
        font-size: 9px;
        font-weight: bold;
        background-color: #5a2b5c;
        color: #ffffff;
        padding: 4px 6px;
        text-align: center;
    }
    .table-data {
        font-size: 9px;
        padding: 3px 6px;
    }
    .text-end { text-align: right; }
    .text-center { text-align: center; }
    .unidentified-row { background-color: #f8f5f0; font-size: 9px; }
    .total-row { background-color: #ece3ee; font-weight: bold; font-size: 9px; }
@endsection

@section('content')
@php
    $dimensoes = $dimensoes ?? [];
    $fmtPct = fn($v) => $v > 0 ? number_format($v, 1, ',', '.') . '%' : '—';

    $partes = [];
    if (request('evento_id')) {
        $evento = \App\Models\Evento::find(request('evento_id'));
        if ($evento) $partes[] = 'da ação <strong>' . e($evento->nome) . '</strong>';
    }
    if (request('regiao_id')) {
        $regiao = \App\Models\Regiao::find(request('regiao_id'));
        if ($regiao) $partes[] = 'na região <strong>' . e($regiao->nome) . '</strong>';
    }
    if (request('de') || request('ate')) {
        $de = request('de') ? \Carbon\Carbon::parse(request('de'))->format('d/m/Y') : '';
        $ate = request('ate') ? \Carbon\Carbon::parse(request('ate'))->format('d/m/Y') : '';
        $intervalo = ($de && $ate) ? "de $de a $ate" : ($de ? "a partir de $de" : "até $ate");
        $partes[] = 'no período <strong>' . $intervalo . '</strong>';
    }

    $totalMunicipios = $totalGeral->filter(fn($r) => ! isset($r['_is_total']) && ! isset($r['_is_unidentified']) && ! isset($r['_is_brasil']))->count();
    $contexto = 'Exibindo o total de participantes em <strong>'.$totalMunicipios.'</strong> '.($totalMunicipios === 1 ? 'município' : 'municípios');
    $contexto .= count($partes) ? ' '.implode(', ', $partes) : '';
    $contexto .= '.';

    if (count($dimensoes)) {
        $labels = ['cpf' => 'CPF', 'raca_cor' => 'Raça/Cor', 'genero' => 'Gênero', 'pcd' => 'PcD', 'certificados' => 'Certificados', 'tag' => 'Tag'];
        $contexto .= ' Detalhando as dimensões: <strong>'.e(implode(', ', array_map(fn($d) => $labels[$d] ?? $d, $dimensoes))).'</strong>.';
    }
@endphp

<x-pdf.header title="Total Geral de Participantes">
    {!! $contexto !!}
</x-pdf.header>

@if($totalGeral->filter(fn($r) => !isset($r['_is_total']))->isEmpty())
    <p style="text-align:center;color:#666;">Nenhum dado encontrado com os filtros aplicados.</p>
@else
<table style="width:100%;border-collapse:collapse;border:1px solid #ddd;">
    <thead>
        <tr>
            <th class="table-header" rowspan="2">Região</th>
            <th class="table-header" rowspan="2">Município</th>
            <th class="table-header text-end" rowspan="2">Previstos</th>
            <th class="table-header text-end" rowspan="2">Participantes distintos</th>
            @if(in_array('cpf', $dimensoes))
            <th class="table-header-group" colspan="3">CPF</th>
            @endif
            @if(in_array('raca_cor', $dimensoes))
            <th class="table-header-group" colspan="10">Raça/Cor</th>
            @endif
            @if(in_array('genero', $dimensoes))
            <th class="table-header-group" colspan="6">Gênero</th>
            @endif
            @if(in_array('pcd', $dimensoes))
            <th class="table-header-group" colspan="2">PcD</th>
            @endif
            @if(in_array('certificados', $dimensoes))
            <th class="table-header-group" colspan="2">Certificados</th>
            @endif
            @if(in_array('tag', $dimensoes))
            <th class="table-header-group" colspan="4">Tag</th>
            @endif
        </tr>
        <tr>
            @if(in_array('cpf', $dimensoes))
            <th class="table-header text-end">Com CPF</th>
            <th class="table-header text-end">Sem CPF</th>
            <th class="table-header text-end">% Com CPF</th>
            @endif
            @if(in_array('raca_cor', $dimensoes))
            <th class="table-header text-end">Branca</th><th class="table-header text-end">%</th>
            <th class="table-header text-end">Parda</th><th class="table-header text-end">%</th>
            <th class="table-header text-end">Preta</th><th class="table-header text-end">%</th>
            <th class="table-header text-end">Amarela</th><th class="table-header text-end">%</th>
            <th class="table-header text-end">Indígena</th><th class="table-header text-end">%</th>
            @endif
            @if(in_array('genero', $dimensoes))
            <th class="table-header text-end">Mulheres</th><th class="table-header text-end">%</th>
            <th class="table-header text-end">Homens</th><th class="table-header text-end">%</th>
            <th class="table-header text-end">Outros/NB</th><th class="table-header text-end">%</th>
            @endif
            @if(in_array('pcd', $dimensoes))
            <th class="table-header text-end">Qtd</th><th class="table-header text-end">%</th>
            @endif
            @if(in_array('certificados', $dimensoes))
            <th class="table-header text-end">Qtd</th><th class="table-header text-end">%</th>
            @endif
            @if(in_array('tag', $dimensoes))
            <th class="table-header text-end">Rede Ensino</th><th class="table-header text-end">%</th>
            <th class="table-header text-end">Mov. Social</th><th class="table-header text-end">%</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @foreach($totalGeral as $row)
        @php $tp = $row['metricas']['total_presentes']; @endphp
        @if(isset($row['_is_total']))
        <tr class="total-row">
            <td colspan="2" style="text-align:right;padding:3px;">{{ $row['municipio_nome'] }}</td>
            <td class="text-end" style="padding:3px;">{{ $row['previstos'] ?: '—' }}</td>
            <td class="text-end" style="padding:3px;">{{ $tp ?: '—' }}</td>
            @if(in_array('cpf', $dimensoes))
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['cpf']['com'] }}</td>
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['cpf']['sem'] }}</td>
            <td class="text-end" style="padding:3px;">{{ $tp > 0 ? $fmtPct($row['metricas']['cpf']['pct']) : '—' }}</td>
            @endif
            @if(in_array('raca_cor', $dimensoes))
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['raca_cor']['branca'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['raca_cor']['pct_branca']) }}</td>
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['raca_cor']['parda'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['raca_cor']['pct_parda']) }}</td>
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['raca_cor']['preta'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['raca_cor']['pct_preta']) }}</td>
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['raca_cor']['amarela'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['raca_cor']['pct_amarela']) }}</td>
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['raca_cor']['indigena'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['raca_cor']['pct_indigena']) }}</td>
            @endif
            @if(in_array('genero', $dimensoes))
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['genero']['mulheres'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['genero']['pct_mulheres']) }}</td>
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['genero']['homens'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['genero']['pct_homens']) }}</td>
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['genero']['outros'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['genero']['pct_outros']) }}</td>
            @endif
            @if(in_array('pcd', $dimensoes))
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['pcd']['n'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['pcd']['pct']) }}</td>
            @endif
            @if(in_array('certificados', $dimensoes))
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['certificados']['n'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['certificados']['pct']) }}</td>
            @endif
            @if(in_array('tag', $dimensoes))
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['tag']['rede_ensino'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['tag']['pct_rede_ensino']) }}</td>
            <td class="text-end" style="padding:3px;">{{ $row['metricas']['tag']['movimento_social'] }}</td><td class="text-end" style="padding:3px;">{{ $fmtPct($row['metricas']['tag']['pct_movimento_social']) }}</td>
            @endif
        </tr>
        @elseif(isset($row['_is_unidentified']) || isset($row['_is_brasil']))
        <tr class="unidentified-row" style="border-bottom:1px solid #ddd;">
            <td colspan="2" class="table-data">{{ $row['municipio_nome'] }}</td>
            <td class="table-data text-end">{{ $row['previstos'] ?: '—' }}</td>
            <td class="table-data text-end">{{ $tp ?: '—' }}</td>
            @if(in_array('cpf', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['cpf']['com'] }}</td>
            <td class="table-data text-end">{{ $row['metricas']['cpf']['sem'] }}</td>
            <td class="table-data text-end">{{ $tp > 0 ? $fmtPct($row['metricas']['cpf']['pct']) : '—' }}</td>
            @endif
            @if(in_array('raca_cor', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['raca_cor']['branca'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['raca_cor']['pct_branca']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['raca_cor']['parda'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['raca_cor']['pct_parda']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['raca_cor']['preta'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['raca_cor']['pct_preta']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['raca_cor']['amarela'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['raca_cor']['pct_amarela']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['raca_cor']['indigena'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['raca_cor']['pct_indigena']) }}</td>
            @endif
            @if(in_array('genero', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['genero']['mulheres'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['genero']['pct_mulheres']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['genero']['homens'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['genero']['pct_homens']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['genero']['outros'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['genero']['pct_outros']) }}</td>
            @endif
            @if(in_array('pcd', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['pcd']['n'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['pcd']['pct']) }}</td>
            @endif
            @if(in_array('certificados', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['certificados']['n'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['certificados']['pct']) }}</td>
            @endif
            @if(in_array('tag', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['tag']['rede_ensino'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['tag']['pct_rede_ensino']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['tag']['movimento_social'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['tag']['pct_movimento_social']) }}</td>
            @endif
        </tr>
        @else
        <tr style="border-bottom:1px solid #ddd;">
            <td class="table-data">{{ $row['regiao'] }}</td>
            <td class="table-data">{{ $row['municipio_nome'] }}</td>
            <td class="table-data text-end">{{ $row['previstos'] ?: '—' }}</td>
            <td class="table-data text-end">{{ $tp ?: '—' }}</td>
            @if(in_array('cpf', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['cpf']['com'] }}</td>
            <td class="table-data text-end">{{ $row['metricas']['cpf']['sem'] }}</td>
            <td class="table-data text-end">{{ $tp > 0 ? $fmtPct($row['metricas']['cpf']['pct']) : '—' }}</td>
            @endif
            @if(in_array('raca_cor', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['raca_cor']['branca'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['raca_cor']['pct_branca']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['raca_cor']['parda'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['raca_cor']['pct_parda']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['raca_cor']['preta'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['raca_cor']['pct_preta']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['raca_cor']['amarela'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['raca_cor']['pct_amarela']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['raca_cor']['indigena'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['raca_cor']['pct_indigena']) }}</td>
            @endif
            @if(in_array('genero', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['genero']['mulheres'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['genero']['pct_mulheres']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['genero']['homens'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['genero']['pct_homens']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['genero']['outros'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['genero']['pct_outros']) }}</td>
            @endif
            @if(in_array('pcd', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['pcd']['n'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['pcd']['pct']) }}</td>
            @endif
            @if(in_array('certificados', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['certificados']['n'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['certificados']['pct']) }}</td>
            @endif
            @if(in_array('tag', $dimensoes))
            <td class="table-data text-end">{{ $row['metricas']['tag']['rede_ensino'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['tag']['pct_rede_ensino']) }}</td>
            <td class="table-data text-end">{{ $row['metricas']['tag']['movimento_social'] }}</td><td class="table-data text-end">{{ $fmtPct($row['metricas']['tag']['pct_movimento_social']) }}</td>
            @endif
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
@endif
@endsection
