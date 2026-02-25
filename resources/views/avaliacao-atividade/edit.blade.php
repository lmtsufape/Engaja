@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h4 fw-bold text-engaja mb-0">
                Editar Relatório — {{ $atividade->evento->nome ?? '' }}
            </h1>
            <small class="text-muted">Atualize o relatório de avaliação deste momento</small>
        </div>
        <a href="{{ route('eventos.show', $atividade->evento_id) }}"
           class="btn btn-outline-secondary">Voltar à ação pedagógica</a>
    </div>

    @if($errors->any())
    <div class="alert alert-danger">
        <strong>Ops!</strong> Verifique os campos abaixo.
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('avaliacao-atividade.update', $atividade) }}">
                @method('PUT')
                @include('avaliacao-atividade._form', [
                    'submitLabel' => 'Salvar alterações',
                ])
            </form>
        </div>
    </div>
</div>
@endsection