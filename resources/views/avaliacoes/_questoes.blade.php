@php
  $selectedTemplateId = $selectedTemplateId ?? null;
  $respostasAntigas = $respostasAntigas ?? [];
@endphp

<div id="blocos-questoes">
  @foreach ($templates as $template)
  @php
    $visivel = $selectedTemplateId === $template->id;
  @endphp
    <div class="card shadow-sm mb-3 template-questoes {{ $visivel ? '' : 'd-none' }}" data-template-block="{{ $template->id }}">
      @php
        $questoes = $template->questoes;
      @endphp
    <div class="card-header bg-white">
      <h2 class="h6 fw-semibold mb-0">Questões do modelo: {{ $template->nome }}</h2>
    </div>
    <div class="card-body">
        @forelse ($questoes as $questao)
      @php
        $campo = "respostas.{$questao->id}";
        $valor = old($campo, $respostasAntigas[$questao->id] ?? null);
        $options = collect([$questao->escala->opcao1 ?? null, $questao->escala->opcao2 ?? null, $questao->escala->opcao3 ?? null,
          $questao->escala->opcao4 ?? null, $questao->escala->opcao5 ?? null])->filter();
      @endphp
      <div class="mb-4">
        <label class="form-label fw-semibold">{{ $questao->texto }}</label>
        <p class="text-muted small mb-2">
          Indicador: {{ $questao->indicador->descricao ?? '—' }} • Dimensão: {{ $questao->indicador->dimensao->descricao ?? '—' }}
        </p>

        @switch($questao->tipo)
          @case('escala')
            @if ($options->isEmpty())
              <p class="text-muted">Configure opções na escala associada antes de coletar respostas.</p>
            @else
              <div class="d-flex flex-wrap gap-2">
                @foreach ($options as $idx => $opcao)
                  @php $inputId = 'questao-'.$questao->id.'-escala-'.$idx; @endphp
                  <div class="form-check">
                    <input class="form-check-input" type="radio"
                      name="respostas[{{ $questao->id }}]" id="{{ $inputId }}"
                      value="{{ $opcao }}" {{ (string)$valor === (string)$opcao ? 'checked' : '' }}>
                    <label class="form-check-label" for="{{ $inputId }}">{!! $opcao !!}</label>
                  </div>
                @endforeach
              </div>
            @endif
            @break

          @case('numero')
            <input type="number" step="any" class="form-control"
              name="respostas[{{ $questao->id }}]"
              value="{{ $valor }}">
            @break

          @case('boolean')
            <div class="d-flex gap-3">
              @php
                $opcoesBoolean = ['1' => 'Sim', '0' => 'Não'];
              @endphp
              @foreach ($opcoesBoolean as $opcaoValor => $rotulo)
                @php $inputId = 'questao-'.$questao->id.'-bool-'.$opcaoValor; @endphp
                <div class="form-check">
                  <input class="form-check-input" type="radio"
                    name="respostas[{{ $questao->id }}]" id="{{ $inputId }}"
                    value="{{ $opcaoValor }}"
                    {{ (string)$valor === (string)$opcaoValor ? 'checked' : '' }}>
                  <label class="form-check-label" for="{{ $inputId }}">{{ $rotulo }}</label>
                </div>
              @endforeach
            </div>
            @break

          @default
            <textarea class="form-control" rows="3" name="respostas[{{ $questao->id }}]">{{ $valor }}</textarea>
        @endswitch
      </div>
      @empty
      <p class="text-muted mb-0">Nenhuma questão vinculada a este modelo.</p>
      @endforelse
    </div>
  </div>
  @endforeach
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const selectTemplate = document.getElementById('template_avaliacao_id');
    if (!selectTemplate) {
      return;
    }

    const blocks = document.querySelectorAll('[data-template-block]');

    function toggleBlocks() {
      const selecionado = selectTemplate.value;
      blocks.forEach(block => {
        block.classList.toggle('d-none', block.getAttribute('data-template-block') !== selecionado);
      });
    }

    selectTemplate.addEventListener('change', toggleBlocks);
    toggleBlocks();
  });
</script>
