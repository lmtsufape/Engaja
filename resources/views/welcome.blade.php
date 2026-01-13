@extends('layouts.app')

@section('content')
<div class="text-center py-5">
    <h1 class="display-4 text-engaja fw-bold">Bem-vindo ao Engaja</h1>
    <p class="lead mt-3">
        Sistema de <span class="fw-semibold">Gestão de Inscrições e Presenças</span> para fortalecer o engajamento educacional.
    </p>

    <div class="mt-4">
        @guest
            <a href="{{ route('login') }}" class="btn btn-engaja btn-lg me-2">Entrar</a>
            <a href="{{ route('register') }}" class="btn btn-outline-engaja btn-lg">Cadastrar</a>
        @else
            {{-- <a href="{{ url('/dashboard') }}" class="btn btn-engaja btn-lg me-2">Ir para o painel</a> --}}
            @hasanyrole('administrador|gestor')
                <a href="{{ route('eventos.index') }}" class="btn btn-engaja btn-lg">Ver ações pedagógicas</a>
            @endrole
        @endguest
    </div>
</div>

<div class="row mt-5">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center">
                <div class="mb-3 text-engaja fs-1">📅</div>
                <h5 class="card-title fw-bold">Ações pedagógicas</h5>
                <p class="card-text">Organize e acompanhe inscrições para formações, encontros e reuniões.</p>
                {{-- " class="btn btn-sm btn-engaja mt-2">Acessar ações pedagógicas</a> --}}
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center">
                <div class="mb-3 text-success fs-1">✅</div>
                <h5 class="card-title fw-bold">Presença</h5>
                <p class="card-text">Controle a participação com check-in via QR Code ou registro manual.</p>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body text-center">
                <div class="mb-3 text-warning fs-1">📊</div>
                <h5 class="card-title fw-bold">Relatórios</h5>
                <p class="card-text">Visualize indicadores de engajamento por ação pedagógica, município ou região.</p>
            </div>
        </div>
    </div>
</div>

<!-- <footer class="text-center text-muted mt-5">
    <small>&copy; {{ date('Y') }} Engaja — Desenvolvido com Laravel</small>
</footer> -->
@endsection
