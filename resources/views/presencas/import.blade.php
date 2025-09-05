@extends('layouts.app')

@section('content')
<div class="container py-4">
  <h1 class="h5 mb-3">Importar presenças — {{ $evento->nome }}</h1>
  <p class="text-muted mb-3">
    Atividade: {{ \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') }}
    • {{ \Illuminate\Support\Str::of($atividade->hora_inicio)->substr(0,5) }}
    • {{ $atividade->descricao ?? 'Momento' }}
  </p>

  @if($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('atividades.presencas.cadastro', $atividade) }}" enctype="multipart/form-data" class="card p-3 shadow-sm">
    @csrf
    <div class="mb-3">
      <label class="form-label">Arquivo Excel (.xlsx/.xls/.csv)</label>
      <input type="file" name="your_file" class="form-control" required>
      <div class="form-text">Colunas: nome, email, cpf, telefone, municipio, escola_unidade, status, justificativa, data_entrada</div>
    </div>
    <button class="btn btn-engaja">Enviar</button>
  </form>
</div>
@endsection
