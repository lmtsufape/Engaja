@php
  $action = $action ?? '#';
  $method = strtoupper($method ?? 'POST');
  $submitLabel = $submitLabel ?? 'Salvar modelo';
  $cancelUrl = $cancelUrl ?? route('templates-avaliacao.index');
  $template = $template ?? null;
  // Build evidencias list if not provided by controller
  if (!isset($evidencias)) {
    $evidencias = \App\Models\Evidencia::with('indicador.dimensao')
      ->orderBy('descricao')
      ->get()
      ->mapWithKeys(function ($e) {
        $prefix = '';
        if ($e->indicador && $e->indicador->dimensao) {
          $prefix = $e->indicador->dimensao->descricao . ' - ' . ($e->indicador->descricao ?? '');
        } elseif ($e->indicador) {
          $prefix = $e->indicador->descricao ?? '';
        }
        return [$e->id => trim($prefix ? ($prefix . ' | ' . $e->descricao) : $e->descricao)];
      });
  }

  $emptyQuestao = [
    'id' => null,
    'indicador_id' => '',
    'evidencia_id' => '',
    'escala_id' => '',
    'texto' => '',
    'tipo' => 'texto',
    'ordem' => null,
    'fixa' => false,
  ];

  $questoesForm = collect(old('questoes'));

  if ($questoesForm->isEmpty() && $template) {
    $questoesForm = $template->questoes
      ->map(fn ($questao) => [
        'id' => $questao->id,
        'indicador_id' => $questao->indicador_id,
        'evidencia_id' => $questao->evidencia_id,
        'escala_id' => $questao->escala_id,
        'texto' => $questao->texto,
        'tipo' => $questao->tipo,
        'ordem' => $questao->ordem,
        'fixa' => $questao->fixa,
      ]);
  }

  $questoesForm = $questoesForm
    ->map(fn ($questao) => array_merge($emptyQuestao, $questao))
    ->values();

  if ($questoesForm->isEmpty()) {
    $questoesForm = collect([$emptyQuestao]);
  }
@endphp

<form method="POST" action="{{ $action }}">
  @csrf
  @if ($method !== 'POST')
  @method($method)
  @endif

  <div class="row g-3">
    <div class="col-md-6">
      <label for="nome" class="form-label">Nome</label>
      <input type="text" id="nome" name="nome" class="form-control @error('nome') is-invalid @enderror"
        value="{{ old('nome', optional($template)->nome) }}" required>
      @error('nome')
      <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>

    <div class="col-md-6">
      <label for="descricao" class="form-label">Descrição</label>
      <input type="text" id="descricao" name="descricao"
        class="form-control @error('descricao') is-invalid @enderror"
        value="{{ old('descricao', optional($template)->descricao) }}">
      @error('descricao')
      <div class="invalid-feedback">{{ $message }}</div>
      @enderror
    </div>
  </div>

  <div class="mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div>
        <h2 class="h6 fw-semibold text-uppercase text-muted mb-1">Questões do modelo</h2>
        <p class="text-muted small mb-0">Adicione, remova ou reordene as questões associadas a este modelo.</p>
      </div>
    </div>

    <div id="questoes-container">
      @foreach ($questoesForm as $index => $questao)
        @include('templates-avaliacao.partials.questao-fields', [
          'index' => $index,
          'questao' => $questao,
          'evidencias' => $evidencias,
          'escalas' => $escalas,
          'tiposQuestao' => $tiposQuestao,
        ])
      @endforeach
    </div>
    @error('questoes')
    <div class="text-danger small mt-2">{{ $message }}</div>
    @enderror
  </div>

  <template id="questao-template">
    @include('templates-avaliacao.partials.questao-fields', [
      'index' => '__INDEX__',
      'questao' => $emptyQuestao,
      'evidencias' => $evidencias,
      'escalas' => $escalas,
      'tiposQuestao' => $tiposQuestao,
      'isPrototype' => true,
    ])
  </template>

  <div class="d-flex justify-content-between mt-4">
    <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Cancelar</a>
    <div>
      <button type="button" class="btn btn-engaja js-add-question me-2">Adicionar questão</button>
      <button type="submit" class="btn btn-engaja">{{ $submitLabel }}</button>
    </div>
  </div>
