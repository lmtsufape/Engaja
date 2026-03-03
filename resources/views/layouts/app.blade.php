<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ config('app.name', 'Engaja') }}</title>
  <link rel="icon" type="image/png" href="{{ asset('images/engaja-favicon.png') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">

  @vite(['resources/sass/app.scss', 'resources/js/app.js'])

  <style>
    :root {
      --engaja-purple: #421944;
    }

    body {
      font-family: 'Montserrat', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }

    .navbar-brand {
      font-weight: 700;
      letter-spacing: .2px;
    }
  </style>
  <style>
    .form-control {
      border-color: #b1b6bbff !important;
      /* cinza escuro padrão Bootstrap */
    }

    .form-control:focus {
      border-color: #421944 !important;
      /* roxo Engaja no foco */
      box-shadow: 0 0 0 0.2rem rgba(66, 25, 68, 0.25);
      /* glow roxo no foco */
    }

    .form-select {
      border-color: #b1b6bbff !important;
    }

    .form-select:focus {
      border-color: #421944 !important;
      box-shadow: 0 0 0 0.2rem rgba(66, 25, 68, 0.25);
    }
  </style>
  @stack('styles')
</head>

<body class="d-flex flex-column min-vh-100">
  @includeWhen(View::exists('layouts.navigation'), 'layouts.navigation')

  @isset($header)
  <header class="bg-white border-bottom py-3">
    <div class="container">{{ $header }}</div>
  </header>
  @endisset

  <div class="row justify-content-center mt-2">
    <div class="col-md-6">
      @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
      @endif
    </div>
  </div>

  @if (session('error'))
  <div class="alert alert-danger text-center">{{ session('error') }}</div>
  @endif

  <main class="flex-grow-1 py-4">
    <div class="container">
      @isset($slot) {{ $slot }} @else @yield('content') @endisset
    </div>
  </main>

  @include('layouts.footer') {{-- <footer class="bg-primary border-top mt-auto pt-5"> ... --}}
  @stack('scripts')

  <div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content shadow-lg border-0">
        <div class="modal-header bg-engaja text-white">
          <h5 class="modal-title" id="confirmModalLabel">Confirmar ação</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <p class="mb-0 js-confirm-message"></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-engaja js-confirm-accept">Confirmar</button>
        </div>
      </div>
    </div>
  </div>

  @if (!empty($exibirModalCompletarPerfil))
  <div class="modal fade"
       id="modalCompletarPerfil"
       tabindex="-1"
       aria-labelledby="modalCompletarPerfilLabel"
       aria-modal="true"
       role="dialog"
       data-bs-backdrop="static"
       data-bs-keyboard="false">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
          <div class="modal-content">

              <div class="modal-header bg-engaja text-white">
                  <h5 class="modal-title" id="modalCompletarPerfilLabel">
                      📋 Complete seu perfil demográfico
                  </h5>
                  {{-- SEM botão de fechar --}}
              </div>

              <div class="modal-body">
                  <p class="text-muted mb-4">
                      Para continuar usando o Engaja, precisamos de algumas informações demográficas.
                      Esses dados são utilizados apenas para fins estatísticos e de políticas públicas.
                  </p>

                  @if ($errors->any())
                  <div class="alert alert-danger">
                      <ul class="mb-0">
                          @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                          @endforeach
                      </ul>
                  </div>
                  @endif

                  <form method="POST" action="{{ route('profile.complete-demographics') }}" id="form-demograficos">
                      @csrf

                      {{-- 1. Identidade de Gênero --}}
                      <div class="mb-3">
                          <label for="identidade_genero_modal" class="form-label fw-semibold">
                              Identidade de Gênero <span class="text-danger">*</span>
                          </label>
                          <select name="identidade_genero" id="identidade_genero_modal"
                                  class="form-select @error('identidade_genero') is-invalid @enderror"
                                  required onchange="toggleOutro(this, 'identidade_genero_outro_wrap_modal')">
                              <option value="" disabled selected>Selecione...</option>
                              <option value="Mulher Cisgênero"   {{ old('identidade_genero') == 'Mulher Cisgênero'   ? 'selected' : '' }}>Mulher Cisgênero</option>
                              <option value="Mulher Transsexual" {{ old('identidade_genero') == 'Mulher Transsexual' ? 'selected' : '' }}>Mulher Transsexual</option>
                              <option value="Homem Cisgênero"    {{ old('identidade_genero') == 'Homem Cisgênero'    ? 'selected' : '' }}>Homem Cisgênero</option>
                              <option value="Homem Transsexual"  {{ old('identidade_genero') == 'Homem Transsexual'  ? 'selected' : '' }}>Homem Transsexual</option>
                              <option value="Travesti"           {{ old('identidade_genero') == 'Travesti'           ? 'selected' : '' }}>Travesti</option>
                              <option value="Não binárie"        {{ old('identidade_genero') == 'Não binárie'        ? 'selected' : '' }}>Não binárie</option>
                              <option value="Prefiro não responder" {{ old('identidade_genero') == 'Prefiro não responder' ? 'selected' : '' }}>Prefiro não responder</option>
                              <option value="Outro"              {{ old('identidade_genero') == 'Outro'              ? 'selected' : '' }}>Outro</option>
                          </select>
                          @error('identidade_genero')
                          <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                          <div id="identidade_genero_outro_wrap_modal" class="mt-2" style="display:none">
                              <input type="text" name="identidade_genero_outro"
                                     class="form-control @error('identidade_genero_outro') is-invalid @enderror"
                                     placeholder="Especifique sua identidade de gênero"
                                     value="{{ old('identidade_genero_outro') }}">
                              @error('identidade_genero_outro')
                              <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
                          </div>
                      </div>

                      {{-- 2. Raça / Cor --}}
                      <div class="mb-3">
                          <label for="raca_cor_modal" class="form-label fw-semibold">
                              Raça / Cor <span class="text-danger">*</span>
                          </label>
                          <select name="raca_cor" id="raca_cor_modal"
                                  class="form-select @error('raca_cor') is-invalid @enderror"
                                  required>
                              <option value="" disabled selected>Selecione...</option>
                              <option value="Preta"                {{ old('raca_cor') == 'Preta'                ? 'selected' : '' }}>Preta</option>
                              <option value="Parda"                {{ old('raca_cor') == 'Parda'                ? 'selected' : '' }}>Parda</option>
                              <option value="Branca"               {{ old('raca_cor') == 'Branca'               ? 'selected' : '' }}>Branca</option>
                              <option value="Amarela"              {{ old('raca_cor') == 'Amarela'              ? 'selected' : '' }}>Amarela</option>
                              <option value="Indígena"             {{ old('raca_cor') == 'Indígena'             ? 'selected' : '' }}>Indígena</option>
                              <option value="Prefere não declarar" {{ old('raca_cor') == 'Prefere não declarar' ? 'selected' : '' }}>Prefere não declarar</option>
                          </select>
                          @error('raca_cor')
                          <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                      </div>

                      {{-- 3. Comunidade Tradicional --}}
                      <div class="mb-3">
                          <label for="comunidade_tradicional_modal" class="form-label fw-semibold">
                              Pertencimento a Povos ou Comunidades Tradicionais <span class="text-danger">*</span>
                          </label>
                          <select name="comunidade_tradicional" id="comunidade_tradicional_modal"
                                  class="form-select @error('comunidade_tradicional') is-invalid @enderror"
                                  required onchange="toggleOutro(this, 'comunidade_tradicional_outro_wrap_modal')">
                              <option value="" disabled selected>Selecione...</option>
                              <option value="Não"                    {{ old('comunidade_tradicional') == 'Não'                    ? 'selected' : '' }}>Não</option>
                              <option value="Povos indígenas"        {{ old('comunidade_tradicional') == 'Povos indígenas'        ? 'selected' : '' }}>Povos indígenas</option>
                              <option value="Comunidades Quilombolas" {{ old('comunidade_tradicional') == 'Comunidades Quilombolas' ? 'selected' : '' }}>Comunidades Quilombolas</option>
                              <option value="Povos Ciganos"          {{ old('comunidade_tradicional') == 'Povos Ciganos'          ? 'selected' : '' }}>Povos Ciganos</option>
                              <option value="Ribeirinhos"            {{ old('comunidade_tradicional') == 'Ribeirinhos'            ? 'selected' : '' }}>Ribeirinhos</option>
                              <option value="Extrativistas"          {{ old('comunidade_tradicional') == 'Extrativistas'          ? 'selected' : '' }}>Extrativistas</option>
                              <option value="Outro"                  {{ old('comunidade_tradicional') == 'Outro'                  ? 'selected' : '' }}>Outro</option>
                          </select>
                          @error('comunidade_tradicional')
                          <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                          <div id="comunidade_tradicional_outro_wrap_modal" class="mt-2" style="display:none">
                              <input type="text" name="comunidade_tradicional_outro"
                                     class="form-control @error('comunidade_tradicional_outro') is-invalid @enderror"
                                     placeholder="Especifique a comunidade tradicional"
                                     value="{{ old('comunidade_tradicional_outro') }}">
                              @error('comunidade_tradicional_outro')
                              <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
                          </div>
                      </div>

                      {{-- 4. Faixa Etária --}}
                      <div class="mb-3">
                          <label for="faixa_etaria_modal" class="form-label fw-semibold">
                              Faixa Etária <span class="text-danger">*</span>
                          </label>
                          <select name="faixa_etaria" id="faixa_etaria_modal"
                                  class="form-select @error('faixa_etaria') is-invalid @enderror"
                                  required>
                              <option value="" disabled selected>Selecione...</option>
                              <option value="Primeira infância (0 a 6 anos)"  {{ old('faixa_etaria') == 'Primeira infância (0 a 6 anos)'  ? 'selected' : '' }}>Primeira infância (0 a 6 anos)</option>
                              <option value="Criança (7 a 11 anos)"           {{ old('faixa_etaria') == 'Criança (7 a 11 anos)'           ? 'selected' : '' }}>Criança (7 a 11 anos)</option>
                              <option value="Adolescente (12 a 17 anos)"      {{ old('faixa_etaria') == 'Adolescente (12 a 17 anos)'      ? 'selected' : '' }}>Adolescente (12 a 17 anos)</option>
                              <option value="Adulto (18 a 59 anos)"           {{ old('faixa_etaria') == 'Adulto (18 a 59 anos)'           ? 'selected' : '' }}>Adulto (18 a 59 anos)</option>
                              <option value="Idoso (a partir dos 60 anos)"    {{ old('faixa_etaria') == 'Idoso (a partir dos 60 anos)'    ? 'selected' : '' }}>Idoso (a partir dos 60 anos)</option>
                          </select>
                          @error('faixa_etaria')
                          <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                      </div>

                      {{-- 5. PcD --}}
                      <div class="mb-3">
                          <label for="pcd_modal" class="form-label fw-semibold">
                              Pessoa com Deficiência (PcD) <span class="text-danger">*</span>
                          </label>
                          <select name="pcd" id="pcd_modal"
                                  class="form-select @error('pcd') is-invalid @enderror"
                                  required>
                              <option value="" disabled selected>Selecione...</option>
                              <option value="Não"         {{ old('pcd') == 'Não'         ? 'selected' : '' }}>Não</option>
                              <option value="Física"      {{ old('pcd') == 'Física'      ? 'selected' : '' }}>Física</option>
                              <option value="Auditiva"    {{ old('pcd') == 'Auditiva'    ? 'selected' : '' }}>Auditiva</option>
                              <option value="Visual"      {{ old('pcd') == 'Visual'      ? 'selected' : '' }}>Visual</option>
                              <option value="Intelectual" {{ old('pcd') == 'Intelectual' ? 'selected' : '' }}>Intelectual</option>
                              <option value="Múltipla"    {{ old('pcd') == 'Múltipla'    ? 'selected' : '' }}>Múltipla</option>
                          </select>
                          @error('pcd')
                          <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                      </div>

                      {{-- 6. Orientação Sexual --}}
                      <div class="mb-3">
                          <label for="orientacao_sexual_modal" class="form-label fw-semibold">
                              Orientação Sexual <span class="text-danger">*</span>
                          </label>
                          <select name="orientacao_sexual" id="orientacao_sexual_modal"
                                  class="form-select @error('orientacao_sexual') is-invalid @enderror"
                                  required onchange="toggleOutro(this, 'orientacao_sexual_outra_wrap_modal')">
                              <option value="" disabled selected>Selecione...</option>
                              <option value="Lésbica"              {{ old('orientacao_sexual') == 'Lésbica'              ? 'selected' : '' }}>Lésbica</option>
                              <option value="Gay"                  {{ old('orientacao_sexual') == 'Gay'                  ? 'selected' : '' }}>Gay</option>
                              <option value="Bissexual"            {{ old('orientacao_sexual') == 'Bissexual'            ? 'selected' : '' }}>Bissexual</option>
                              <option value="Heterossexual"        {{ old('orientacao_sexual') == 'Heterossexual'        ? 'selected' : '' }}>Heterossexual</option>
                              <option value="Prefere não declarar" {{ old('orientacao_sexual') == 'Prefere não declarar' ? 'selected' : '' }}>Prefere não declarar</option>
                              <option value="Outra"                {{ old('orientacao_sexual') == 'Outra'                ? 'selected' : '' }}>Outra</option>
                          </select>
                          @error('orientacao_sexual')
                          <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                          <div id="orientacao_sexual_outra_wrap_modal" class="mt-2" style="display:none">
                              <input type="text" name="orientacao_sexual_outra"
                                     class="form-control @error('orientacao_sexual_outra') is-invalid @enderror"
                                     placeholder="Especifique sua orientação sexual"
                                     value="{{ old('orientacao_sexual_outra') }}">
                              @error('orientacao_sexual_outra')
                              <div class="invalid-feedback">{{ $message }}</div>
                              @enderror
                          </div>
                      </div>

                  </form>
              </div>

              <div class="modal-footer">
                  <button type="submit" form="form-demograficos" class="btn btn-engaja w-100">
                      Salvar e continuar
                  </button>
              </div>

          </div>
      </div>
  </div>

  <script>
      document.addEventListener('DOMContentLoaded', function () {
          const modalEl = document.getElementById('modalCompletarPerfil');
          if (modalEl) {
              const modal = new bootstrap.Modal(modalEl);
              modal.show();

              restoreOutroFields();
          }
      });

      function toggleOutro(select, wrapId) {
          const wrap = document.getElementById(wrapId);
          if (!wrap) return;
          const mostrar = select.value === 'Outro' || select.value === 'Outra';
          wrap.style.display = mostrar ? 'block' : 'none';
          const input = wrap.querySelector('input');
          if (input) input.required = mostrar;
      }

      function restoreOutroFields() {
          [
              { selectId: 'identidade_genero_modal',      wrapId: 'identidade_genero_outro_wrap_modal' },
              { selectId: 'comunidade_tradicional_modal', wrapId: 'comunidade_tradicional_outro_wrap_modal' },
              { selectId: 'orientacao_sexual_modal',      wrapId: 'orientacao_sexual_outra_wrap_modal' },
          ].forEach(({ selectId, wrapId }) => {
              const select = document.getElementById(selectId);
              if (select && (select.value === 'Outro' || select.value === 'Outra')) {
                  const wrap = document.getElementById(wrapId);
                  if (wrap) wrap.style.display = 'block';
              }
          });
      }
  </script>
  @endif

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      
      document.addEventListener('change', function (e) {
          const cb = e.target;
          if (!cb.classList.contains('js-checklist-item')) return;

          const modalId = cb.dataset.modal;
          const total   = parseInt(cb.dataset.total, 10);
          const checked = document.querySelectorAll(
              `#${modalId} .js-checklist-item:checked`
          ).length;

          const card = cb.closest('.checklist-card');
          if (card) card.classList.toggle('checked', cb.checked);

          // ── Contador ────────────────────────────────────
          const counter = document.querySelector(`.js-counter[data-modal="${modalId}"]`);
          if (counter) counter.textContent = `${checked} / ${total}`;

          // ── Barra de progresso ──────────────────────────
          const bar = document.querySelector(`.js-progress[data-modal="${modalId}"]`);
          if (bar) {
              const pct = Math.round((checked / total) * 100);
              bar.style.width = pct + '%';
              bar.setAttribute('aria-valuenow', pct);
          }

          // ── Botão confirmar ─────────
          const btn = document.querySelector(`.js-checklist-confirm[data-modal="${modalId}"]`);
          if (btn) btn.disabled = false; 
      });

      // ── Reset ao fechar ─────────────────────────────────
      document.querySelectorAll('.modal').forEach(function (modalEl) {
          modalEl.addEventListener('hidden.bs.modal', function () {
              const id    = modalEl.id;
              
              if (id === 'modalCompletarPerfil') return;

              const btn = modalEl.querySelector('.js-checklist-confirm');
              if (btn) btn.disabled = false; 
          });
      });

  });
  </script>

</body>
</html>