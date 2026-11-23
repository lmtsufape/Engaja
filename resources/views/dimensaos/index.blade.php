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
        <label for="has_indicators" class="form-label">Filtro por indicador</label>
        <select id="has_indicators" name="has_indicators" class="form-select">
          <option value="">Todas</option>
          <option value="with" @selected(request('has_indicators') === 'with')>Com indicadores</option>
          <option value="without" @selected(request('has_indicators') === 'without')>Sem indicadores</option>
        </select>
      </div>
      <div class="col-3 d-flex gap-2">
        <button type="submit" class="btn btn-engaja">Aplicar</button>
        <a href="{{ route('dimensaos.index') }}" class="btn btn-outline-secondary">Limpar</a>
      </div>
    </div>
  </div>
</form>

@php
    $columns = [
        ['field' => 'descricao', 'headerName' => 'Descrição', 'flex' => 3],
        ['field' => 'indicadores', 'headerName' => 'Qtd. indicadores', 'flex' => 1],
        ['field' => 'acoes', 'headerName' => 'Ações', 'flex' => 1, 'html' => true],
    ];

    $podeExcluir = auth()->user()?->hasAnyRole(['administrador', 'gerente', 'eq_pedagogica']);

    $rows = $dimensaos->map(function ($dimensao) use ($podeExcluir) {
        $acoesHtml = '<div class="dropdown">'
            . '<button class="btn btn-sm btn-engaja dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Gerenciar</button>'
            . '<ul class="dropdown-menu dropdown-menu-end">'
            . '<li><a class="dropdown-item" href="' . route('dimensaos.show', $dimensao) . '">Ver</a></li>'
            . '<li><a class="dropdown-item" href="' . route('dimensaos.edit', $dimensao) . '">Editar</a></li>';

        if ($podeExcluir) {
            $acoesHtml .= '<li>'
                . '<form method="POST" action="' . route('dimensaos.destroy', $dimensao) . '" data-confirm="Tem certeza que deseja excluir esta dimensão?">'
                . csrf_field() . method_field('DELETE')
                . '<button type="submit" class="dropdown-item text-danger">Excluir</button>'
                . '</form>'
                . '</li>';
        }

        $acoesHtml .= '</ul></div>';

        return [
            'descricao' => $dimensao->descricao,
            'indicadores' => $dimensao->indicadores_count,
            'acoes' => $acoesHtml,
        ];
    })->values();
@endphp

<div class="card shadow-sm">
    <x-data-table
        id="grid-dimensaos"
        :columns="$columns"
        :rows="$rows"
        :page-size="15"
    />
</div>
@endsection
