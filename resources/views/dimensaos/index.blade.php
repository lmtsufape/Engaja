@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Dimensões</h1>
  <a href="{{ route('dimensaos.create') }}" class="btn btn-engaja">Nova dimensão</a>
</div>

<form method="GET" action="{{ route('dimensaos.index') }}" class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-md-5 col-lg-4">
        <label for="search" class="form-label">Buscar por descrição</label>
        <input type="text" class="form-control" id="search" name="search"
          value="{{ request('search') }}" placeholder="Digite parte da descrição">
      </div>
      <div class="col-md-4 col-lg-3">
        <label for="has_indicators" class="form-label">Filtro por indicadores</label>
        <select id="has_indicators" name="has_indicators" class="form-select">
          <option value="">Todas</option>
          <option value="with" @selected(request('has_indicators') === 'with')>Com indicadores</option>
          <option value="without" @selected(request('has_indicators') === 'without')>Sem indicadores</option>
        </select>
      </div>
      <div class="col-3 d-flex gap-2">
        <input type="hidden" name="sort" value="{{ request('sort', 'descricao') }}">
        <input type="hidden" name="dir"
          value="{{ strtolower(request('dir', request('direction', 'asc'))) === 'desc' ? 'desc' : 'asc' }}">
        <button type="submit" class="btn btn-engaja">Aplicar</button>
        <a href="{{ route('dimensaos.index') }}" class="btn btn-outline-secondary">Limpar</a>
      </div>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        @php
          function dimensao_sort_link($label, $key) {
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
          <th>{!! dimensao_sort_link('Descrição', 'descricao') !!}</th>
          <th class="text-center">{!! dimensao_sort_link('Qtd. indicadores', 'indicadores') !!}</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($dimensaos as $dimensao)
        <tr>
          <td class="fw-semibold">{{ $dimensao->descricao }}</td>
          <td class="text-center">{{ $dimensao->indicadores_count }}</td>
          <td class="text-end">
            <a href="{{ route('dimensaos.show', $dimensao) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('dimensaos.edit', $dimensao) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('dimensaos.destroy', $dimensao) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir esta dimensão?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="3" class="text-center text-muted py-4">Nenhuma dimensão cadastrada.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $dimensaos->links() }}
</div>
@endsection
