@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 fw-bold text-engaja mb-0">Evidência</h1>
      <a href="{{ route('evidencias.edit', $evidencia) }}" class="btn btn-outline-secondary">Editar</a>
    </div>

    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h2 class="h5 fw-semibold">Descrição</h2>
        <p class="mb-4">{{ $evidencia->descricao }}</p>

        <div class="row mb-3">
          <div class="col-md-6">
            <span class="fw-semibold d-block text-muted text-uppercase small">Indicador</span>
            <span>{{ $evidencia->indicador->descricao ?? '—' }}</span>
          </div>
          <div class="col-md-6">
            <span class="fw-semibold d-block text-muted text-uppercase small">Dimensão</span>
            <span>{{ $evidencia->indicador->dimensao->descricao ?? '—' }}</span>
          </div>
        </div>
      </div>
    </div>

    <a href="{{ route('evidencias.index') }}" class="btn btn-link px-0 mt-3">Voltar para lista</a>
  </div>
</div>
@endsection

