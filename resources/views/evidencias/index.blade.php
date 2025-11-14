@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Evidências</h1>
  <a href="{{ route('evidencias.create') }}" class="btn btn-engaja">Nova evidência</a>
</div>

<form method="GET" action="{{ route('evidencias.index') }}" class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-lg-3 col-md-5">
        <label for="search" class="form-label">Buscar por descrição</label>
        <input type="text" class="form-control" id="search" name="search"
          value="{{ request('search') }}" placeholder="Digite parte da descrição">
      </div>
      <div class="col-lg-3 col-md-4">
        <label for="dimensao_id" class="form-label">Filtrar por dimensão</label>
        <select id="dimensao_id" name="dimensao_id" class="form-select">
          <option value="">Todas</option>
          @foreach ($dimensoes as $id => $descricao)
          <option value="{{ $id }}" @selected((string)request('dimensao_id') === (string)$id)>{{ $descricao }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-lg-3 col-md-3">
        <label for="indicador_id" class="form-label">Filtrar por indicador</label>
        <select id="indicador_id" name="indicador_id" class="form-select">
          <option value="">Todos</option>
          @foreach ($indicadores as $id => $descricao)
          <option value="{{ $id }}" @selected((string)request('indicador_id') === (string)$id)>{{ $descricao }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-2 d-flex gap-2">
        <input type="hidden" name="sort" value="{{ request('sort', 'descricao') }}">
        <input type="hidden" name="dir"
          value="{{ strtolower(request('dir', request('direction', 'asc'))) === 'desc' ? 'desc' : 'asc' }}">
        <button type="submit" class="btn btn-engaja">Aplicar</button>
        <a href="{{ route('evidencias.index') }}" class="btn btn-outline-secondary">Limpar</a>
      </div>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        @php
          function evidencia_sort_link($label, $key) {
            $currentSort = request('sort', 'descricao');
            $dirParam = request('dir', request('direction', 'asc'));
            $currentDir = strtolower((string) $dirParam) === 'desc' ? 'desc' : 'asc';
            $nextDir = ($currentSort === $key && $currentDir === 'asc') ? 'desc' : 'asc';
            $params = array_merge(request()->except('page'), ['sort' => $key, 'dir' => $nextDir]);
            $url = request()->url() . '?' . http_build_query($params);
            $isActive = $currentSort === $key;
            $arrow = $isActive ? ($currentDir === 'asc' ? '↑' : '↓') : '';
            return '<a href="' . $url . '" class="text-decoration-none text-nowrap">' . e($label) . ' <span class="text-muted">' . $arrow . '</span></a>';
          }
        @endphp
        <tr>
          <th>{!! evidencia_sort_link('Descrição', 'descricao') !!}</th>
          <th>{!! evidencia_sort_link('Dimensão', 'dimensao') !!}</th>
          <th>{!! evidencia_sort_link('Indicador', 'indicador') !!}</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($evidencias as $evidencia)
        <tr>
          <td class="fw-semibold">{{ $evidencia->descricao }}</td>
          <td>{{ $evidencia->indicador->dimensao->descricao ?? '—' }}</td>
          <td>{{ $evidencia->indicador->descricao ?? '—' }}</td>
          <td class="text-end">
            <a href="{{ route('evidencias.show', $evidencia) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('evidencias.edit', $evidencia) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('evidencias.destroy', $evidencia) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir esta evidência?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" class="text-center text-muted py-4">Nenhuma evidência cadastrada.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $evidencias->links() }}
</div>
@endsection
