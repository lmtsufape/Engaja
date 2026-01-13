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

                        {{-- E-mail ou CPF --}}
                        <div class="mb-3">
                            <label for="login" class="form-label">{{ __('E-mail ou CPF') }}</label>
                            <input id="login" type="text"
                                class="form-control @error('login') is-invalid @enderror"
                                name="login" value="{{ old('login') }}" required autofocus>
                            @error('login')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <script>
                          document.addEventListener('DOMContentLoaded', function () {
                            const loginInput = document.getElementById('login');
                            if (!loginInput) return;

                            const onlyDigits = (str) => (str || '').replace(/\D+/g, '').slice(0, 11);
                            const maskCPF = (digits) => {
                              const d = onlyDigits(digits);
                              const p1 = d.slice(0, 3);
                              const p2 = d.slice(3, 6);
                              const p3 = d.slice(6, 9);
                              const p4 = d.slice(9, 11);
                              let out = p1;
                              if (p2) out += '.' + p2;
                              if (p3) out += '.' + p3;
                              if (p4) out += '-' + p4;
                              return out;
                            };

                            const hasLettersOrAt = (value) => /[A-Za-z@]/.test(value);

                            const maybeMask = (value) => {
                              // se tem letras ou @, assume e-mail e não mexe
                              if (hasLettersOrAt(value)) return value;
                              const digits = onlyDigits(value);
                              if (digits.length === 0) return value;
                              return maskCPF(digits);
                            };

                            const applyMask = (e) => {
                              const originalPos = e.target.selectionStart;
                              const masked = maybeMask(e.target.value);
                              e.target.value = masked;
                              // move cursor para o fim para evitar quebra da digitação
                              e.target.setSelectionRange(masked.length, masked.length);
                            };

                            loginInput.addEventListener('input', applyMask);
                            loginInput.addEventListener('blur', applyMask);
                          });
                        </script>

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
                            @if (Route::has('password.request'))
                                <a class="btn btn-link p-0" href="{{ route('password.request') }}">
                                    {{ __('Esqueceu a senha?') }}
                                </a>
                            @endif
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
