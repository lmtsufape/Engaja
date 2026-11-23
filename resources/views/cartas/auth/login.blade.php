@extends('cartas.auth._shell')

@section('title', 'Entrar - Cartas para Esperançar')
@section('auth-bg-style', "background-image: url('" . asset('images/cartas/bg-login.png') . "');")

@section('auth-content')
    <h1 class="cartas-title">Entrar</h1>

    @if (session('status'))
        <div class="cartas-alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="cartas-alert cartas-alert--error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('cartas.login.store') }}" class="cartas-form">
        @csrf
        <div class="cartas-field-wrap">
            <label class="cartas-label">E-mail</label>
            <input class="cartas-field" type="email" name="email" value="{{ old('email') }}" placeholder="Digite seu e-mail." required autofocus>
        </div>
        <div class="cartas-field-wrap" style="margin-top: 14px;">
            <label class="cartas-label">Senha</label>
            <input class="cartas-field" type="password" name="password" placeholder="Digite sua senha." required>
        </div>

        <button type="submit" class="cartas-button" style="margin-top: 18px;">Entrar</button>
    </form>

@push('styles')
    <style>
        .cartas-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #111;
            margin-bottom: 6px;
        }
    </style>
@endpush

    <div class="cartas-links">
        <a href="{{ route('cartas.register') }}" class="cartas-link">Criar conta</a>
        <a href="{{ route('cartas.password.request') }}" class="cartas-link">Esqueci minha senha</a>
    </div>
    @include('cartas.shared._help_fab')
@endsection
