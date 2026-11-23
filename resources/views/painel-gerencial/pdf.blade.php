@extends('layouts.pdf-alfa-eja-landscape')

@section('title', 'Painel Gerencial de Quantitativos')

@section('styles')
    /* Conteúdo longo / multipágina: sobrescreve o body centralizado do layout. */
    body { display: block; height: auto; font-size: 9px; color: #222; }
    h2 { font-size: 11px; color: #421944; margin: 10px 0 4px; }
    th, td { padding: 4px 6px; text-align: left; }
    th { background: #421944; color: #fff; font-weight: 700; }
    td { border-bottom: 1px solid #e5e7eb; }
    tbody tr:nth-child(even) td { background: #f9fafb; }
    table { margin-bottom: 6px; }
    td.num, th.num { text-align: right; }
    .kpis td { width: 25%; border-bottom: none; }
    .kpis tr:nth-child(even) td { background: transparent; }
    .kpis .label { color: #666; font-size: 8px; }
    .kpis .valor { font-size: 13px; font-weight: 700; color: #421944; }
    .vazio { color: #888; font-style: italic; }
@endsection

@section('content')
    @php
        $modelos = [
            'Ação' => 'da ação %s',
            'Município' => 'no município de %s',
            'Região' => 'na região %s',
            'Período' => 'no período %s',
            'Turno' => 'no turno da %s',
        ];
        $partes = [];
        foreach ($filtros ?? [] as $rotulo => $valor) {
            $modelo = $modelos[$rotulo] ?? ($rotulo . ' %s');
            $partes[] = sprintf($modelo, '<strong>' . e($valor) . '</strong>');
        }
        $contexto = count($partes)
            ? 'Exibindo os quantitativos ' . implode(', ', $partes) . '.'
            : 'Exibindo os quantitativos de todas as ações pedagógicas (sem filtros aplicados).';
    @endphp

    <x-pdf.header title="Painel Gerencial de Quantitativos" subtitle="Projeto Alfa-EJA">
        {!! $contexto !!}
    </x-pdf.header>

    <h2>Resumo</h2>
    <table class="kpis">
        <tr>
            <td><div class="label">Municípios ativos</div><div class="valor">{{ $kpis['municipios_ativos'] }}</div></td>
            <td><div class="label">Participantes totais</div><div class="valor">{{ $kpis['participantes_totais'] }}</div></td>
            <td><div class="label">Participantes únicos</div><div class="valor">{{ $kpis['participantes_unicos'] }}</div></td>
            <td><div class="label">Eventos realizados</div><div class="valor">{{ $kpis['eventos_realizados'] }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Horas presenciais</div><div class="valor">{{ number_format($kpis['horas_presenciais'], 1, ',', '.') }}</div></td>
            <td><div class="label">Horas EaD</div><div class="valor">{{ number_format($kpis['horas_ead'], 1, ',', '.') }}</div></td>
            <td><div class="label">Certificados emitidos</div><div class="valor">{{ $kpis['certificados_emitidos'] }}</div></td>
            <td><div class="label">Avaliações respondidas</div><div class="valor">{{ $kpis['avaliacoes_respondidas'] }}</div></td>
        </tr>
        <tr>
            <td><div class="label">Pendências de documentação</div><div class="valor">{{ $kpis['pendencias_documentacao'] }}</div></td>
            <td></td><td></td><td></td>
        </tr>
    </table>

    <h2>Metas por Ação Pedagógica</h2>
    <table>
        <thead><tr>
            <th>Ação</th><th class="num">Previstas</th><th class="num">Inscritos</th>
            <th class="num">Presentes</th><th class="num">Avaliações</th><th class="num">% Realizado</th>
        </tr></thead>
        <tbody>
            @forelse($metas_por_acao as $r)
                <tr>
                    <td>{{ $r['acao'] }}</td>
                    <td class="num">{{ $r['previstas'] }}</td>
                    <td class="num">{{ $r['inscritos'] }}</td>
                    <td class="num">{{ $r['presentes'] }}</td>
                    <td class="num">{{ $r['avaliacoes'] }}</td>
                    <td class="num">{{ number_format($r['pct_realizado'], 1, ',', '.') }}%</td>
                </tr>
            @empty
                <tr><td colspan="6" class="vazio">Sem dados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Participação por Região</h2>
    <table>
        <thead><tr><th>Região</th><th class="num">Previstas</th><th class="num">Presentes</th><th class="num">% Realizado</th></tr></thead>
        <tbody>
            @forelse($participacao_por_regiao as $r)
                <tr><td>{{ $r['regiao'] }}</td><td class="num">{{ $r['previstas'] }}</td><td class="num">{{ $r['presentes'] }}</td><td class="num">{{ number_format($r['pct_realizado'], 1, ',', '.') }}%</td></tr>
            @empty
                <tr><td colspan="4" class="vazio">Sem dados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Comparação entre Segmentos</h2>
    <table>
        <thead><tr><th>Segmento</th><th class="num">Presentes</th><th class="num">Participantes únicos</th></tr></thead>
        <tbody>
            @forelse($segmentos as $r)
                <tr><td>{{ $r['segmento'] }}</td><td class="num">{{ $r['presentes'] }}</td><td class="num">{{ $r['participantes_unicos'] }}</td></tr>
            @empty
                <tr><td colspan="3" class="vazio">Sem dados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Evolução Semestral</h2>
    <table>
        <thead><tr><th>Semestre</th><th class="num">Eventos</th><th class="num">Presentes</th><th class="num">Avaliações</th></tr></thead>
        <tbody>
            @forelse($evolucao_semestral as $r)
                <tr><td>{{ $r['semestre'] }}</td><td class="num">{{ $r['eventos'] }}</td><td class="num">{{ $r['presentes'] }}</td><td class="num">{{ $r['avaliacoes'] }}</td></tr>
            @empty
                <tr><td colspan="4" class="vazio">Sem dados.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Municípios com Baixo Engajamento</h2>
    <table>
        <thead><tr><th>Município</th><th>Região</th><th class="num">Previstas</th><th class="num">Presentes</th><th class="num">% Realizado</th></tr></thead>
        <tbody>
            @forelse($municipios_baixo_engajamento as $r)
                <tr><td>{{ $r['municipio'] }}</td><td>{{ $r['regiao'] }}</td><td class="num">{{ $r['previstas'] }}</td><td class="num">{{ $r['presentes'] }}</td><td class="num">{{ number_format($r['pct_realizado'], 1, ',', '.') }}%</td></tr>
            @empty
                <tr><td colspan="5" class="vazio">Nenhum.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Eventos sem Avaliação Registrada</h2>
    <table>
        <thead><tr><th>Ação</th><th>Momento</th><th>Município</th><th>Data</th></tr></thead>
        <tbody>
            @forelse($eventos_sem_avaliacao as $r)
                <tr><td>{{ $r['acao'] }}</td><td>{{ $r['momento'] }}</td><td>{{ $r['municipio'] }}</td><td>{{ \Illuminate\Support\Carbon::parse($r['dia'])->format('d/m/Y') }}</td></tr>
            @empty
                <tr><td colspan="4" class="vazio">Nenhum.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Participantes com Recorrência de Ausência</h2>
    <table>
        <thead><tr><th>Participante</th><th>Município</th><th class="num">Ausências</th></tr></thead>
        <tbody>
            @forelse($recorrencia_ausencia as $r)
                <tr><td>{{ $r['participante'] }}</td><td>{{ $r['municipio'] }}</td><td class="num">{{ $r['ausencias'] }}</td></tr>
            @empty
                <tr><td colspan="3" class="vazio">Nenhum.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
