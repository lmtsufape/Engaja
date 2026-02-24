@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <p class="text-uppercase small text-muted mb-1">Dashboards</p>
            <h1 class="h4 mb-0">BI educacional</h1>
            <p class="text-muted small mb-0">Primeiro painel de indicadores com Livewire e ApexCharts.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Hub de dashboards</a>
            <a href="{{ route('dashboards.avaliacoes') }}" class="btn btn-outline-primary btn-sm">Dashboard de respostas</a>
            <a href="{{ route('dashboards.presencas') }}" class="btn btn-outline-success btn-sm">Dashboard de presencas</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('dashboards.bi') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="bi-ano" class="form-label mb-1">Ano do indicador</label>
                    <select id="bi-ano" name="ano" class="form-select">
                        @forelse($anosDisponiveis as $itemAno)
                            <option value="{{ $itemAno }}" @selected($itemAno === $ano)>{{ $itemAno }}</option>
                        @empty
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="bi-municipio" class="form-label mb-1">Municipio (graficos por dimensao)</label>
                    <select id="bi-municipio" name="municipio_id" class="form-select">
                        <option value="">Todos os municipios</option>
                        @foreach($municipiosDisponiveis as $municipio)
                            <option value="{{ $municipio->id }}" @selected((int) $municipio->id === (int) ($municipioId ?? 0))>
                                {{ $municipio->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">Atualizar painel</button>
                </div>
            </form>
        </div>
    </div>

    @if($anosDisponiveis->isEmpty())
        <div class="alert alert-warning">
            Nao ha dados BI carregados para exibicao no dashboard.
        </div>
    @else
        @php($tituloGrafico = "Ranking de taxa de analfabetismo por municipios ({$ano})")
        @php($sufixoMunicipioDimensao = $municipioSelecionado?->nome ? " - {$municipioSelecionado->nome}" : '')
        <livewire:graficos.ranking-municipios :indicador="$indicador" :ano="$ano" :titulo="$tituloGrafico" />

        <div class="row g-3 mt-1">
            <div class="col-lg-4">
                <livewire:graficos.distribuicao-dimensao
                    :indicador="'ANALFABETISMO_QTDE'"
                    :ano="$ano"
                    :municipio-id="$municipioId"
                    :dimensao="'SEXO'"
                    :titulo="'Distribuicao por sexo'.$sufixoMunicipioDimensao"
                    :tipo-grafico="'donut'"
                    :key="'dimensao-sexo-'.$ano.'-'.($municipioId ?? 'todos')" />
            </div>
            <div class="col-lg-4">
                <livewire:graficos.distribuicao-dimensao
                    :indicador="'ANALFABETISMO_QTDE'"
                    :ano="$ano"
                    :municipio-id="$municipioId"
                    :dimensao="'RACA'"
                    :titulo="'Distribuicao por raca'.$sufixoMunicipioDimensao"
                    :tipo-grafico="'polarArea'"
                    :key="'dimensao-raca-'.$ano.'-'.($municipioId ?? 'todos')" />
            </div>
            <div class="col-lg-4">
                <livewire:graficos.distribuicao-dimensao
                    :indicador="'ANALFABETISMO_QTDE'"
                    :ano="$ano"
                    :municipio-id="$municipioId"
                    :dimensao="'RESIDENCIA'"
                    :titulo="'Distribuicao por residencia'.$sufixoMunicipioDimensao"
                    :tipo-grafico="'bar'"
                    :mostrar-valores="false"
                    :usar-percentual="true"
                    :casas-decimais-percentual="0"
                    :key="'dimensao-residencia-'.$ano.'-'.($municipioId ?? 'todos')" />
            </div>
        </div>
    @endif

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-uppercase small text-muted mb-1">Proximas etapas</p>
                    <h2 class="h6 fw-bold">Comparativos planejados</h2>
                    <p class="text-muted mb-0">Serie temporal por ano, comparativo entre municipios e distribuicoes por dimensao.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-uppercase small text-muted mb-1">Status</p>
                    <h2 class="h6 fw-bold">Base pronta para novos graficos</h2>
                    <p class="text-muted mb-0">Template Apex padronizado, cache de dados e renderizacao Livewire idempotente.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
