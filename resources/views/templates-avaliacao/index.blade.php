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
        <button type="submit" class="btn btn-engaja">Aplicar</button>
        <a href="{{ route('templates-avaliacao.index') }}" class="btn btn-outline-secondary">Limpar</a>
      </div>
    </div>
  </div>
</form>

@php
    $columns = [
        ['field' => 'nome', 'headerName' => 'Nome', 'flex' => 2],
        ['field' => 'descricao', 'headerName' => 'Descrição', 'flex' => 3],
        ['field' => 'questoes', 'headerName' => 'Qtd. questões', 'flex' => 1],
        ['field' => 'acoes', 'headerName' => 'Ações', 'flex' => 1, 'html' => true],
    ];

    $podeExcluir = auth()->user()?->hasAnyRole(['administrador', 'gerente', 'eq_pedagogica']);

    $rows = $templates->map(function ($template) use ($podeExcluir) {
        $acoesHtml = '<div class="dropdown">'
            . '<button class="btn btn-sm btn-engaja dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Gerenciar</button>'
            . '<ul class="dropdown-menu dropdown-menu-end">'
            . '<li><a class="dropdown-item" href="' . route('templates-avaliacao.show', $template) . '">Ver</a></li>'
            . '<li><a class="dropdown-item" href="' . route('templates-avaliacao.edit', $template) . '">Editar</a></li>';

        if ($podeExcluir) {
            $acoesHtml .= '<li>'
                . '<form method="POST" action="' . route('templates-avaliacao.destroy', $template) . '" data-confirm="Tem certeza que deseja excluir este modelo?">'
                . csrf_field() . method_field('DELETE')
                . '<button type="submit" class="dropdown-item text-danger">Excluir</button>'
                . '</form>'
                . '</li>';
        }

        $acoesHtml .= '</ul></div>';

        return [
            'nome' => $template->nome,
            'descricao' => $template->descricao ? strip_tags($template->descricao) : '—',
            'questoes' => $template->questoes_count,
            'acoes' => $acoesHtml,
        ];
    })->values();
@endphp

<div class="card shadow-sm">
    <x-data-table
        id="grid-templates-avaliacao"
        :columns="$columns"
        :rows="$rows"
        :page-size="15"
    />
</div>
@endsection
