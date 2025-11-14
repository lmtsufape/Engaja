@php
  /** @var int|string $index */
  $errorsBag = $errors ?? app('view')->shared('errors');
  $isPrototype = $isPrototype ?? false;
  $namePrefix = $namePrefix ?? 'questoes';
  $errorPrefix = $errorPrefix ?? $namePrefix;
  $scope = $scope ?? 'template';
  $titlePrefix = $titlePrefix ?? 'Questao';
  $showFixaToggle = $showFixaToggle ?? true;
  $textoRequired = $textoRequired ?? ! $isPrototype;
  $tipoRequired = $tipoRequired ?? ! $isPrototype;
  $deleteValueRaw = $deleteValue ?? '0';
  $deleteValue = (string) $deleteValueRaw === '1' ? '1' : '0';

  $questaoId = $questao['id'] ?? null;
  $baseKey = $errorPrefix . '.' . $index;
  $evidenciaErro = ! $isPrototype && $errorsBag->has("$baseKey.evidencia_id");
  $textoErro = ! $isPrototype && $errorsBag->has("$baseKey.texto");
  $tipoErro = ! $isPrototype && $errorsBag->has("$baseKey.tipo");
  $escalaErro = ! $isPrototype && $errorsBag->has("$baseKey.escala_id");
  $ordemErro = ! $isPrototype && $errorsBag->has("$baseKey.ordem");
  $fixaErro = ! $isPrototype && $errorsBag->has("$baseKey.fixa");
  $questionPosition = is_numeric($index) ? ((int) $index + 1) : '';

  $fieldName = static function (string $field) use ($namePrefix, $index): string {
      return $namePrefix . '[' . $index . '][' . $field . ']';
  };

  $fieldId = static function (string $field) use ($namePrefix, $index): string {
      return $namePrefix . '-' . $index . '-' . $field;
  };

  $cardClass = trim('card shadow-sm mb-3 question-item question-config ' . ($cardClass ?? ''));

  $cardAttributes = $cardAttributes ?? [];
  $cardAttributes['data-question-card'] = 'true';
  $cardAttributes['data-index'] = $index;
  $cardAttributes['data-existing'] = $questaoId ? 'true' : 'false';
  $cardAttributes['data-question-scope'] = $scope;

  $attributesString = '';
  foreach ($cardAttributes as $attr => $value) {
      $attributesString .= ' ' . $attr . '="' . e((string) $value, false) . '"';
  }
@endphp

<div class="{{ $cardClass }}"{!! $attributesString !!}>
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <h3 class="h6 fw-semibold mb-0">{{ $titlePrefix }} <span class="question-position">{{ $questionPosition }}</span></h3>
      <button type="button" class="btn btn-sm btn-outline-danger js-remove-question">Remover</button>
    </div>

    <input type="hidden" name="{{ $fieldName('id') }}" value="{{ $questaoId }}">
    <input type="hidden" name="{{ $fieldName('_delete') }}" value="{{ $deleteValue }}" class="question-delete-flag">

    <div class="row g-3 align-items-start">
      <div class="col-md-6">
        <label for="{{ $fieldId('evidencia_id') }}" class="form-label">Evidencia</label>
        <select id="{{ $fieldId('evidencia_id') }}" name="{{ $fieldName('evidencia_id') }}"
          class="form-select{{ $evidenciaErro ? ' is-invalid' : '' }}">
          <option value="">Selecione...</option>
          @foreach (($evidencias ?? []) as $id => $descricao)
          <option value="{{ $id }}" @selected((string) ($questao['evidencia_id'] ?? '') === (string) $id)>{{ $descricao }}</option>
          @endforeach
        </select>
        <div class="form-text">O indicador sera associado automaticamente pela evidencia escolhida.</div>
        @if ($evidenciaErro)
        <div class="invalid-feedback">{{ $errorsBag->first("$baseKey.evidencia_id") }}</div>
        @endif
      </div>

      <div class="col-md-3">
        <label for="{{ $fieldId('tipo') }}" class="form-label">Tipo de resposta</label>
        <select id="{{ $fieldId('tipo') }}" name="{{ $fieldName('tipo') }}"
          class="form-select{{ $tipoErro ? ' is-invalid' : '' }}" {{ $tipoRequired ? 'required' : '' }} data-tipo-select>
          @foreach ($tiposQuestao as $valor => $rotulo)
          <option value="{{ $valor }}" @selected((string) ($questao['tipo'] ?? 'texto') === (string) $valor)>{{ $rotulo }}</option>
          @endforeach
        </select>
        @if ($tipoErro)
        <div class="invalid-feedback">{{ $errorsBag->first("$baseKey.tipo") }}</div>
        @endif
      </div>

      <div class="col-md-3">
        <label for="{{ $fieldId('ordem') }}" class="form-label">Ordem</label>
        <input type="number" id="{{ $fieldId('ordem') }}" name="{{ $fieldName('ordem') }}" min="1" max="999"
          class="form-control{{ $ordemErro ? ' is-invalid' : '' }}" value="{{ $questao['ordem'] ?? '' }}" placeholder="1, 2, 3...">
        @if ($ordemErro)
        <div class="invalid-feedback">{{ $errorsBag->first("$baseKey.ordem") }}</div>
        @endif
      </div>
    </div>

    <div class="row g-3 align-items-start mt-1">
      <div class="col-md-8">
        <label for="{{ $fieldId('texto') }}" class="form-label">Enunciado</label>
        <textarea id="{{ $fieldId('texto') }}" name="{{ $fieldName('texto') }}" rows="3"
          class="form-control{{ $textoErro ? ' is-invalid' : '' }}" {{ $textoRequired ? 'required' : '' }}>{{ $questao['texto'] ?? '' }}</textarea>
        @if ($textoErro)
        <div class="invalid-feedback">{{ $errorsBag->first("$baseKey.texto") }}</div>
        @endif
      </div>

      <div class="col-md-4 escala-field" data-escala-wrapper>
        <label for="{{ $fieldId('escala_id') }}" class="form-label">Escala (quando tipo = Escala)</label>
        <select id="{{ $fieldId('escala_id') }}" name="{{ $fieldName('escala_id') }}"
          class="form-select{{ $escalaErro ? ' is-invalid' : '' }}">
          <option value="">Selecione...</option>
          @foreach ($escalas as $id => $descricao)
          <option value="{{ $id }}" @selected((string) ($questao['escala_id'] ?? '') === (string) $id)>{{ $descricao }}</option>
          @endforeach
        </select>
        @if ($escalaErro)
        <div class="invalid-feedback">{{ $errorsBag->first("$baseKey.escala_id") }}</div>
        @endif
      </div>
    </div>

    @if ($showFixaToggle)
    <div class="form-check form-switch mt-3">
      <input class="form-check-input{{ $fixaErro ? ' is-invalid' : '' }}" type="checkbox" role="switch"
        id="{{ $fieldId('fixa') }}" name="{{ $fieldName('fixa') }}" value="1" @checked(!empty($questao['fixa']))>
      <label class="form-check-label" for="{{ $fieldId('fixa') }}">Questao fixa</label>
      @if ($fixaErro)
      <div class="invalid-feedback d-block">{{ $errorsBag->first("$baseKey.fixa") }}</div>
      @endif
    </div>
    @endif
  </div>
</div>

