@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Avaliações</h1>
  <a href="{{ route('avaliacoes.create') }}" class="btn btn-engaja">Nova avaliação</a>
</div>

<form method="GET" action="{{ route('avaliacoes.index') }}" class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-1 align-items-end">
      <div class="col-lg-3 col-md-6">
        <label for="search" class="form-label">Buscar (momento, evento, participante ou modelo)</label>
        <input type="text" class="form-control" id="search" name="search"
          value="{{ request('search') }}" placeholder="Digite para filtrar...">
      </div>
      <div class="col-lg-3 col-md-6">
        <label for="template_id" class="form-label">Modelo</label>
        <select id="template_id" name="template_id" class="form-select">
          <option value="">Todos</option>
          @foreach ($templatesDisponiveis as $id => $nome)
          <option value="{{ $id }}" @selected((string) request('template_id') === (string) $id)>{{ $nome }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-2 col-md-6">
        <label for="de" class="form-label">Registrada de</label>
        <input type="date" id="de" name="de" class="form-control" value="{{ request('de') }}">
      </div>
      <div class="col-lg-2 col-md-6">
        <label for="ate" class="form-label">Registrada até</label>
        <input type="date" id="ate" name="ate" class="form-control" value="{{ request('ate') }}">
      </div>
      <!-- <div class="col-lg-2 col-md-6">
        <label for="has_respostas" class="form-label">Respostas</label>
        <select id="has_respostas" name="has_respostas" class="form-select">
          <option value="">Todas</option>
          <option value="with" @selected(request('has_respostas') === 'with')>Com respostas</option>
          <option value="without" @selected(request('has_respostas') === 'without')>Sem respostas</option>
        </select>
      </div> -->
      <div class="col-2 d-flex gap-1">
        <input type="hidden" name="sort" value="{{ request('sort', 'created_at') }}">
        <input type="hidden" name="dir"
          value="{{ strtolower(request('dir', request('direction', 'desc'))) === 'asc' ? 'asc' : 'desc' }}">
        <button type="submit" class="btn btn-engaja">Aplicar</button>
        <a href="{{ route('avaliacoes.index') }}" class="btn btn-outline-secondary">Limpar</a>
      </div>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        @php
          function avaliacao_sort_link($label, $key) {
            $currentSort = request('sort', 'created_at');
            $dirParam = request('dir', request('direction', 'desc'));
            $currentDir = strtolower((string) $dirParam) === 'asc' ? 'asc' : 'desc';
            $nextDir = ($currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';
            $params = array_merge(request()->except('page'), ['sort' => $key, 'dir' => $nextDir]);
            $url = request()->url() . '?' . http_build_query($params);
            $isActive = $currentSort === $key;
            $arrow = $isActive ? ($currentDir === 'asc' ? '↑' : '↓') : '';
            return '<a href="' . $url . '" class="text-decoration-none text-nowrap">' . e($label) . ' <span class="text-muted">' . $arrow . '</span></a>';
          }
        @endphp
        <tr>
          <th>{!! avaliacao_sort_link('Momento', 'momento') !!}</th>
          <th>{!! avaliacao_sort_link('Modelo', 'template') !!}</th>
          <th>{!! avaliacao_sort_link('Registrada em', 'created_at') !!}</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($avaliacoes as $avaliacao)
        @php
          $inscricaoExibida = $avaliacao->inscricao ?? $avaliacao->respostas->first()?->inscricao;
          $participanteNome = $inscricaoExibida?->participante?->user?->name;
        @endphp
        <tr>
          <td>
            <span>{{ $avaliacao->atividade->descricao ?? '—' }}</span>
            <small class="d-block text-muted">
              {{ $avaliacao->atividade && $avaliacao->atividade->dia ? \Illuminate\Support\Carbon::parse($avaliacao->atividade->dia)->format('d/m/Y') : '' }}
              {{ $avaliacao->atividade->hora_inicio ?? '' }}
            </small>
            @if($participanteNome)
            <small class="d-block text-muted">Participante: {{ $participanteNome }}</small>
            @endif
          </td>
          <td>{{ $avaliacao->templateAvaliacao->nome ?? '—' }}</td>
          <td>{{ $avaliacao->created_at ? $avaliacao->created_at->format('d/m/Y H:i') : '—' }}</td>
          <td class="text-end">
            <a href="{{ route('avaliacoes.show', $avaliacao) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('avaliacoes.edit', $avaliacao) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('avaliacoes.destroy', $avaliacao) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir esta avaliação?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" class="text-center text-muted py-4">Nenhuma avaliação registrada.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $avaliacoes->links() }}
</div>
@endsection
