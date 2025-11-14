@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-10">
    <h1 class="h3 fw-bold text-engaja mb-4">Editar modelo de avaliação</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        @include('templates-avaliacao.partials.form', [
          'action' => route('templates-avaliacao.update', $template),
          'method' => 'PUT',
          'submitLabel' => 'Salvar alterações',
          'template' => $template,
          'evidencias' => $evidencias,
          'escalas' => $escalas,
          'tiposQuestao' => $tiposQuestao,
        ])
      </div>
    </div>
  </div>
</div>
@endsection
