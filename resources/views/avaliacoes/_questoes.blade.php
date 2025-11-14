@php
  $formData = $questoesForm ?? [
    'templates' => [],
    'adicionais' => ['cards' => [], 'empty' => true, 'prototype' => ['index' => '__INDEX__', 'questao' => [], 'delete_value' => '0']],
    'options' => ['tipos' => [], 'evidencias' => [], 'escalas' => []],
    'option_maps' => ['tipos' => [], 'evidencias' => [], 'escalas' => []],
  ];

  $optionMaps = $formData['option_maps'] ?? ['tipos' => [], 'evidencias' => [], 'escalas' => []];
  $adicionaisData = $formData['adicionais'] ?? ['cards' => [], 'empty' => true, 'prototype' => ['index' => '__INDEX__', 'questao' => [], 'delete_value' => '0']];
@endphp

<div id="blocos-questoes">
  @foreach ($formData['templates'] as $template)
    <div class="card shadow-sm mb-3 template-questoes {{ $template['active'] ? '' : 'd-none' }}"
      data-template-block="{{ $template['id'] }}">
      <div class="card-header bg-white">
        <h2 class="h6 fw-semibold mb-0">Questoes do modelo: {{ $template['nome'] }}</h2>
      </div>

      <div class="card-body">
        @forelse ($template['questoes'] as $questao)
          <div class="mb-4 question-config" data-questao="{{ $questao['key'] }}">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <span class="form-label fw-semibold mb-0">{{ $questao['card']['label'] }}</span>
              <span class="badge {{ $questao['card']['badge']['class'] ?? '' }}">{{ $questao['card']['badge']['label'] ?? '' }}</span>
            </div>

            <p class="text-muted small mb-2">
              Indicador: {{ $questao['meta']['indicador'] ?? '-' }}
              @if (! empty($questao['meta']['dimensao']))
                &bull; Dimensao: {{ $questao['meta']['dimensao'] }}
              @endif
            </p>

            @if (! empty($questao['fixa']))
              <p class="fw-semibold mb-2">{{ $questao['texto_display'] }}</p>
              <div class="row g-2 text-muted small mb-3">
                <div class="col-md-4">Tipo: {{ $questao['resumo']['tipo'] ?? '' }}</div>
                <div class="col-md-4">Evidencia: {{ $questao['resumo']['evidencia'] ?? 'Sem evidencia' }}</div>
                <div class="col-md-4">Escala: {{ $questao['resumo']['escala'] ?? '---' }}</div>
              </div>
            @else
              <div class="row g-3 align-items-start mb-3">
                <div class="col-12">
                  <label class="form-label small text-muted" for="{{ $questao['form']['texto']['id'] }}">
                    Ajuste o enunciado para esta avaliacao
                  </label>
                  <textarea
                    class="form-control{{ $questao['form']['texto']['error'] ? ' is-invalid' : '' }}"
                    id="{{ $questao['form']['texto']['id'] }}"
                    name="{{ $questao['form']['texto']['name'] }}"
                    rows="3"
                    {{ ! empty($questao['form']['disabled']) ? 'disabled' : '' }}
                  >{{ $questao['form']['texto']['value'] ?? '' }}</textarea>
                  @if ($questao['form']['texto']['error'])
                    <div class="invalid-feedback">{{ $questao['form']['texto']['error'] }}</div>
                  @endif
                </div>

                <div class="col-md-4">
                  <label class="form-label small text-muted" for="{{ $questao['form']['tipo']['id'] }}">Tipo de resposta</label>
                  <select
                    class="form-select{{ $questao['form']['tipo']['error'] ? ' is-invalid' : '' }}"
                    id="{{ $questao['form']['tipo']['id'] }}"
                    name="{{ $questao['form']['tipo']['name'] }}"
                    data-tipo-select
                    {{ ! empty($questao['form']['disabled']) ? 'disabled' : '' }}
                  >
                    @foreach ($questao['form']['tipo']['options'] as $option)
                      <option value="{{ $option['value'] }}" {{ ! empty($option['selected']) ? 'selected' : '' }}>{{ $option['label'] }}</option>
                    @endforeach
                  </select>
                  @if ($questao['form']['tipo']['error'])
                    <div class="invalid-feedback">{{ $questao['form']['tipo']['error'] }}</div>
                  @endif
                </div>

                <div class="col-md-4">
                  <label class="form-label small text-muted" for="{{ $questao['form']['evidencia']['id'] }}">Evidencia</label>
                  <select
                    class="form-select{{ $questao['form']['evidencia']['error'] ? ' is-invalid' : '' }}"
                    id="{{ $questao['form']['evidencia']['id'] }}"
                    name="{{ $questao['form']['evidencia']['name'] }}"
                    data-evidencia-select
                    {{ ! empty($questao['form']['disabled']) ? 'disabled' : '' }}
                  >
                    @foreach ($questao['form']['evidencia']['options'] as $option)
                      <option value="{{ $option['value'] }}" {{ ! empty($option['selected']) ? 'selected' : '' }}>{{ $option['label'] }}</option>
                    @endforeach
                  </select>
                  @if ($questao['form']['evidencia']['error'])
                    <div class="invalid-feedback">{{ $questao['form']['evidencia']['error'] }}</div>
                  @endif
                </div>

                <div class="col-md-4 escala-wrapper {{ ! empty($questao['form']['escala']['visible']) ? '' : 'd-none' }}" data-escala-wrapper>
                  <label class="form-label small text-muted" for="{{ $questao['form']['escala']['id'] }}">
                    Escala (quando tipo = Escala)
                  </label>
                  <select
                    class="form-select{{ $questao['form']['escala']['error'] ? ' is-invalid' : '' }}"
                    id="{{ $questao['form']['escala']['id'] }}"
                    name="{{ $questao['form']['escala']['name'] }}"
                    data-escala-select
                    {{ ! empty($questao['form']['disabled']) ? 'disabled' : '' }}
                  >
                    @foreach ($questao['form']['escala']['options'] as $option)
                      <option value="{{ $option['value'] }}" {{ ! empty($option['selected']) ? 'selected' : '' }}>{{ $option['label'] }}</option>
                    @endforeach
                  </select>
                  @if ($questao['form']['escala']['error'])
                    <div class="invalid-feedback">{{ $questao['form']['escala']['error'] }}</div>
                  @endif
                </div>
              </div>
            @endif

            @if (! empty($questao['resposta']['show']))
              <div class="mt-3">
                <label class="form-label fw-semibold d-block mb-2">Resposta</label>

                @switch($questao['resposta']['tipo'])
                  @case('escala')
                    @if (empty($questao['resposta']['escala_opcoes']))
                      <p class="text-muted small mb-0">
                        Configure opcoes na escala associada antes de registrar respostas.
                      </p>
                    @else
                      <div class="d-flex flex-wrap gap-2">
                        @foreach ($questao['resposta']['escala_opcoes'] as $idx => $opcao)
                          @php $inputId = 'questao-' . $questao['key'] . '-escala-' . $idx; @endphp
                          <div class="form-check">
                            <input class="form-check-input"
                              type="radio"
                              name="respostas[{{ $questao['key'] }}]"
                              id="{{ $inputId }}"
                              value="{{ $opcao }}"
                              {{ (string) ($questao['resposta']['valor'] ?? '') === (string) $opcao ? 'checked' : '' }}
                              {{ ! empty($questao['form']['disabled']) ? 'disabled' : '' }}>
                            <label class="form-check-label" for="{{ $inputId }}">{{ $opcao }}</label>
                          </div>
                        @endforeach
                      </div>
                    @endif
                    @break

                  @case('numero')
                    <input type="number"
                      step="any"
                      class="form-control"
                      name="respostas[{{ $questao['key'] }}]"
                      value="{{ $questao['resposta']['valor'] }}"
                      {{ ! empty($questao['form']['disabled']) ? 'disabled' : '' }}>
                    @break

                  @case('boolean')
                    <div class="d-flex gap-3">
                      @foreach (['1' => 'Sim', '0' => 'Nao'] as $valorBooleano => $rotulo)
                        @php $inputId = 'questao-' . $questao['key'] . '-boolean-' . $valorBooleano; @endphp
                        <div class="form-check">
                          <input class="form-check-input"
                            type="radio"
                            name="respostas[{{ $questao['key'] }}]"
                            id="{{ $inputId }}"
                            value="{{ $valorBooleano }}"
                            {{ (string) ($questao['resposta']['valor'] ?? '') === (string) $valorBooleano ? 'checked' : '' }}
                            {{ ! empty($questao['form']['disabled']) ? 'disabled' : '' }}>
                          <label class="form-check-label" for="{{ $inputId }}">{{ $rotulo }}</label>
                        </div>
                      @endforeach
                    </div>
                    @break

                  @default
                    <textarea class="form-control" rows="3"
                      name="respostas[{{ $questao['key'] }}]"
                      {{ ! empty($questao['form']['disabled']) ? 'disabled' : '' }}>{{ $questao['resposta']['valor'] }}</textarea>
                @endswitch
              </div>
            @endif
          </div>
        @empty
          <p class="text-muted mb-0">Nenhuma questao vinculada a este modelo.</p>
        @endforelse
      </div>
    </div>
  @endforeach
