@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<div class="container py-4">

    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <p class="text-uppercase small text-muted mb-1">Relatórios</p>
            <h1 class="h4 mb-0">Painel Gerencial de Quantitativos</h1>
            <p class="text-muted small mb-0">Quadro de referência: público esperado × inscritos × presentes × avaliações, por Ação Pedagógica.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('painel-gerencial.exportar') }}?{{ http_build_query(array_merge(request()->query(), ['formato' => 'pdf'])) }}" class="btn btn-sm btn-outline-danger" title="Exportar como PDF">
                <i class="bi bi-filetype-pdf"></i> PDF
            </a>
            <a href="{{ route('painel-gerencial.exportar') }}?{{ http_build_query(array_merge(request()->query(), ['formato' => 'xlsx'])) }}" class="btn btn-sm btn-outline-success" title="Exportar como Excel">
                <i class="bi bi-file-earmark-spreadsheet"></i> Excel
            </a>
            <a href="{{ route('painel-gerencial.exportar') }}?{{ http_build_query(array_merge(request()->query(), ['formato' => 'docx'])) }}" class="btn btn-sm btn-outline-primary" title="Exportar como Word">
                <i class="bi bi-file-earmark-word"></i> Word
            </a>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('painel-gerencial.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3 col-lg-3">
                    <label class="form-label mb-1 small fw-semibold">Ação</label>
                    <select name="evento_id" id="filter-evento" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach($eventos as $id => $nome)
                            <option value="{{ $id }}" @selected(request('evento_id') == $id)>{{ $nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label mb-1 small fw-semibold">Região</label>
                    <select name="regiao_id" class="form-select form-select-sm">
                        <option value="">Todas</option>
                        @foreach($regioes as $regiao)
                            <option value="{{ $regiao->id }}" @selected(request('regiao_id') == $regiao->id)>{{ $regiao->nome }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-lg-3">
                    <label class="form-label mb-1 small fw-semibold">Município</label>
                    <select name="municipio_id" id="filter-municipio" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        @foreach($municipios as $municipio)
                            <option value="{{ $municipio->id }}" @selected(request('municipio_id') == $municipio->id)>{{ $municipio->nome_com_estado }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label class="form-label mb-1 small fw-semibold">Intervalo</label>
                    <input type="text" id="filter-daterange" class="form-control form-control-sm" placeholder="De ... até">
                    <input type="hidden" name="de" id="filter-de" value="{{ request('de') }}">
                    <input type="hidden" name="ate" id="filter-ate" value="{{ request('ate') }}">
                </div>
                <div class="col-md-2 col-lg-2">
                    <label class="form-label mb-1 small fw-semibold">Período</label>
                    <select name="periodo" class="form-select form-select-sm">
                        <option value="">Todos</option>
                        <option value="manha" @selected(request('periodo') == 'manha')>Manhã</option>
                        <option value="tarde" @selected(request('periodo') == 'tarde')>Tarde</option>
                        <option value="noite" @selected(request('periodo') == 'noite')>Noite</option>
                    </select>
                </div>
                <div class="col-auto d-flex gap-2">
                    <button type="submit" class="btn btn-sm text-white" style="background-color:#421944;">Filtrar</button>
                    <a href="{{ route('painel-gerencial.index') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    {{-- KPIs --}}
    @php
        $cards = [
            ['Municípios ativos', $kpis['municipios_ativos'], 'bi-geo-alt'],
            ['Participantes totais', $kpis['participantes_totais'], 'bi-people'],
            ['Participantes únicos', $kpis['participantes_unicos'], 'bi-person-check'],
            ['Eventos realizados', $kpis['eventos_realizados'], 'bi-calendar-event'],
            ['Horas presenciais', $kpis['horas_presenciais'], 'bi-building'],
            ['Horas EaD', $kpis['horas_ead'], 'bi-laptop'],
            ['Certificados emitidos', $kpis['certificados_emitidos'], 'bi-award'],
            ['Avaliações respondidas', $kpis['avaliacoes_respondidas'], 'bi-clipboard-check'],
            ['Pendências de documentação', $kpis['pendencias_documentacao'], 'bi-exclamation-triangle'],
        ];
    @endphp
    <div class="row g-3 mb-4">
        @foreach($cards as [$label, $valor, $icon])
            <div class="col-6 col-md-4 col-lg-3 col-xl-2">
                <div class="card shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="text-muted small mb-1"><i class="bi {{ $icon }}"></i> {{ $label }}</div>
                        <div class="h4 mb-0">{{ is_float($valor) ? number_format($valor, 1, ',', '.') : number_format($valor, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Gráficos --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h6">Metas previstas × realizadas (por ação)</h2>
                <div style="position:relative;height:300px"><canvas id="chartMetas"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h6">Participação por região</h2>
                <div style="position:relative;height:300px"><canvas id="chartRegiao"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h6">Comparação entre segmentos</h2>
                <div style="position:relative;height:300px"><canvas id="chartSegmentos"></canvas></div>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h6">Evolução semestral</h2>
                <div style="position:relative;height:300px"><canvas id="chartEvolucao"></canvas></div>
            </div></div>
        </div>
    </div>

    {{-- Metas por ação (tabela) --}}
    @php
        $colunasMetas = [
            ['field' => 'acao', 'headerName' => 'Ação', 'flex' => 2],
            ['field' => 'previstas', 'headerName' => 'Previstas', 'flex' => 1, 'align' => 'end'],
            ['field' => 'inscritos', 'headerName' => 'Inscritos', 'flex' => 1, 'align' => 'end'],
            ['field' => 'presentes', 'headerName' => 'Presentes', 'flex' => 1, 'align' => 'end'],
            ['field' => 'avaliacoes', 'headerName' => 'Avaliações', 'flex' => 1, 'align' => 'end'],
            ['field' => 'pct_realizado', 'headerName' => '% Realizado', 'flex' => 1, 'align' => 'end'],
        ];
        $linhasMetas = collect($metas_por_acao)->map(fn ($r) => [
            'acao' => $r['acao'],
            'previstas' => $r['previstas'],
            'inscritos' => $r['inscritos'],
            'presentes' => $r['presentes'],
            'avaliacoes' => $r['avaliacoes'],
            'pct_realizado' => number_format($r['pct_realizado'], 1, ',', '.') . '%',
        ])->values();
    @endphp
    <div class="card shadow-sm mb-4"><div class="card-body">
        <h2 class="h6 mb-3">Metas por Ação Pedagógica</h2>
        <x-data-table class="dt-no-border" id="grid-painel-metas" :columns="$colunasMetas" :rows="$linhasMetas" :pagination="false" dom-layout="normal" style="height: 440px" />
    </div></div>

    {{-- Listas de acompanhamento --}}
    @php
        $colunasBaixoEngajamento = [
            ['field' => 'municipio', 'headerName' => 'Município', 'flex' => 2, 'html' => true],
            ['field' => 'pct_realizado', 'headerName' => '% Realizado', 'flex' => 1, 'align' => 'end'],
        ];
        $linhasBaixoEngajamento = collect($municipios_baixo_engajamento)->map(fn ($r) => [
            'municipio' => e($r['municipio']) . ' <span class="text-muted small">(' . e($r['regiao']) . ')</span>',
            'pct_realizado' => '<span class="text-danger">' . number_format($r['pct_realizado'], 1, ',', '.') . '%</span>',
        ])->values();

        $colunasSemAvaliacao = [
            ['field' => 'acao_momento', 'headerName' => 'Ação / Momento', 'flex' => 2, 'html' => true],
            ['field' => 'municipio', 'headerName' => 'Município', 'flex' => 1],
        ];
        $linhasSemAvaliacao = collect($eventos_sem_avaliacao)->map(fn ($r) => [
            'acao_momento' => e($r['acao']) . '<br><span class="text-muted small">' . e($r['momento']) . '</span>',
            'municipio' => $r['municipio'],
        ])->values();

        $colunasRecorrenciaAusencia = [
            ['field' => 'participante', 'headerName' => 'Participante', 'flex' => 2, 'html' => true],
            ['field' => 'ausencias', 'headerName' => 'Ausências', 'flex' => 1, 'align' => 'end'],
        ];
        $linhasRecorrenciaAusencia = collect($recorrencia_ausencia)->map(fn ($r) => [
            'participante' => e($r['participante']) . ' <span class="text-muted small">(' . e($r['municipio']) . ')</span>',
            'ausencias' => $r['ausencias'],
        ])->values();
    @endphp
    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h6 mb-3">Municípios com baixo engajamento</h2>
                <x-data-table class="dt-no-border" id="grid-painel-baixo-engajamento" :columns="$colunasBaixoEngajamento" :rows="$linhasBaixoEngajamento" :pagination="false" dom-layout="normal" style="height: 340px" />
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h6 mb-3">Eventos sem avaliação registrada</h2>
                <x-data-table class="dt-no-border" id="grid-painel-sem-avaliacao" :columns="$colunasSemAvaliacao" :rows="$linhasSemAvaliacao" :pagination="false" dom-layout="normal" style="height: 340px" />
            </div></div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100"><div class="card-body">
                <h2 class="h6 mb-3">Participantes com recorrência de ausência</h2>
                <x-data-table class="dt-no-border" id="grid-painel-recorrencia-ausencia" :columns="$colunasRecorrenciaAusencia" :rows="$linhasRecorrenciaAusencia" :pagination="false" dom-layout="normal" style="height: 340px" />
            </div></div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/plugins/rangePlugin.js"></script>
<script>
(function () {
    const metas      = @json($metas_por_acao);
    const regioes    = @json($participacao_por_regiao);
    const segmentos  = @json($segmentos);
    const evolucao   = @json($evolucao_semestral);
    const cor = '#421944';
    const cor2 = '#b07cb3';

    new Chart(document.getElementById('chartMetas'), {
        type: 'bar',
        data: {
            labels: metas.map(m => m.acao),
            datasets: [
                { label: 'Previstas', data: metas.map(m => m.previstas), backgroundColor: cor2 },
                { label: 'Presentes', data: metas.map(m => m.presentes), backgroundColor: cor },
            ],
        },
        options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { position: 'bottom' } } },
    });

    new Chart(document.getElementById('chartRegiao'), {
        type: 'bar',
        data: {
            labels: regioes.map(r => r.regiao),
            datasets: [
                { label: 'Previstas', data: regioes.map(r => r.previstas), backgroundColor: cor2 },
                { label: 'Presentes', data: regioes.map(r => r.presentes), backgroundColor: cor },
            ],
        },
        options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { position: 'bottom' } } },
    });

    new Chart(document.getElementById('chartSegmentos'), {
        type: 'doughnut',
        data: {
            labels: segmentos.map(s => s.segmento),
            datasets: [{ data: segmentos.map(s => s.presentes), backgroundColor: ['#421944', '#b07cb3', '#e8daea', '#8a5a8c', '#cbb0cc'] }],
        },
        options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { position: 'bottom' } } },
    });

    new Chart(document.getElementById('chartEvolucao'), {
        type: 'line',
        data: {
            labels: evolucao.map(e => e.semestre),
            datasets: [
                { label: 'Presentes', data: evolucao.map(e => e.presentes), borderColor: cor, backgroundColor: 'rgba(66,25,68,0.15)', fill: true },
                { label: 'Avaliações', data: evolucao.map(e => e.avaliacoes), borderColor: cor2, backgroundColor: 'rgba(176,124,179,0.15)', fill: true },
            ],
        },
        options: { maintainAspectRatio: false, responsive: true, plugins: { legend: { position: 'bottom' } } },
    });

    // Flatpickr range
    const deInput = document.getElementById('filter-de');
    const ateInput = document.getElementById('filter-ate');
    flatpickr(document.getElementById('filter-daterange'), {
        mode: 'range', dateFormat: 'Y-m-d', locale: { rangeSeparator: ' até ' },
        defaultDate: [deInput.value, ateInput.value].filter(Boolean),
        onChange: function (selectedDates) {
            deInput.value  = selectedDates[0] ? flatpickr.formatDate(selectedDates[0], 'Y-m-d') : '';
            ateInput.value = selectedDates[1] ? flatpickr.formatDate(selectedDates[1], 'Y-m-d') : '';
        },
    });

    // Cascata: ao trocar a ação, recarrega municípios
    const eventoSelect = document.getElementById('filter-evento');
    const municipioSelect = document.getElementById('filter-municipio');
    if (eventoSelect) {
        eventoSelect.addEventListener('change', function () {
            const url = '{{ route('painel-gerencial.momentos') }}?evento_id=' + (this.value || '');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(data => {
                    const atual = municipioSelect.value;
                    municipioSelect.innerHTML = '<option value="">Todos</option>';
                    data.municipios.forEach(m => {
                        const opt = document.createElement('option');
                        opt.value = m.id; opt.textContent = m.nome;
                        if (String(m.id) === atual) opt.selected = true;
                        municipioSelect.appendChild(opt);
                    });
                });
        });
    }
})();
</script>
@endsection
