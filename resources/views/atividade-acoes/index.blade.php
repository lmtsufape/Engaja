@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 fw-bold text-engaja mb-0">Atividades/Ações</h1>
  <a href="{{ route('atividade-acoes.create') }}" class="btn btn-engaja">Nova atividade/ação</a>
</div>

@php
    $columns = [
        ['field' => 'nome', 'headerName' => 'Nome', 'flex' => 2],
        ['field' => 'turmas', 'headerName' => 'Turmas', 'flex' => 2],
        ['field' => 'detalhe', 'headerName' => 'Detalhe', 'flex' => 2],
        ['field' => 'agendamentos', 'headerName' => 'Agendamentos', 'flex' => 1],
        ['field' => 'acoes', 'headerName' => 'Ações', 'flex' => 1, 'html' => true],
    ];

    $rows = $atividadeAcoes->map(function ($atividadeAcao) {
        $turmas = $atividadeAcao->turmas_configuradas;
        $turmasLabel = $atividadeAcao->usa_turmas
            ? (count($turmas) ? implode(', ', $turmas) : 'Sem turmas configuradas')
            : 'Não utiliza turmas';

        $acoesHtml = '<div class="dropdown">'
            . '<button class="btn btn-sm btn-engaja dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Gerenciar</button>'
            . '<ul class="dropdown-menu dropdown-menu-end">'
            . '<li><a class="dropdown-item" href="' . route('atividade-acoes.show', $atividadeAcao) . '">Ver</a></li>'
            . '<li><a class="dropdown-item" href="' . route('atividade-acoes.edit', $atividadeAcao) . '">Editar</a></li>'
            . '<li>'
            . '<form method="POST" action="' . route('atividade-acoes.destroy', $atividadeAcao) . '" data-confirm="Tem certeza que deseja excluir esta atividade/ação?">'
            . csrf_field() . method_field('DELETE')
            . '<button type="submit" class="dropdown-item text-danger">Excluir</button>'
            . '</form>'
            . '</li>'
            . '</ul>'
            . '</div>';

        return [
            'nome' => $atividadeAcao->nome,
            'turmas' => $turmasLabel,
            'detalhe' => \Illuminate\Support\Str::limit(strip_tags($atividadeAcao->detalhe), 80) ?: '-',
            'agendamentos' => $atividadeAcao->agendamentos_count,
            'acoes' => $acoesHtml,
        ];
    })->values();
@endphp

<div class="card shadow-sm">
    <x-data-table
        id="grid-atividade-acoes"
        :columns="$columns"
        :rows="$rows"
        :page-size="15"
    />
</div>
@endsection
