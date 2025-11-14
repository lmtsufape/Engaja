@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Modelos de avaliação</h1>
  <a href="{{ route('templates-avaliacao.create') }}" class="btn btn-engaja">Novo modelo</a>
</div>

<form method="GET" action="{{ route('templates-avaliacao.index') }}" class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-lg-4 col-md-5">
        <label for="search" class="form-label">Buscar por nome ou descrição</label>
        <input type="text" class="form-control" id="search" name="search"
          value="{{ request('search') }}" placeholder="Digite para filtrar...">
      </div>
      <div class="col-lg-3 col-md-4">
        <label for="has_questions" class="form-label">Filtro por questões</label>
        <select id="has_questions" name="has_questions" class="form-select">
          <option value="">Todos</option>
          <option value="with" @selected(request('has_questions') === 'with')>Com questões</option>
          <option value="without" @selected(request('has_questions') === 'without')>Sem questões</option>
        </select>
      </div>
      <div class="col-3 d-flex gap-2">
        <input type="hidden" name="sort" value="{{ request('sort', 'nome') }}">
        <input type="hidden" name="dir"
          value="{{ strtolower(request('dir', request('direction', 'asc'))) === 'desc' ? 'desc' : 'asc' }}">
        <button type="submit" class="btn btn-engaja">Aplicar</button>
        <a href="{{ route('templates-avaliacao.index') }}" class="btn btn-outline-secondary">Limpar</a>
      </div>
    </div>
  </div>
</form>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        @php
          function template_sort_link($label, $key) {
            $currentSort = request('sort', 'nome');
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
          <th>{!! template_sort_link('Nome', 'nome') !!}</th>
          <th>{!! template_sort_link('Descrição', 'descricao') !!}</th>
          <th class="text-center">{!! template_sort_link('Qtd. questões', 'questoes') !!}</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($templates as $template)
        <tr>
          <td class="fw-semibold">{{ $template->nome }}</td>
          <td>
            @if($template->descricao)
            <span class="text-wrap">{{ \Illuminate\Support\Str::limit(strip_tags($template->descricao), 80) }}</span>
            @else
            <span class="text-muted">—</span>
            @endif
          </td>
          <td class="text-center">{{ $template->questoes_count }}</td>
          <td class="text-end">
            <a href="{{ route('templates-avaliacao.show', $template) }}" class="btn btn-sm btn-outline-primary">Ver</a>
            <a href="{{ route('templates-avaliacao.edit', $template) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
            <form action="{{ route('templates-avaliacao.destroy', $template) }}" method="POST" class="d-inline">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger"
                onclick="return confirm('Tem certeza que deseja excluir este modelo?')">Excluir</button>
            </form>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" class="text-center text-muted py-4">Nenhum modelo cadastrado.</td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="mt-3">
  {{ $templates->links() }}
</div>
@endsection
