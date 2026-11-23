@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Indicadores</h1>
  <a href="{{ route('indicadors.create') }}" class="btn btn-engaja">Novo indicador</a>
</div>

<form method="GET" action="{{ route('indicadors.index') }}" class="card shadow-sm mb-4">
  <div class="card-body">
    <div class="row g-3 align-items-end">
      <div class="col-lg-4 col-md-5">
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
      <div class="col-3 d-flex gap-2">
        <button type="submit" class="btn btn-engaja">Aplicar</button>
        <a href="{{ route('indicadors.index') }}" class="btn btn-outline-secondary">Limpar</a>
      </div>
    </div>
  </div>
</form>

@php
    $columns = [
        ['field' => 'descricao', 'headerName' => 'Descrição', 'flex' => 3],
        ['field' => 'dimensao', 'headerName' => 'Dimensão', 'flex' => 2],
        ['field' => 'questoes', 'headerName' => 'Qtd. questões', 'flex' => 1],
        ['field' => 'acoes', 'headerName' => 'Ações', 'flex' => 1, 'html' => true],
    ];

    $podeExcluir = auth()->user()?->hasAnyRole(['administrador', 'gerente', 'eq_pedagogica']);

    $rows = $indicadors->map(function ($indicador) use ($podeExcluir) {
        $acoesHtml = '<div class="dropdown">'
            . '<button class="btn btn-sm btn-engaja dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Gerenciar</button>'
            . '<ul class="dropdown-menu dropdown-menu-end">'
            . '<li><a class="dropdown-item" href="' . route('indicadors.show', $indicador) . '">Ver</a></li>'
            . '<li><a class="dropdown-item" href="' . route('indicadors.edit', $indicador) . '">Editar</a></li>';

        if ($podeExcluir) {
            $acoesHtml .= '<li>'
                . '<form method="POST" action="' . route('indicadors.destroy', $indicador) . '" data-confirm="Tem certeza que deseja excluir este indicador?">'
                . csrf_field() . method_field('DELETE')
                . '<button type="submit" class="dropdown-item text-danger">Excluir</button>'
                . '</form>'
                . '</li>';
        }

        $acoesHtml .= '</ul></div>';

        return [
            'descricao' => $indicador->descricao,
            'dimensao' => $indicador->dimensao->descricao ?? '—',
            'questoes' => $indicador->questoes_count,
            'acoes' => $acoesHtml,
        ];
    })->values();
@endphp

<div class="card shadow-sm">
    <x-data-table
        id="grid-indicadores"
        :columns="$columns"
        :rows="$rows"
        :page-size="15"
    />
</div>
@endsection
