@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 fw-bold text-engaja mb-0">{{ $indicador->descricao }}</h1>
      <a href="{{ route('indicadors.edit', $indicador) }}" class="btn btn-outline-secondary">Editar</a>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <p class="mb-2"><span class="fw-semibold">Dimensão:</span> {{ $indicador->dimensao->descricao ?? '—' }}</p>
        <p class="mb-3"><span class="fw-semibold">Questões cadastradas:</span> {{ $indicador->questoes()->count() }}</p>

        <a href="{{ route('indicadors.index') }}" class="btn btn-link px-0">Voltar para lista</a>
      </div>
    </div>
  </div>
</div>
@endsection
