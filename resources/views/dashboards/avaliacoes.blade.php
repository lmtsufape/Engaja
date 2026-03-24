@extends('layouts.app')

@section('content')
<div class="container py-4" id="avaliacoes-dashboard" data-endpoint="{{ route('dashboards.avaliacoes.data', request()->only(['fonte', 'survey_id'])) }}">
  <div class="mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
      <div>
        <p class="text-uppercase small text-muted mb-1">Dashboards</p>
        <h1 class="h3 fw-bold mb-1">Respostas dos formularios</h1>
        <p class="text-muted mb-0">Visual limpo, na paleta do projeto, com filtros instantaneos.</p>
      </div>
      <div class="d-flex gap-2">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Hub de dashboards</a>
        <a href="{{ route('dashboards.bi') }}" class="btn btn-outline-secondary">Ir para BI</a>
        <a href="{{ route('dashboards.presencas') }}" class="btn btn-outline-primary">Ir para presencas</a>
      </div>
    </div>
  </div>

  @if(request('fonte') === 'limesurvey')
    <div class="alert alert-info border-0 shadow-sm py-2">
      Fonte ativa: <strong>LimeSurvey</strong>
      @if(request('survey_id'))
        (survey_id={{ request('survey_id') }})
      @endif
    </div>
  @endif

  @include('dashboards.avaliacoes._filtros')
  @include('dashboards.avaliacoes._cards-totais')
  @include('dashboards.avaliacoes._bi-matriz')

  <div class="row g-3">
    <div class="col-12">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h5 fw-bold mb-0">Distribuicao por questao</h2>
        <span class="badge bg-primary-subtle text-primary">Interativo</span>
      </div>
      <div class="row g-3" id="cards-questoes">
        <div class="col-12" id="placeholder-card">
          <div class="card border-0 shadow-sm">
            <div class="card-body text-center text-muted">
              Carregando graficos...
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@include('dashboards.avaliacoes._modal-respostas')

<style>
  @media (max-width: 576px) {
    #cards-questoes .question-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }
    #cards-questoes .question-controls {
      width: 100%;
      justify-content: flex-start;
    }
    #cards-questoes .question-controls select {
      width: 100%;
      max-width: none;
    }
  }
</style>

@vite('resources/js/dashboards/avaliacoes.js')
@endsection
