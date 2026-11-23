@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
  <div>
    <p class="text-uppercase text-muted small mb-1">Certificados</p>
    <h1 class="h4 fw-bold text-engaja mb-0">Modelos de certificado</h1>
    <div class="text-muted small">Configure textos e imagens para gerar certificados.</div>
  </div>
  <a href="{{ route('certificados.modelos.create') }}" class="btn btn-engaja">Novo modelo</a>
</div>

@if ($modelos->isEmpty())
  <div class="alert alert-info">Nenhum modelo cadastrado.</div>
@else
  @php
      $columns = [
          ['field' => 'nome', 'headerName' => 'Nome', 'flex' => 1],
          ['field' => 'descricao', 'headerName' => 'Descrição', 'flex' => 2],
          ['field' => 'eixo', 'headerName' => 'Eixo', 'flex' => 1],
          ['field' => 'acoes', 'headerName' => 'Ações', 'flex' => 1, 'html' => true],
      ];

      $podeExcluir = auth()->user()?->hasRole('administrador');

      $rows = $modelos->map(function ($modelo) use ($podeExcluir) {
          $acoesHtml = '<div class="dropdown">'
              . '<button class="btn btn-sm btn-engaja dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Gerenciar</button>'
              . '<ul class="dropdown-menu dropdown-menu-end">'
              . '<li><a class="dropdown-item" href="' . route('certificados.modelos.edit', $modelo) . '">Editar</a></li>';

          if ($podeExcluir) {
              $acoesHtml .= '<li>'
                  . '<form method="POST" action="' . route('certificados.modelos.destroy', $modelo) . '" data-confirm="Remover este modelo?">'
                  . csrf_field() . method_field('DELETE')
                  . '<button type="submit" class="dropdown-item text-danger">Excluir</button>'
                  . '</form>'
                  . '</li>';
          }

          $acoesHtml .= '</ul></div>';

          return [
              'nome' => $modelo->nome,
              'descricao' => $modelo->descricao ?: '—',
              'eixo' => $modelo->eixo->nome ?? '—',
              'acoes' => $acoesHtml,
          ];
      })->values();
  @endphp

  <div class="card shadow-sm">
      <x-data-table
          id="grid-certificados-modelos"
          :columns="$columns"
          :rows="$rows"
          :page-size="15"
      />
  </div>
@endif
@endsection
