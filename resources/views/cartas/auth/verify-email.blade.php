@extends('cartas.auth._shell')

@section('title', 'Verifique seu e-mail - Cartas para Esperançar')
@section('auth-bg-style', 'background-color: #c893df; background-image: none; position: relative; overflow: hidden;')

@section('auth-side-content')
    {{-- Ilustração 1: menina na janela --}}
    <img
        src="{{ asset('images/cartas/ilustracao1-verificar-email.png') }}"
        alt=""
        style="
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -58%);
            width: 65%;
            height: auto;
            object-fit: contain;
            z-index: 1;
            pointer-events: none;
        "
    >
    {{-- Ilustração 2: envelope com notas --}}
    <img
        src="{{ asset('images/cartas/ilustracao2-verificar-email.png') }}"
        alt=""
        style="
            position: absolute;
            bottom: 0;
            right: 0;
            width: 20%;
            height: auto;
            object-fit: contain;
            z-index: 2;
            pointer-events: none;
        "
    >
@endsection

@section('auth-content')
    <h1 class="cartas-title cartas-title--strong">Verifique seu e-mail</h1>
    <p class="cartas-copy">Enviamos uma mensagem de confirmação para seu e-mail. Abra a mensagem e clique no link para validar sua conta.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="cartas-alert">Um novo link de confirmação foi enviado para o e-mail informado no cadastro.</div>
    @endif

    <form method="POST" action="{{ route('cartas.verification.send') }}" class="cartas-form">
        @csrf
        <button type="submit" class="cartas-button">Reenviar e-mail de confirmação</button>
    </form>

    <form method="POST" action="{{ route('logout') }}" style="margin-top:16px;">
        @csrf
        <button type="submit" class="cartas-link" style="background:none;border:none;cursor:pointer;font:inherit;">Sair</button>
    </form>
@endsection
