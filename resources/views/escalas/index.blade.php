@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Escalas</h1>
  <a href="{{ route('escalas.create') }}" class="btn btn-engaja">Nova escala</a>
</div>

<form method="GET" action="{{ route('escalas.index') }}" class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-md-4">
        <label for="search" class="form-label">Buscar por descrição</label>
        <input type="text" class="form-control" id="search" name="search"
          value="{{ request('search') }}" placeholder="Digite parte da descrição">
      </div>
      <div class="col-md-3">
        <label for="has_options" class="form-label">Filtro de opções</label>
        <select id="has_options" name="has_options" class="form-select">
          <option value="">Todas</option>
          <option value="with" @selected(request('has_options') === 'with')>Com opções</option>
          <option value="without" @selected(request('has_options') === 'without')>Sem opções</option>
        </select>
      </div>
      <div class="col-4 d-flex gap-2">
        <button type="submit" class="btn btn-engaja">Aplicar</button>
        <a href="{{ route('escalas.index') }}" class="btn btn-outline-secondary">Limpar</a>
      </div>
    </div>
  </div>
</form>

@php
    $columns = [
        ['field' => 'descricao', 'headerName' => 'Descrição', 'flex' => 2],
        ['field' => 'opcoes', 'headerName' => 'Opções', 'flex' => 3],
        ['field' => 'acoes', 'headerName' => 'Ações', 'flex' => 1, 'html' => true],
    ];

    $podeExcluir = auth()->user()?->hasAnyRole(['administrador', 'gerente', 'eq_pedagogica']);

    $rows = $escalas->map(function ($escala) use ($podeExcluir) {
        $opcoes = collect([$escala->opcao1, $escala->opcao2, $escala->opcao3, $escala->opcao4, $escala->opcao5])->filter()->values();
        $opcoesPreview = $opcoes->map(fn ($texto) => trim(strip_tags($texto)))->filter()->implode(' | ');

        $acoesHtml = '<div class="dropdown">'
            . '<button class="btn btn-sm btn-engaja dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Gerenciar</button>'
            . '<ul class="dropdown-menu dropdown-menu-end">'
            . '<li><a class="dropdown-item" href="' . route('escalas.show', $escala) . '">Ver</a></li>'
            . '<li><a class="dropdown-item" href="' . route('escalas.edit', $escala) . '">Editar</a></li>';

        if ($podeExcluir) {
            $acoesHtml .= '<li>'
                . '<form method="POST" action="' . route('escalas.destroy', $escala) . '" data-confirm="Tem certeza que deseja excluir esta escala?">'
                . csrf_field() . method_field('DELETE')
                . '<button type="submit" class="dropdown-item text-danger">Excluir</button>'
                . '</form>'
                . '</li>';
        }

        $acoesHtml .= '</ul></div>';

        return [
            'descricao' => $escala->descricao,
            'opcoes' => $opcoes->isEmpty() ? '-' : $opcoesPreview,
            'acoes' => $acoesHtml,
        ];
    })->values();
@endphp

<div class="card shadow-sm">
    <x-data-table
        id="grid-escalas"
        :columns="$columns"
        :rows="$rows"
        :page-size="15"
    />
</div>
@endsection
