@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
  <div>
    <p class="text-uppercase text-muted small mb-1">Certificados</p>
    <h1 class="h4 fw-bold text-engaja mb-0">Novo modelo</h1>
    <div class="text-muted small">Defina textos e imagens base para certificados.</div>
  </div>
  <a href="{{ route('certificados.modelos.index') }}" class="btn btn-outline-secondary btn-sm">&lt; Voltar</a>
</div>

<form action="{{ route('certificados.modelos.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
  @csrf
  @include('certificados.modelos.form', ['modelo' => null])
</form>
@endsection
