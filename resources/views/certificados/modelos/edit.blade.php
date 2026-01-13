@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
  <div>
    <p class="text-uppercase text-muted small mb-1">Certificados</p>
    <h1 class="h4 fw-bold text-engaja mb-0">Editar modelo</h1>
    <div class="text-muted small">Atualize textos e imagens do modelo.</div>
  </div>
  <a href="{{ route('certificados.modelos.index') }}" class="btn btn-outline-secondary btn-sm">&lt; Voltar</a>
</div>

<form action="{{ route('certificados.modelos.update', $modelo) }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
  @csrf
  @method('PUT')
  @include('certificados.modelos.form', ['modelo' => $modelo])
</form>
@endsection
