<style>
  .form-label[data-required="true"]::after {
    content: ' *';
    color: #dc3545;
    font-weight: 700;
  }

  .municipios-checkbox-list {
    max-height: 18rem;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.75rem;
    background: #fff;
  }

  .municipios-checkbox-item {
    padding: 0.35rem 0 0.35rem 1.5rem;
    border-bottom: 1px solid #f1f3f5;
  }

  .municipios-checkbox-item:last-child {
    border-bottom: 0;
  }
</style>

@csrf

{{-- Momento --}}
<div class="mb-3">
  <label for="descricao" class="form-label">Descrição</label>
  <textarea name="descricao" id="descricao" rows="3"
            class="form-control @error('descricao') is-invalid @enderror"
            required>{{ old('descricao', $atividade->descricao ?? '') }}</textarea>
  @error('descricao') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

@php
  $municipiosSelecionados = collect(old('municipios', isset($atividade) ? $atividade->municipios->pluck('id')->all() : []))
    ->map(fn($v) => (string) $v)
    ->all();
@endphp
<div class="mb-3">
  <label for="municipios" class="form-label">Municípios </label>
  <div id="municipios"
       class="municipios-checkbox-list @error('municipios') is-invalid @enderror @error('municipios.*') is-invalid @enderror">
    @foreach($municipios ?? [] as $m)
      @php
        $uf = $m->estado->sigla ?? '';
        $regiao = $m->estado->regiao->nome ?? '';
        $label = trim(($regiao ? $regiao . ' — ' : '') . $m->nome . ($uf ? ' - ' . $uf : ''));
      @endphp
      <div class="form-check municipios-checkbox-item">
        <input class="form-check-input"
               type="checkbox"
               name="municipios[]"
               id="municipio_{{ $m->id }}"
               value="{{ $m->id }}"
               @checked(in_array((string) $m->id, $municipiosSelecionados, true))>
        <label class="form-check-label small" for="municipio_{{ $m->id }}">
          {{ $label }}
        </label>
      </div>
    @endforeach
  </div>
  <div class="form-text">Selecione um ou mais municípios atendidos por este momento.</div>
  @error('municipios') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
  @error('municipios.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('descricao')?.closest('form');
    if (!form) return;

    // Remove a marcação anterior antes de aplicar o asterisco nos campos required.
    form.querySelectorAll('label[data-required="true"]').forEach(function (label) {
      label.removeAttribute('data-required');
    });

    // O asterisco visual passa a depender apenas do atributo HTML `required`.
    form.querySelectorAll('input[required], select[required], textarea[required]').forEach(function (field) {
      if (!field.id) return;
      const label = form.querySelector(`label[for="${field.id}"]`);
      if (label) {
        label.dataset.required = 'true';
      }
    });
  });
 </script>

<div class="row g-3">
  <div class="col-md-4">
    <label for="dia" class="form-label">Dia</label>
    <input type="date" name="dia" id="dia"
           value="{{ old('dia', isset($atividade)? $atividade->dia : '') }}"
           class="form-control @error('dia') is-invalid @enderror" required>
    @error('dia') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-4">
    <label for="hora_inicio" class="form-label">Hora de início</label>
    <input type="time" name="hora_inicio" id="hora_inicio"
           value="{{ old('hora_inicio', isset($atividade)? \Illuminate\Support\Str::of($atividade->hora_inicio)->substr(0,5) : '') }}"
           class="form-control @error('hora_inicio') is-invalid @enderror" required>
    @error('hora_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  
  <div class="col-md-4">
    <label for="hora_fim" class="form-label">Hora de término</label>
    <input type="time" name="hora_fim" id="hora_fim"
           value="{{ old('hora_fim', isset($atividade)? \Illuminate\Support\Str::of($atividade->hora_fim)->substr(0,5) : '') }}"
           class="form-control @error('hora_fim') is-invalid @enderror" required>
    @error('hora_fim') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-md-6">
    <label class="form-label">Público esperado</label>
    <input type="number" name="publico_esperado" min="0" step="1"
           value="{{ old('publico_esperado', $atividade->publico_esperado ?? '') }}"
           class="form-control @error('publico_esperado') is-invalid @enderror"
           placeholder="Quantas pessoas pretende alcançar">
    @error('publico_esperado') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-6">
    <label class="form-label">Carga horária (horas)</label>
    <input type="number" name="carga_horaria" min="0" step="1"
           value="{{ old('carga_horaria', $atividade->carga_horaria ?? '') }}"
           class="form-control @error('carga_horaria') is-invalid @enderror"
           placeholder="Ex.: 2">
    @error('carga_horaria') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
</div>

@php
  $listaCopiaveis = collect($atividadesCopiaveis ?? []);
@endphp
@if($listaCopiaveis->isNotEmpty())
  <div class="mt-3">
    <label for="copiar_inscritos_de" class="form-label">Importar inscritos</label>
    <select name="copiar_inscritos_de" id="copiar_inscritos_de" class="form-select @error('copiar_inscritos_de') is-invalid @enderror">
      <option value="">Não importar inscritos</option>
      @foreach($listaCopiaveis as $momentoCopiavel)
        @php
          $eventoNome = $momentoCopiavel->evento->nome ?? 'Evento sem título';
          $descricao = $momentoCopiavel->descricao ?: 'Momento';
          $dia = $momentoCopiavel->dia ? \Carbon\Carbon::parse($momentoCopiavel->dia)->format('d/m/Y') : 'Sem data';
          $hora = $momentoCopiavel->hora_inicio ? \Carbon\Carbon::parse($momentoCopiavel->hora_inicio)->format('H:i') : null;
          $inscritos = $momentoCopiavel->inscricoes_count ?? $momentoCopiavel->inscricoes()->count();
          $label = $eventoNome . ' - ' . $descricao . ' (' . $dia . ($hora ? ' • ' . $hora : '') . ') - ' . $inscritos . ' inscrito' . ($inscritos == 1 ? '' : 's');
        @endphp
        <option value="{{ $momentoCopiavel->id }}" @selected(old('copiar_inscritos_de') == $momentoCopiavel->id)>
          {{ $label }}
        </option>
      @endforeach
    </select>
    <div class="form-text">Duplicaremos todos os participantes desse momento no ato do salvamento.</div>
    @error('copiar_inscritos_de') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
@endif

<div class="d-flex justify-content-end gap-2 mt-3">
  <a href="{{ route('eventos.atividades.index', $evento) }}" class="btn btn-outline-secondary">Cancelar</a>
  <button class="btn btn-engaja">{{ $submitLabel ?? 'Salvar' }}</button>
</div>
