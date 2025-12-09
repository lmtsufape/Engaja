@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="card shadow-sm">
                <div class="card-header text-center bg-primary text-white fw-bold">
                    {{ __('Criar conta no Engaja') }}
                </div>

                <div class="card-body">
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        {{-- Name --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Nome') }}</label>
                            <input id="name" type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   name="name" value="{{ old('name') }}" required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('E-mail') }}</label>
                            <input id="email" type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Password --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Senha') }}</label>
                            <input id="password" type="password"
                                   class="form-control @error('password') is-invalid @enderror"
                                   name="password" required autocomplete="new-password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">
                                {{ __('Min. 8 caracteres. Use letras, números e/ou símbolos.') }}
                            </div>
                        </div>

                        {{-- Confirm Password --}}
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label">{{ __('Confirmar senha') }}</label>
                            <input id="password_confirmation" type="password"
                                   class="form-control"
                                   name="password_confirmation" required autocomplete="new-password">
                        </div>

                        {{-- Actions --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <a class="btn btn-link p-0" href="{{ route('login') }}">
                                {{ __('Já tem conta? Entrar') }}
                            </a>
                            <button type="submit" class="btn btn-primary">
                                {{ __('Cadastrar') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="card-footer text-center">
                    <small class="text-muted">
                        {{ __('Ao criar a conta, você concorda com os termos de uso e a política de privacidade.') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
