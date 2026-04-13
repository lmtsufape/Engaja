@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-engaja text-white">
                <h1 class="h5 mb-0">Defina uma nova senha</h1>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Sua senha foi redefinida por um administrador. Para continuar usando o sistema, informe uma nova senha.
                </p>

                <form method="POST" action="{{ route('password.force.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="password" class="form-label">Nova senha</label>
                        <input id="password"
                               type="password"
                               name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               required
                               autocomplete="new-password"
                               autofocus>
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar nova senha</label>
                        <input id="password_confirmation"
                               type="password"
                               name="password_confirmation"
                               class="form-control"
                               required
                               autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn btn-engaja w-100">Atualizar senha</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
