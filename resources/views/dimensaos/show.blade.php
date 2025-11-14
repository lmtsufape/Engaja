@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-6">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 fw-bold text-engaja mb-0">{{ $dimensao->descricao }}</h1>
      <a href="{{ route('dimensaos.edit', $dimensao) }}" class="btn btn-outline-secondary">Editar</a>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <p class="mb-3">
          <span class="fw-semibold">Indicadores relacionados:</span>
          {{ $dimensao->indicadores()->count() }}
        </p>

        <a href="{{ route('dimensaos.index') }}" class="btn btn-link px-0">Voltar para lista</a>
      </div>
    </div>
  </div>
</div>
@endsection
