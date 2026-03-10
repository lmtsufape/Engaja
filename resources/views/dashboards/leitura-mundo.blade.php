@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
    <div>
      <p class="text-uppercase small text-muted mb-1">Dashboards</p>
      <h1 class="h4 fw-bold mb-1">Leitura do mundo</h1>
      <p class="text-muted mb-0">Selecione uma leitura para abrir o dashboard de respostas.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Hub de dashboards</a>
      <a href="{{ route('dashboards.avaliacoes') }}" class="btn btn-outline-primary">Dashboard de respostas</a>
    </div>
  </div>

  @if($erro)
    <div class="alert alert-danger border-0 shadow-sm">
      Nao foi possivel carregar as leituras do LimeSurvey: {{ $erro }}
    </div>
  @endif

  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th scope="col">SID</th>
              <th scope="col">Leitura do mundo</th>
              <th scope="col">Status</th>
              <th scope="col">Inicio</th>
              <th scope="col">Expira</th>
              <th scope="col" class="text-end">Acao</th>
            </tr>
          </thead>
          <tbody>
            @forelse($surveys as $survey)
              <tr>
                <td class="fw-semibold">{{ $survey['sid'] }}</td>
                <td>{{ $survey['titulo'] }}</td>
                <td>
                  @if($survey['ativo'])
                    <span class="badge text-bg-success">Ativo</span>
                  @else
                    <span class="badge text-bg-secondary">Inativo</span>
                  @endif
                </td>
                <td>{{ $survey['startdate'] ?? '-' }}</td>
                <td>{{ $survey['expires'] ?? '-' }}</td>
                <td class="text-end">
                  <a
                    href="{{ route('dashboards.avaliacoes', ['fonte' => 'limesurvey', 'survey_id' => $survey['sid']]) }}"
                    class="btn btn-sm btn-primary">
                    Abrir dashboard
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">Nenhuma leitura encontrada.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