</div>

<div class="card shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <div>
      <h2 class="h6 fw-semibold mb-0">Questoes adicionais</h2>
      <small class="text-muted">Personalize a avaliacao adicionando novas questoes especificas.</small>
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm" id="btn-add-questao-adicional">Adicionar questao</button>
  </div>
  <div class="card-body">
    <div id="questoes-adicionais-container">
      <p class="text-muted small mb-3 {{ ! empty($adicionaisData['empty']) ? '' : 'd-none' }}" data-adicional-empty>Nenhuma questao adicional adicionada.</p>

      @foreach ($adicionaisData['cards'] as $card)
        @php
          $cardClass = ($card['hidden'] ?? false) ? 'd-none' : '';
        @endphp
        @include('templates-avaliacao.partials.questao-fields', [
            'index' => $card['index'],
            'questao' => $card['questao'],
            'evidencias' => $optionMaps['evidencias'] ?? [],
            'escalas' => $optionMaps['escalas'] ?? [],
            'tiposQuestao' => $optionMaps['tipos'] ?? [],
            'namePrefix' => 'questoes_adicionais',
            'errorPrefix' => 'questoes_adicionais',
            'titlePrefix' => 'Questao adicional',
            'scope' => 'adicional',
            'showFixaToggle' => false,
            'textoRequired' => false,
            'tipoRequired' => false,
            'deleteValue' => $card['delete_value'] ?? '0',
            'cardClass' => $cardClass,
        ])
      @endforeach
    </div>
  </div>
