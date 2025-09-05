@extends('layouts.app')

@section('content')

@session('error')
<div class="alert alert-danger">
  {{ session('error') }}
</div>
@endsession

<div class="container py-4">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card ev-card">
        <div class="card-body">
          <h1 class="h5 fw-bold mb-3">Confirmar Presença</h1>
          <p class="mb-3">Para confirmar sua presença, por favor, insira seu e-mail, CPF ou telefone abaixo:</p>
          <form method="POST" action="{{ route('presenca.store', $atividade) }}">
            @csrf
            <div class="mb-3">
              <label for="email" class="form-label">E-mail, CPF ou telefone</label>
              <input type="text" class="form-control" id="campo" name="campo" required>
              @error('campo')
                <div class="text-danger mt-1">{{ $message }}</div>
              @enderror
            </div>
            <button type="submit" class="btn btn-primary">Confirmar Presença</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
