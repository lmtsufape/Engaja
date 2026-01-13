@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold text-engaja mb-0">Editar momento — {{ $evento->nome }}</h1>
    <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">Voltar à ação pedagógica</a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger"><strong>Ops!</strong> Verifique os campos abaixo.</div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="POST" action="{{ route('atividades.update', $atividade) }}">
        @method('PUT')
        @include('atividades._form', [
          'evento' => $evento,
          'atividade' => $atividade,
          'municipios' => $municipios,
          'atividadesCopiaveis' => $atividadesCopiaveis,
          'submitLabel' => 'Salvar alterações'
        ])
      </form>
    </div>
  </div>
</div>
@endsection
