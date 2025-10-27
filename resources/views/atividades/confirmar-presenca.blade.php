@extends('layouts.app')

@section('content')

  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card ev-card">
          <div class="card-body">
            <x-header-atividade :atividade="$atividade" class="mb-4" />
            <h1 class="h5 fw-bold mb-3">Olá, seja bem vindo (a)!</h1>
            <p class="mb-3">Para confirmar a sua presença na atividade de hoje, basta preencher o seu E-mail, CPF ou telefone e clicar no botão.<br/></p>
            <form method="POST" action="{{ route('presenca.store', $atividade) }}">
              @csrf
              <div class="mb-3">
                <label for="email" class="form-label">E-mail, CPF ou telefone</label>
                <input type="text" class="form-control" id="campo" name="campo" required>
                @error('campo')
                  <div class="text-danger mt-1">{{ $message }}</div>
                @enderror
              </div>
              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Confirmar Presença</button>
              </div>

              @if (session('show_register_button') && session('error'))
                  <a class="btn btn-outline-primary float-end my-1"
                    href="{{ route('evento.cadastro_inscricao', ['evento_id' => $atividade->evento->id, 'atividade_id' => $atividade->id]) }}">
                    Cadastre-se
                  </a>
              @endif
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection
