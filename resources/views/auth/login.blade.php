@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card shadow-sm">
                <div class="card-header text-center bg-primary text-white fw-bold">
                    {{ __('Entrar no Engaja') }}
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success mb-3">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('E-mail') }}</label>
                            <input id="email" type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" required autofocus>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Senha') }}</label>
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror"
                                name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Remember --}}
                        <div class="mb-3 form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="remember">
                            <label class="form-check-label" for="remember">
                                {{ __('Lembrar-me') }}
                            </label>
                        </div>

                        {{-- Buttons --}}
                        <div class="d-flex justify-content-between align-items-center">
                            {{-- @if (Route::has('password.request'))
                                <a class="btn btn-link p-0" href="{{ route('password.request') }}">
                                    {{ __('Esqueceu a senha?') }}
                                </a>
                            @endif --}}
                            <button type="submit" class="btn btn-primary">
                                {{ __('Entrar') }}
                            </button>
                        </div>
                    </form>
                </div>
                {{-- <div class="card-footer text-center">
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">{{ __('Criar nova conta') }}</a>
                    @endif
                </div> --}}
            </div>
        </div>
    </div>
</div>
@endsection