</form>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('#questoes-container');
    const template = document.querySelector('#questao-template');
  const addButtons = Array.from(document.querySelectorAll('.js-add-question'));

    if (!container || !template) {
      return;
    }

    const getCurrentIndexes = () => Array
      .from(container.querySelectorAll('[data-question-card]'))
      .map((element) => parseInt(element.dataset.index, 10))
      .filter((value) => !Number.isNaN(value));

    let nextIndex = Math.max(-1, ...getCurrentIndexes()) + 1;

    const updatePositions = () => {
      const visibleCards = Array.from(container.querySelectorAll('[data-question-card]'))
        .filter((card) => !card.classList.contains('d-none'));

      visibleCards.forEach((card, position) => {
        const label = card.querySelector('.question-position');
        if (label) {
          label.textContent = position + 1;
        }

        const ordemInput = card.querySelector('input[name$="[ordem]"]');
        if (ordemInput) {
          ordemInput.value = position + 1;
        }
      });
    };

    const addQuestion = () => {
      const index = nextIndex++;
      const html = template.innerHTML.replace(/__INDEX__/g, index);
      const fragment = document.createRange().createContextualFragment(html);

      container.appendChild(fragment);
      updatePositions();
      // Ensure new question fields have correct required state and visibility
      applyEvidenciaRequiredRules();
      applyEscalaVisibility();
    };

    // Attach click handler to all add-question buttons
    addButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        addQuestion();
      });
    });

    container.addEventListener('click', (event) => {
      const button = event.target.closest('.js-remove-question');
      if (!button) {
        return;
      }

      event.preventDefault();

      const card = button.closest('[data-question-card]');
      if (!card) {
        return;
      }

      const deleteField = card.querySelector('.question-delete-flag');
      const isExisting = card.dataset.existing === 'true';

      if (isExisting && deleteField) {
        deleteField.value = '1';
        card.classList.add('d-none');
      } else {
        card.remove();
      }

      updatePositions();
    });

    // When an escala is selected, automatically switch the tipo to 'escala'.
    // If escala is cleared and tipo was previously set to 'escala', revert to 'texto'.
    container.addEventListener('change', (event) => {
      const target = event.target;

      // Match any escala select: name ends with [escala_id]
      if (target.matches('select[name$="[escala_id]"]')) {
        const card = target.closest('[data-question-card]');
        if (!card) return;

        const tipoSelect = card.querySelector('select[name$="[tipo]"]');
        if (!tipoSelect) return;

        const escalaValue = target.value;
        // If an escala was chosen, set tipo to 'escala'
        if (escalaValue) {
          if (tipoSelect.value !== 'escala') {
            tipoSelect.value = 'escala';
            tipoSelect.dispatchEvent(new Event('change', { bubbles: true }));
          }
        } else {
          // If escala cleared and tipo currently 'escala', revert to 'texto'
          if (tipoSelect.value === 'escala') {
            // choose 'texto' as fallback default
            tipoSelect.value = 'texto';
            tipoSelect.dispatchEvent(new Event('change', { bubbles: true }));
          }
        }
      }
    });

    // Show/hide escala field based on tipo select
    function applyEscalaVisibility() {
      const cards = Array.from(container.querySelectorAll('[data-question-card]'))
        .filter(card => !card.classList.contains('d-none'));
      cards.forEach(card => {
        const tipoSelect = card.querySelector('select[name$="[tipo]"]');
        const escalaWrapper = card.querySelector('[data-escala-wrapper]');
        if (!tipoSelect || !escalaWrapper) return;
        if (tipoSelect.value === 'escala') {
          escalaWrapper.style.display = '';
        } else {
          escalaWrapper.style.display = 'none';
          // Clear any selected escala
          const escalaSelect = card.querySelector('select[name$="[escala_id]"]');
          if (escalaSelect) escalaSelect.value = '';
        }
      });
    }

    // Listen for tipo changes to toggle escala visibility
    container.addEventListener('change', event => {
      const target = event.target;
      if (target.matches('select[name$="[tipo]"]')) {
        const card = target.closest('[data-question-card]');
        const escalaWrapper = card.querySelector('[data-escala-wrapper]');
        if (!escalaWrapper) return;
        if (target.value === 'escala') {
          escalaWrapper.style.display = '';
        } else {
          escalaWrapper.style.display = 'none';
          const escalaSelect = card.querySelector('select[name$="[escala_id]"]');
          if (escalaSelect) escalaSelect.value = '';
        }
      }
    });

    // Toggle evidencia required attribute based on fixa checkbox.
    function applyEvidenciaRequiredRules() {
      const cards = Array.from(container.querySelectorAll('[data-question-card]'))
        .filter((card) => !card.classList.contains('d-none'));

      cards.forEach((card) => {
        const fixaCheckbox = card.querySelector("input[type=\"checkbox\"][name$=\"[fixa]\"]");
        const evidenciaSelect = card.querySelector("select[name$=\"[evidencia_id]\"]");
        if (!evidenciaSelect) return;

        if (fixaCheckbox && fixaCheckbox.checked) {
          evidenciaSelect.setAttribute('required', 'required');
        } else {
          evidenciaSelect.removeAttribute('required');
        }
      });
    }

    // Listen for fixa change to update required rule live
    container.addEventListener('change', (event) => {
      const target = event.target;
      if (target.matches("input[type=\"checkbox\"][name$=\"[fixa]\"]")) {
        const card = target.closest('[data-question-card]');
        if (!card) return;
        const evidenciaSelect = card.querySelector("select[name$=\"[evidencia_id]\"]");
        if (!evidenciaSelect) return;

        if (target.checked) {
          evidenciaSelect.setAttribute('required', 'required');
        } else {
          evidenciaSelect.removeAttribute('required');
        }
      }
    });

    // Apply rules on initial load
    applyEvidenciaRequiredRules();
    applyEscalaVisibility();

    updatePositions();
  });
</script>
