@extends('layouts.app')

@section('content')

  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card ev-card">
          <div class="card-body">
            <x-header-atividade :atividade="$atividade" class="mb-4" />
            <h1 class="h5 fw-bold mb-3">Olá, seja bem vindo(a)!</h1>
            <p class="mb-3">Para confirmar a sua presença na atividade de hoje, basta preencher o seu e-mail, CPF ou
              telefone e clicar no botão.<br /></p>
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

              {{-- @if (session('show_register_button') && session('error'))
              <a class="btn btn-outline-primary float-end"
                href="{{ route('evento.cadastro_inscricao', ['evento_id' => $atividade->evento->id, 'atividade_id' => $atividade->id]) }}">
                Cadastre-se
              </a>
              @endif --}}
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if (session('success'))
    <script>
      window.addEventListener('load', () => {
        const fakeBtn = document.createElement('button');

        // Passa o nome do usuário para o dataset
        fakeBtn.dataset.user = "Presença confirmada!";


        fakeBtn.dataset.success = `
                                              <div class="text-center">
                                                <i class="bi bi-check-circle-fill text-success fs-3 mb-2"></i><br>
                                                <p class="mb-0 mt-2">
                                                  Parabéns, {{ session('usuario_nome') }}!<br>
                                                  Você confirmou sua presença no momento<br>
                                                  <b>{{ session('atividade_nome') }}</b>, ação pedagógica <b>{{ session('evento_nome') }}</b>,<br>
                                                  realizada na <b>{{ session('dia') }}</b>.
                                                </p>
                                              </div>
                                            `;
        document.body.appendChild(fakeBtn);
        fakeBtn.click();
        fakeBtn.remove();
      });
    </script>
  @endif

  <div class="modal fade" id="confirmacaoPresencaModal" tabindex="-1" aria-labelledby="confirmacaoPresencaModalLabel"
    aria-hidden="true">
    @php
      $avaliacao = \App\Models\Avaliacao::where('atividade_id', $atividade->id)->first();
    @endphp

    <div class="modal-dialog modal modal-dialog-centered mt-1">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold" id="confirmacaoPresencaModalLabel">Presença confirmada!</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>

        <div class="modal-body">
          <div class="text-center py-2">
            <p class="mb-0 mt-2">
              Parabéns, <strong>{{ session('usuario_nome') }}</strong>!<br>
              Você confirmou sua presença no momento<br>
              <strong>{{ session('atividade_nome') }}</strong>, ação pedagógica
              <strong>{{ session('evento_nome') }}</strong>,<br>
              realizada na <strong>{{ session('dia') }}</strong>.
            </p>
          </div>

          @if(isset($avaliacao))
          <div class="text-center py-1">
            <p class="mb-0 mt-2">Para acessar e responder o formulário de avaliação do momento {{ session('atividade_nome') }}, clique no botão abaixo.</p>
          </div>
          @endif
        </div>

        @if(isset($avaliacao))
        <div class="modal-footer justify-content-center">
          <a class="btn btn-outline-primary" href="{{ session('avaliacao_token') ? route('avaliacao.formulario', ['avaliacao' => $avaliacao, 'token' => session('avaliacao_token')]) : route('avaliacao.formulario', $avaliacao) }}">Formulário de Avaliação</a>
        </div>
        @endif
      </div>
    </div>
  </div>

  @if(session('success'))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('confirmacaoPresencaModal');
        if (modalEl) {
          const modal = new bootstrap.Modal(modalEl);
          modal.show();
        }
      });
    </script>
  @endif
@endsection
