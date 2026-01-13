@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="card shadow-sm border-0 mb-4" style="background:#ecdeec;">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-start gap-3">
      <div>
        <p class="text-uppercase small mb-1" style="color:#421944;">Dashboards</p>
        <h1 class="h4 fw-bold mb-1" style="color:#421944;">Escolha o painel</h1>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="{{ route('dashboards.avaliacoes') }}">
          Respostas de formularios
        </a>
        <a class="btn btn-primary" href="{{ route('dashboards.presencas') }}">
          Presencas e inscricoes
        </a>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <p class="text-uppercase text-primary small fw-semibold mb-1">Respostas</p>
              <h2 class="h5 fw-bold">Painel estilo Forms</h2>
              <p class="text-muted mb-3">
                Veja medias, distribuicoes e respostas abertas com graficos dinamicos.
                Troque de modelo, ação pedagógica ou periodo.
              </p>
              <a href="{{ route('dashboards.avaliacoes') }}" class="btn btn-primary">
                Abrir dashboard de respostas
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <p class="text-uppercase text-success small fw-semibold mb-1">Presenca</p>
              <h2 class="h5 fw-bold">Painel de presencas e inscricoes</h2>
              <p class="text-muted mb-3">
                Continue acompanhando presencas e inscritos por acao pedagogica
                com filtros, expansao de linhas e exportacao em PDF.
              </p>
              <a href="{{ route('dashboards.presencas') }}" class="btn btn-success">
                Abrir dashboard de presencas
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-0">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <p class="text-uppercase small text-muted mb-0">Modelos de formulario</p>
              <h3 class="h6 fw-bold mb-0">Ultimos utilizados</h3>
            </div>
          </div>
        </div>
        <div class="list-group list-group-flush">
          @forelse($templatesDisponiveis as $template)
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold">{{ $template->nome }}</div>
            </div>
            <a class="btn btn-sm btn-outline-primary" href="{{ route('dashboards.avaliacoes', ['template_id' => $template->id]) }}">
              Ver respostas
            </a>
          </div>
          @empty
          <div class="list-group-item text-muted">Nenhum modelo cadastrado ainda.</div>
          @endforelse
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card shadow-sm border-0 h-100">
        <div class="card-header bg-white border-0">
          <p class="text-uppercase small text-muted mb-0">Ações pedagógicas recentes</p>
          <h3 class="h6 fw-bold mb-0">O que recebeu inscricoes</h3>
        </div>
        <div class="card-body">
          <div class="vstack gap-3">
            @forelse($eventosRecentes as $evento)
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold">{{ $evento->nome }}</div>
                <small class="text-muted">Criado em {{ optional($evento->created_at)->format('d/m/Y') }}</small>
              </div>
              <a href="{{ route('eventos.show', $evento) }}" class="btn btn-sm btn-outline-secondary">Ver ação</a>
            </div>
            @empty
            <div class="text-muted">Nenhuma ação pedagógica registrado.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
