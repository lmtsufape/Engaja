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
        <button type="submit" class="btn btn-engaja">Aplicar</button>
        <a href="{{ route('evidencias.index') }}" class="btn btn-outline-secondary">Limpar</a>
      </div>
    </div>
  </div>
</form>

@php
    $columns = [
        ['field' => 'descricao', 'headerName' => 'Descrição', 'flex' => 2],
        ['field' => 'dimensao', 'headerName' => 'Dimensão', 'flex' => 1],
        ['field' => 'indicador', 'headerName' => 'Indicador', 'flex' => 1],
        ['field' => 'acoes', 'headerName' => 'Ações', 'flex' => 1, 'html' => true],
    ];

    $podeExcluir = auth()->user()?->hasAnyRole(['administrador', 'gerente', 'eq_pedagogica']);

    $rows = $evidencias->map(function ($evidencia) use ($podeExcluir) {
        $acoesHtml = '<div class="dropdown">'
            . '<button class="btn btn-sm btn-engaja dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Gerenciar</button>'
            . '<ul class="dropdown-menu dropdown-menu-end">'
            . '<li><a class="dropdown-item" href="' . route('evidencias.show', $evidencia) . '">Ver</a></li>'
            . '<li><a class="dropdown-item" href="' . route('evidencias.edit', $evidencia) . '">Editar</a></li>'
            . '<li>'
            . '<form method="POST" action="' . route('evidencias.duplicar', $evidencia) . '" data-confirm="Tem certeza que deseja duplicar esta evidência?">'
            . csrf_field()
            . '<button type="submit" class="dropdown-item">Duplicar</button>'
            . '</form>'
            . '</li>';

        if ($podeExcluir) {
            $acoesHtml .= '<li>'
                . '<form method="POST" action="' . route('evidencias.destroy', $evidencia) . '" data-confirm="Tem certeza que deseja excluir esta evidência?">'
                . csrf_field() . method_field('DELETE')
                . '<button type="submit" class="dropdown-item text-danger">Excluir</button>'
                . '</form>'
                . '</li>';
        }

        $acoesHtml .= '</ul></div>';

        return [
            'descricao' => $evidencia->descricao,
            'dimensao' => $evidencia->indicador->dimensao->descricao ?? '—',
            'indicador' => $evidencia->indicador->descricao ?? '—',
            'acoes' => $acoesHtml,
        ];
    })->values();
@endphp

<div class="card shadow-sm">
    <x-data-table
        id="grid-evidencias"
        :columns="$columns"
        :rows="$rows"
        :page-size="15"
    />
</div>
@endsection
