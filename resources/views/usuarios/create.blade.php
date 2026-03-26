@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
  <div>
    <p class="text-uppercase text-muted small mb-1">Administração</p>
    <h1 class="h4 fw-bold text-engaja mb-0">Cadastrar Usuário</h1>
    <div class="text-muted small">Crie um novo usuário pela área de gerenciamento.</div>
  </div>
  <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

@include('usuarios._form', [
  'formAction' => route('usuarios.store'),
  'submitLabel' => 'Cadastrar usuário',
  'isEdit' => false,
])
@endsection
