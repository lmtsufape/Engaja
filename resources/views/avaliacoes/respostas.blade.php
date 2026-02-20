@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-10">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 fw-bold text-engaja mb-0">Respostas da avaliação</h1>
      <a href="{{ route('avaliacoes.show', $avaliacao) }}" class="btn btn-outline-secondary">Voltar</a>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <p class="mb-3">
          <strong>Atividade:</strong> {{ $avaliacao->atividade->descricao ?? 'N/A' }} —
          <strong>Ação pedagógica:</strong> {{ $avaliacao->atividade->evento->nome ?? 'N/A' }}<br>
          <strong>Modelo:</strong> {{ $avaliacao->templateAvaliacao->nome ?? 'N/A' }}
        </p>

        @if($submissoes->isEmpty())
          <div class="alert alert-info mb-0">Nenhuma resposta enviada ainda.</div>
        @else
          <div class="table-responsive">
            <table class="table align-middle">
              <thead>
                <tr>
                  <th>Participante</th>
                  <th>Email</th>
                  <th>Enviado em</th>
                  <th class="text-end">Ações</th>
                </tr>
              </thead>
              <tbody>
                @foreach($submissoes as $submissao)
                  @php
                    $user = $submissao->presenca->inscricao->participante->user ?? null;
                  @endphp
                  <tr>
                    <td>{{ $user?->name ?? 'N/A' }}</td>
                    <td>{{ $user?->email ?? 'N/A' }}</td>
                    <td>{{ $submissao->created_at->format('d/m/Y H:i') }}</td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-primary"
                        href="{{ route('avaliacoes.respostas.mostrar', [$avaliacao, $submissao]) }}">
                        Ver respostas
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
