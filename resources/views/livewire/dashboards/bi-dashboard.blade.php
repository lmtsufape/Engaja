<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
        <div>
            <p class="text-uppercase small text-muted mb-1">Dashboards</p>
            <h1 class="h4 mb-0">BI Educacional</h1>
            <p class="text-muted small mb-0">Painel de indicadores educacionais para análise territorial e acompanhamento anual.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Hub de dashboards</a>
            <a href="{{ route('dashboards.avaliacoes') }}" class="btn btn-outline-primary btn-sm">Dashboard de respostas</a>
            <a href="{{ route('dashboards.presencas') }}" class="btn btn-outline-success btn-sm">Dashboard de presenças</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="bi-ano" class="form-label mb-1">Ano do indicador</label>
                    <select id="bi-ano" wire:model.live="ano" class="form-select">
                        @forelse($anosDisponiveis as $itemAno)
                            <option value="{{ $itemAno }}">{{ $itemAno }}</option>
                        @empty
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endforelse
                    </select>
                </div>
                <div class="col-md-5">
                    <label for="bi-municipio" class="form-label mb-1">Município (gráficos por dimensão)</label>
                    <select id="bi-municipio" wire:model.live="municipioId" class="form-select">
                        <option value="">Todos os municípios</option>
                        @foreach($municipiosDisponiveis as $municipio)
                            <option value="{{ $municipio['id'] }}">{{ $municipio['nome'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if(empty($anosDisponiveis))
        <div class="alert alert-warning">
            Não há dados de BI carregados para exibição no dashboard.
        </div>
    @else
        @php($anoAtual = (int) $ano)
        @php($municipioIdAtual = $this->municipioIdNormalizado())
        @php($municipioSelecionadoNome = $this->municipioSelecionadoNome())
        @php($tituloRankingAnalfabetismo = "Ranking de não alfabetizados por município ({$anoAtual})")
        @php($tituloRankingEja = "Ranking de acesso à EJA por município ({$anoAtual})")
        @php($sufixoMunicipioDimensao = $municipioSelecionadoNome ? " - {$municipioSelecionadoNome}" : '')

        <livewire:graficos.ranking-municipios
            :indicador-percentual="'ANALFABETISMO_TAXA'"
            :indicador-absoluto="'ANALFABETISMO_QTDE'"
            :ano="$anoAtual"
            :titulo="$tituloRankingAnalfabetismo"
            :cor="'--engaja-purple'"
            :key="'ranking-analfabetismo-'.$anoAtual" />

        <livewire:graficos.ranking-municipios
            :indicador-percentual="'EJA_ACESSO_TAXA'"
            :indicador-absoluto="'EJA_MATRICULAS_QTDE'"
            :ano="$anoAtual"
            :titulo="$tituloRankingEja"
            :cor="'--engaja-blue'"
            :key="'ranking-eja-'.$anoAtual" />

        <div class="row g-3 mt-1">
            <div class="col-lg-4">
                <livewire:graficos.distribuicao-dimensao
                    :indicador="'ANALFABETISMO_QTDE'"
                    :ano="$anoAtual"
                    :municipio-id="$municipioIdAtual"
                    :dimensao="'SEXO'"
                    :titulo="'Distribuição por sexo'.$sufixoMunicipioDimensao"
                    :tipo-grafico="'donut'"
                    :key="'dimensao-sexo-'.$anoAtual.'-'.($municipioIdAtual ?? 'todos')" />
            </div>
            <div class="col-lg-4">
                <livewire:graficos.distribuicao-dimensao
                    :indicador="'ANALFABETISMO_QTDE'"
                    :ano="$anoAtual"
                    :municipio-id="$municipioIdAtual"
                    :dimensao="'RACA'"
                    :titulo="'Distribuição por raça'.$sufixoMunicipioDimensao"
                    :tipo-grafico="'polarArea'"
                    :key="'dimensao-raca-'.$anoAtual.'-'.($municipioIdAtual ?? 'todos')" />
            </div>
            <div class="col-lg-4">
                <livewire:graficos.distribuicao-dimensao
                    :indicador="'ANALFABETISMO_QTDE'"
                    :ano="$anoAtual"
                    :municipio-id="$municipioIdAtual"
                    :dimensao="'RESIDENCIA'"
                    :titulo="'Distribuição por residência'.$sufixoMunicipioDimensao"
                    :tipo-grafico="'bar'"
                    :mostrar-valores="false"
                    :usar-percentual="true"
                    :casas-decimais-percentual="0"
                    :key="'dimensao-residencia-'.$anoAtual.'-'.($municipioIdAtual ?? 'todos')" />
            </div>
        </div>
    @endif
</div>