</div>

<template id="questao-adicional-template">
  @include('templates-avaliacao.partials.questao-fields', [
      'index' => $adicionaisData['prototype']['index'],
      'questao' => $adicionaisData['prototype']['questao'],
      'evidencias' => $optionMaps['evidencias'] ?? [],
      'escalas' => $optionMaps['escalas'] ?? [],
      'tiposQuestao' => $optionMaps['tipos'] ?? [],
      'namePrefix' => 'questoes_adicionais',
      'errorPrefix' => 'questoes_adicionais',
      'titlePrefix' => 'Questao adicional',
      'scope' => 'adicional',
      'showFixaToggle' => false,
      'textoRequired' => false,
      'tipoRequired' => false,
      'isPrototype' => true,
      'deleteValue' => $adicionaisData['prototype']['delete_value'] ?? '0',
  ])
</template>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const selectTemplate = document.getElementById('template_avaliacao_id');
    const blocks = document.querySelectorAll('[data-template-block]');

  function toggleBlocks() {
    if (!selectTemplate) {
      return;
    }

    const selecionado = selectTemplate.value;
    blocks.forEach(block => {
      const ativo = block.getAttribute('data-template-block') === selecionado;
      block.classList.toggle('d-none', !ativo);
      block.querySelectorAll('input, textarea, select').forEach(field => {
        field.disabled = !ativo;
      });
    });
  }

  function toggleEscala(select) {
    const questionContainer = select.closest('.question-config');
    if (!questionContainer) {
      return;
    }

    const escalaWrapper = questionContainer.querySelector('[data-escala-wrapper]');
    if (!escalaWrapper) {
      return;
    }

    const mostrar = select.value === 'escala';
    escalaWrapper.classList.toggle('d-none', !mostrar);
  }

  if (selectTemplate) {
    selectTemplate.addEventListener('change', toggleBlocks);
    toggleBlocks();
  }

  document.addEventListener('change', (event) => {
    if (event.target.matches('select[name$="[escala_id]"]')) {
      const card = event.target.closest('.question-config');
      if (!card) {
        return;
      }

      const tipoSelect = card.querySelector('[data-tipo-select]');
      if (!tipoSelect) {
        return;
      }

      if (event.target.value && tipoSelect.value !== 'escala') {
        tipoSelect.value = 'escala';
        tipoSelect.dispatchEvent(new Event('change', { bubbles: true }));
      }

      if (!event.target.value && tipoSelect.value === 'escala') {
        tipoSelect.value = 'texto';
        tipoSelect.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }
  });

  function attachTipoListener(select) {
    if (!select || select.dataset.tipoListener === 'true') {
      return;
    }

    select.addEventListener('change', () => toggleEscala(select));
    toggleEscala(select);
    select.dataset.tipoListener = 'true';
  }

  document.querySelectorAll('[data-tipo-select]').forEach(select => attachTipoListener(select));

  const adicionaisContainer = document.getElementById('questoes-adicionais-container');
  const addAdicionalButton = document.getElementById('btn-add-questao-adicional');
  const adicionalTemplate = document.getElementById('questao-adicional-template');

  function setCardInputsDisabled(card, disabled) {
    card.querySelectorAll('input, textarea, select').forEach(field => {
      if (!field.name) {
        return;
      }

      const isDeleteField = field.classList.contains('question-delete-flag') || field.name.endsWith('[_delete]');
      const isIdField = field.name.endsWith('[id]');

      if (disabled) {
        field.disabled = !isDeleteField && !isIdField;
      } else {
        field.disabled = false;
      }
    });
  }

  function markCardAsDeleted(card, setDeleteValue = true) {
    if (!card) {
      return;
    }

    card.classList.add('d-none');
    setCardInputsDisabled(card, true);

    const deleteField = card.querySelector('.question-delete-flag');
    if (deleteField) {
      if (setDeleteValue) {
        deleteField.value = '1';
      }
      deleteField.disabled = false;
    }
  }

  function setupAdditionalCard(card) {
    if (!card) {
      return;
    }

    attachTipoListener(card.querySelector('[data-tipo-select]'));
    setCardInputsDisabled(card, false);
  }

  function updateAdicionaisPositions() {
    if (!adicionaisContainer) {
      return;
    }

    const cards = Array.from(adicionaisContainer.querySelectorAll('[data-question-card][data-question-scope="adicional"]'))
      .filter(card => !card.classList.contains('d-none'));

    cards.forEach((card, index) => {
      const marker = card.querySelector('.question-position');
      if (marker) {
        marker.textContent = index + 1;
      }
    });

    const emptyMessage = adicionaisContainer.querySelector('[data-adicional-empty]');
    if (emptyMessage) {
      emptyMessage.classList.toggle('d-none', cards.length > 0);
    }
  }

  if (adicionaisContainer) {
    adicionaisContainer.addEventListener('click', (event) => {
      const button = event.target.closest('.js-remove-question');
      if (!button) {
        return;
      }

      const card = button.closest('[data-question-card][data-question-scope="adicional"]');
      if (!card) {
        return;
      }

      const deleteField = card.querySelector('.question-delete-flag');
      const isExisting = card.dataset.existing === 'true';

      if (isExisting && deleteField) {
        markCardAsDeleted(card);
      } else {
        card.remove();
      }

      updateAdicionaisPositions();
    });

    adicionaisContainer.querySelectorAll('[data-question-card][data-question-scope="adicional"]').forEach(card => {
      setupAdditionalCard(card);
      const deleteField = card.querySelector('.question-delete-flag');
      if (deleteField && deleteField.value === '1') {
        markCardAsDeleted(card, false);
      }
    });

    updateAdicionaisPositions();
  }

  if (addAdicionalButton && adicionalTemplate && adicionaisContainer) {
    addAdicionalButton.addEventListener('click', () => {
      const cards = Array.from(adicionaisContainer.querySelectorAll('[data-question-card][data-question-scope="adicional"]'));
      const indexes = cards
        .map(card => parseInt(card.dataset.index, 10))
        .filter(value => !Number.isNaN(value));
      const nextIndex = indexes.length ? Math.max(...indexes) + 1 : 0;

      const html = adicionalTemplate.innerHTML.replace(/__INDEX__/g, nextIndex);
      const fragment = document.createRange().createContextualFragment(html);
      const card = fragment.querySelector('[data-question-card]');

      if (!card) {
        return;
      }

      card.dataset.index = String(nextIndex);
      card.dataset.existing = 'false';

      adicionaisContainer.appendChild(fragment);
      setupAdditionalCard(card);

      const deleteField = card.querySelector('.question-delete-flag');
      if (deleteField) {
        deleteField.value = '0';
        deleteField.disabled = false;
      }

      updateAdicionaisPositions();
    });
  }
});
</script>
