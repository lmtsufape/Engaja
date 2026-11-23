@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <p class="text-uppercase small text-muted mb-1">Relatório anónimo</p>
            <h1 class="h3 fw-bold mb-1" style="color:#421944;">
                Avaliação — {{ $atividade->descricao }}
            </h1>
            <p class="text-muted mb-0">
                {{ \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') }}
                @if($atividade->municipios->isNotEmpty())
                    · {{ $atividade->municipios->map(fn($m) => $m->nome_com_estado ?? $m->nome)->join(', ') }}
                @endif
            </p>
            <p class="text-muted small mt-1 mb-0">
                Ação pedagógica: <strong>{{ $atividade->evento->nome ?? '—' }}</strong>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($submissoes->isNotEmpty())
                <a href="{{ route('atividades.avaliacoes.pdf', $atividade) }}"
                   class="btn btn-primary">
                    Baixar PDF
                </a>
                <a href="{{ route('atividades.avaliacoes.pdf', ['atividade' => $atividade, 'formato' => 'docx']) }}"
                   class="btn btn-outline-primary">
                    <i class="bi bi-file-earmark-word"></i> Baixar Word
                </a>
            @endif
            <a href="{{ route('eventos.show', $atividade->evento_id) }}"
               class="btn btn-outline-secondary">← Voltar à ação pedagógica</a>
        </div>
    </div>

    <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
        <span style="font-size:1.2rem;">🔒</span>
        <div>
            As avaliações exibidas abaixo são <strong>estritamente anónimas</strong>.
            Nenhum dado identificador do participante (nome, e-mail, CPF) é armazenado
            ou apresentado nesta visualização.
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-3 col-sm-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-uppercase small text-muted mb-1">Submissões</p>
                    <div class="h3 fw-bold mb-0" style="color:#421944;">{{ number_format($totais['submissoes'] ?? 0, 0, ',', '.') }}</div>
                    <small class="text-muted">Respostas completas registadas</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-uppercase small text-muted mb-1">Questões com resposta</p>
                    <div class="h3 fw-bold mb-0" style="color:#421944;">{{ number_format($totais['questoes'] ?? 0, 0, ',', '.') }}</div>
                    <small class="text-muted">Com alguma resposta agregada</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-uppercase small text-muted mb-1">Itens de resposta</p>
                    <div class="h3 fw-bold mb-0" style="color:#421944;">{{ number_format($totais['respostas'] ?? 0, 0, ',', '.') }}</div>
                    <small class="text-muted">Total de campos preenchidos</small>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-sm-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <p class="text-uppercase small text-muted mb-1">Última resposta</p>
                    <div class="h4 fw-bold mb-0" style="color:#421944;">{{ $totais['ultima'] ?? '—' }}</div>
                    <small class="text-muted">Data/hora do último envio</small>
                </div>
            </div>
        </div>
    </div>

    @if($submissoes->isEmpty())
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-3">
                <div class="fw-semibold mb-1">Formulário aplicado</div>
                <div class="text-muted small">
                    {{ $avaliacao->templateAvaliacao->nome ?? '—' }}
                    &nbsp;·&nbsp;
                    {{ $avaliacao->avaliacaoQuestoes->count() }} questão(ões) no modelo
                </div>
            </div>
        </div>
        <div class="alert alert-warning mb-0">
            Nenhuma avaliação recebida ainda para este momento.
        </div>
    @else
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body py-3">
                <div class="fw-semibold mb-1">Formulário aplicado</div>
                <div class="text-muted small">
                    {{ $avaliacao->templateAvaliacao->nome ?? '—' }}
                    &nbsp;·&nbsp;
                    {{ $avaliacao->avaliacaoQuestoes->count() }} questão(ões) no modelo
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
            <h2 class="h5 fw-bold mb-0" style="color:#421944;">Distribuição por questão</h2>
            <span class="badge bg-primary-subtle text-primary">Interativo · padrão: barras horizontais</span>
        </div>

        {{-- vstack no JS: cabeçalhos em largura total; cada indicador tem a sua própria .row com 2 colunas --}}
        <div id="avaliacoes-momento-root" class="mb-4">
            <script type="application/json" id="avaliacoes-perguntas-json">@json($perguntas ?? [])</script>
            <div class="vstack gap-4" id="cards-questoes-momento"></div>
        </div>
    @endif

</div>

<div class="modal fade" id="textAnswersModalMomento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title js-text-modal-title">Respostas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="text-muted small mb-2 js-text-modal-count"></div>
                <div class="vstack gap-2 js-text-modal-list" style="max-height: 60vh; overflow: auto;"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
  /*
   * Duas colunas (md+): col-md-6 = 50%. Com só um cartão na linha (ex.: 1 questão por indicador),
   * o cartão ficava à esquerda e a metade direita parecia “vazia” — não era outra tela nem cache.
   * Órfãos na linha ocupam largura total.
   */
  @media (min-width: 768px) {
    #cards-questoes-momento .row.g-3 > .col-md-6:only-child,
    #cards-questoes-momento .row.g-3 > .col-md-6:nth-child(odd):nth-last-child(1) {
      flex: 0 0 100%;
      max-width: 100%;
    }
  }
  /* Grelha em duas colunas: evita que a coluna flex “empurre” o gráfico para metade vazia */
  #cards-questoes-momento .row > [class*="col-"] {
    min-width: 0;
  }
  #cards-questoes-momento .question-body {
    min-width: 0;
  }
  @media (max-width: 576px) {
    #cards-questoes-momento .question-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }
    #cards-questoes-momento .question-controls {
      width: 100%;
      justify-content: flex-start;
    }
    #cards-questoes-momento .question-controls select {
      width: 100%;
      max-width: none;
    }
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@vite(['resources/js/avaliacoes-distribuicao-charts.js'])
@endpush
