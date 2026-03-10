@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-9">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h4 fw-bold text-engaja mb-0">Respostas anónimas — {{ $submissao->created_at->format('d/m/Y H:i') }}</h1>
      <a href="{{ route('avaliacoes.respostas', $avaliacao) }}" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <div class="alert alert-info d-flex align-items-center gap-2 mb-3" role="alert">
          <span>🔒</span>
          <div>Resposta <strong>anónima</strong>. Nenhum dado identificador do participante é armazenado.</div>
        </div>
        <p class="mb-3">
          <strong>Enviado em:</strong> {{ $submissao->created_at->format('d/m/Y H:i') }}<br>
          <strong>Atividade:</strong> {{ $avaliacao->atividade->descricao ?? 'N/A' }} — {{ $avaliacao->atividade->evento->nome ?? 'N/A' }}
        </p>

        <div class="list-group">
          @foreach($avaliacao->avaliacaoQuestoes as $questao)
            @php
              $resp = $respostasPorQuestao->get($questao->id);
            @endphp
            <div class="list-group-item">
              <div class="fw-semibold mb-1">{{ $questao->texto }}</div>
              <div class="text-muted small mb-2">
                Tipo: {{ $questao->tipo }} |
                Evidência: {{ $questao->evidencia->descricao ?? '—' }} |
                Escala: {{ $questao->escala->descricao ?? '—' }}
              </div>
              <div>
                @if($resp)
                  {!! nl2br(e($resp->resposta)) !!}
                @else
                  <span class="text-muted">Não respondida.</span>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
