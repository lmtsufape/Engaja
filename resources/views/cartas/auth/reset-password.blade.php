@extends('cartas.auth._shell')

@section('title', 'Nova senha - Cartas para Esperançar')
@section('auth-bg-style', "background-image: url('" . asset('images/cartas/bg-recuperar-senha.png') . "');")

@section('auth-content')
    <h1 class="cartas-title">Nova senha</h1>

    @if ($errors->any())
        <div class="cartas-alert cartas-alert--error">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('cartas.password.store') }}" class="cartas-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="email" name="email" value="{{ old('email', $request->email) }}" placeholder="E-mail" required readonly>
        </div>
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="password" name="password" placeholder="Nova senha" required autofocus>
        </div>
        <div class="cartas-field-wrap">
            <input class="cartas-field" type="password" name="password_confirmation" placeholder="Confirmar senha" required>
        </div>
        <button type="submit" class="cartas-button">Salvar senha</button>
    </form>
@endsection
