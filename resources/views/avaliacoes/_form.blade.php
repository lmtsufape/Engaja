@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-xl-8">
    <div class="text-center mb-4">
      <h1 class="h3 fw-bold text-engaja mb-1">
        Avaliação — {{ $atividade->descricao }}
      </h1>
      <p class="text-muted mb-0">Conte-nos como foi a experiência neste momento.</p>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <strong>Ops!</strong> Verifique os campos destacados e tente novamente.
      </div>
    @endif

    <div class="card shadow-sm">
      <div class="card-body">
        @php
          $inscricaoExibida = $inscricaoRespondente ?? $avaliacao->inscricao ?? $avaliacao->respostas->first()?->inscricao;
          $participanteNome = $inscricaoExibida?->participante?->user?->name;
          $eventoNome = $inscricaoExibida?->evento?->nome ?? $atividade?->evento?->nome;
          $respostas = $respostasExistentes ?? collect();
          $formBloqueado = $jaRespondeu ?? false;
        @endphp

        <div class="mb-4">
          <p class="mb-0"><strong>Participante:</strong> {{ $participanteNome ?? '—' }}</p>
          <p class="mb-0"><strong>Ação pedagógica:</strong> {{ $eventoNome ?? '—' }}</p>
        </div>

        @if(empty($token))
          <div class="alert alert-warning">Confirme sua presença no momento para gerar o link pessoal desta avaliação.</div>
        @endif

        @if($formBloqueado)
          <div class="alert alert-info">Você já respondeu este formulário. Obrigado pelo retorno!</div>
        @endif

        <form method="POST" action="{{ route('avaliacao.formulario.responder', $avaliacao) }}">
          @csrf
          <input type="hidden" name="token" value="{{ old('token', $token) }}">

          <fieldset @disabled($formBloqueado)>
           <ol class="list-group list-group-flush" style="counter-reset: questao">
            @forelse ($avaliacao->avaliacaoQuestoes as $questao)
              <li class="list-group-item px-0" style="counter-increment: questao">
                <p class="fw-semibold mb-1">
                  <span class="text-muted me-2">{{ $loop->iteration }}.</span>
                  {{ $questao->texto }}
                </p>

                @php $valorAtual = old("respostas.{$questao->id}", $respostas[$questao->id] ?? null); @endphp

                <div class="mt-2">
                  @switch($questao->tipo)
                    @case('numero')
                      <input type="number" step="any" name="respostas[{{ $questao->id }}]" class="form-control"
                        value="{{ $valorAtual }}" placeholder="Digite um número">
                      @break

                    @case('escala')
                      @php $opcoesEscala = collect($questao->escala?->valores ?? []); @endphp
                      @if($opcoesEscala->isNotEmpty())
                        <div class="d-flex flex-column gap-2">
                          @foreach($opcoesEscala as $indice => $opcao)
                            @php $inputId = 'q'.$questao->id.'_'.($indice + 1); @endphp
                            <div class="form-check">
                              <input class="form-check-input" type="radio"
                                name="respostas[{{ $questao->id }}]"
                                id="{{ $inputId }}"
                                value="{{ $opcao }}"
                                {{ (string) $valorAtual === (string) $opcao ? 'checked' : '' }}>
                              <label class="form-check-label" for="{{ $inputId }}">{{ strip_tags($opcao) }}</label>
                            </div>
                          @endforeach
                        </div>
                      @else
                        <p class="text-muted small">Escala não configurada.</p>
                      @endif
                      @break

                    @case('boolean')
                      <div class="d-flex flex-column gap-2">
                        @php $inputSim = 'q'.$questao->id.'_sim'; $inputNao = 'q'.$questao->id.'_nao'; @endphp
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="respostas[{{ $questao->id }}]" value="1" id="{{ $inputSim }}"
                            {{ (string) $valorAtual === '1' ? 'checked' : '' }}>
                          <label class="form-check-label" for="{{ $inputSim }}">Sim</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="radio" name="respostas[{{ $questao->id }}]" value="0" id="{{ $inputNao }}"
                            {{ (string) $valorAtual === '0' ? 'checked' : '' }}>
                          <label class="form-check-label" for="{{ $inputNao }}">Não</label>
                        </div>
                      </div>
                      @break

                    @default
                      <textarea name="respostas[{{ $questao->id }}]" class="form-control" rows="3" placeholder="Compartilhe sua percepção">{{ $valorAtual }}</textarea>
                  @endswitch
                </div>
                @error("respostas.{$questao->id}")
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
              </li>
            @empty
              <li class="list-group-item px-0 text-muted">Nenhuma questão cadastrada para esta avaliação.</li>
            @endforelse
          </ol>
          </fieldset>

          @unless($formBloqueado)
          <div class="text-end mt-4">
            <button type="submit" class="btn btn-primary">
              Enviar avaliação
            </button>
          </div>
          @endunless
        </form>

      </div>
    </div>
  </div>
</div>
@endsection
