@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-8 col-xl-6">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="h3 fw-bold text-engaja mb-0">{{ $escala->descricao }}</h1>
      <a href="{{ route('escalas.edit', $escala) }}" class="btn btn-outline-secondary">Editar</a>
    </div>

    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="h6 fw-semibold text-uppercase text-muted mb-3">Opções</h2>
        <ul class="list-group list-group-flush mb-3">
          @php
            $opcoes = collect([$escala->opcao1, $escala->opcao2, $escala->opcao3, $escala->opcao4, $escala->opcao5]);
          @endphp
          @forelse ($opcoes->filter() as $opcao)
          <li class="list-group-item px-0">{!! $opcao !!}</li>
          @empty
          <li class="list-group-item px-0 text-muted">Nenhuma opção configurada.</li>
          @endforelse
        </ul>

        <a href="{{ route('escalas.index') }}" class="btn btn-link px-0">Voltar para lista</a>
      </div>
    </div>
  </div>
</div>
@endsection
