@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <p class="text-uppercase text-muted small mb-1">Meu perfil</p>
      <h1 class="h4 fw-bold text-engaja mb-0">Meus certificados</h1>
    </div>
    <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary btn-sm">Voltar para o perfil</a>
  </div>

  @if ($certificados->isEmpty())
    <div class="alert alert-info">Você ainda não possui certificados emitidos.</div>
  @else
    <div class="card shadow-sm">
      <div class="table-responsive">
        <table class="table align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Ação pedagógica</th>
              <th>Emitido em</th>
              <th>Carga horária</th>
              <th class="text-end pe-4">Ações</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($certificados as $c)
              <tr>
                <td>{{ $c->evento_nome ?? $c->modelo?->nome ?? 'Evento' }}</td>
                <td>{{ $c->created_at?->format('d/m/Y H:i') }}</td>
                <td>{{ $c->carga_horaria }}h</td>
                <td class="text-end pe-4">
                  <a href="{{ route('certificados.download', $c) }}" class="btn btn-sm btn-engaja">
                    Baixar PDF
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  @endif
</div>
@endsection
