@extends('layouts.app')

@section('content')

  <div class="container py-4">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <div class="card ev-card">
          <div class="card-body">
            <x-header-atividade :atividade="$atividade" class="mb-4" />
            <h1 class="h5 fw-bold mb-3">Olá, seja bem vindo(a)!</h1>
            <p class="mb-3">Para confirmar a sua presença nesta atividade e/ou responder a avaliação, preencha o campo com
              o seu e-mail, CPF ou
              telefone e clique no botão.<br /></p>
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
                <button type="submit" class="btn btn-primary">Confirmar Presença / Realizar Avaliação</button>
              </div>

              @if (session('show_register_button') && session('error'))
                <a class="btn btn-outline-primary float-end mt-2"
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
          <div class="text-center pt-2 pb-3">
            <p class="mb-0 mt-2">
              Parabéns, <strong>{{ session('usuario_nome') }}</strong>!<br>
              Você confirmou sua presença no momento
              <strong>{{ session('atividade_nome') }}</strong>, ação pedagógica
              <strong>{{ session('evento_nome') }}</strong>,
              realizada na <strong>{{ session('dia') }}</strong>.
            </p>
          </div>

          @php $avaliacaoDisponivel = session('avaliacao_token') && session('avaliacao_disponivel', true); @endphp
          @if(isset($avaliacao) && $avaliacaoDisponivel)
            <div class="text-center py-1">
              <p class="mb-0 mt-2">Para acessar e responder o formulário de avaliação, clique no botão abaixo.
              </p>
            </div>
          @endif
        </div>

        @if(isset($avaliacao) && $avaliacaoDisponivel)
          <div class="modal-footer justify-content-center">
            <a class="btn btn-outline-primary"
              href="{{ session('avaliacao_token') ? route('avaliacao.formulario', ['avaliacao' => $avaliacao, 'token' => session('avaliacao_token')]) : route('avaliacao.formulario', $avaliacao) }}">Formulário
              de Avaliação</a>
          </div>
        @endif
      </div>
    </div>
  </div>

  <div class="modal fade" id="cadastroSistemaModal" tabindex="-1" aria-labelledby="cadastroSistemaModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal modal-dialog-centered mt-1">
      <div class="modal-content">
        <div class="modal-header bg-primary text-white">
          <h5 class="modal-title fw-bold" id="confirmacaoPresencaModalLabel">Dados não encontrados no sistema!</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>

        <div class="modal-body">
          <div class="text-center pt-2 pb-3">
            <p class="mb-0 mt-2">
              Seu registro não foi identificado no sistema.
            </p>
          </div>

          <div class="text-center py-1">
            <p class="mb-0 mt-2">Faça o seu cadastro clicando no botão abaixo para conseguir registrar a sua presença e avaliar a atividade. </p>
          </div>
        </div>


        <div class="modal-footer justify-content-center">
          <a class="btn btn-outline-primary float-end mt-2"
            href="{{ route('evento.cadastro_inscricao', ['evento_id' => $atividade->evento->id, 'atividade_id' => $atividade->id]) }}">
            Cadastre-se
          </a>
        </div>
      </div>
    </div>
  </div>

  @if(session('success-presenca'))
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

  @if(session('show_register_button'))
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('cadastroSistemaModal');
        if (modalEl) {
          const modal = new bootstrap.Modal(modalEl);
          modal.show();
        }
      });
    </script>
  @endif
@endsection