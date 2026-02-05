@csrf

{{-- Momento --}}
<div class="mb-3">
  <label for="descricao" class="form-label">Descrição <span class="text-danger">*</span></label>
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
  <select name="municipios[]" id="municipios" multiple
          class="form-select @error('municipios') is-invalid @enderror @error('municipios.*') is-invalid @enderror"
          size="6">
    @foreach($municipios ?? [] as $m)
      @php
        $uf = $m->estado->sigla ?? '';
        $regiao = $m->estado->regiao->nome ?? '';
        $label = trim(($regiao ? $regiao . ' — ' : '') . $m->nome . ($uf ? ' - ' . $uf : ''));
      @endphp
      <option value="{{ $m->id }}" @selected(in_array((string) $m->id, $municipiosSelecionados, true))>
        {{ $label }}
      </option>
    @endforeach
  </select>
  <div class="form-text">Selecione um ou mais municípios atendidos por este momento.</div>
  @error('municipios') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
  @error('municipios.*') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
</div>

<script>
  // Permite selecionar/deselecionar vários itens sem precisar segurar Ctrl/Cmd.
  document.addEventListener('DOMContentLoaded', function () {
    const select = document.getElementById('municipios');
    if (!select) return;
    select.addEventListener('mousedown', function (e) {
      if (e.target.tagName === 'OPTION') {
        e.preventDefault();
        const opt = e.target;
        opt.selected = !opt.selected;
      }
    });
  });
 </script>

<div class="row g-3">
  <div class="col-md-4">
    <label class="form-label">Dia <span class="text-danger">*</span></label>
    <input type="date" name="dia"
           value="{{ old('dia', isset($atividade)? $atividade->dia : '') }}"
           class="form-control @error('dia') is-invalid @enderror" required>
    @error('dia') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>

  <div class="col-md-4">
    <label class="form-label">Hora de início <span class="text-danger">*</span></label>
    <input type="time" name="hora_inicio"
           value="{{ old('hora_inicio', isset($atividade)? \Illuminate\Support\Str::of($atividade->hora_inicio)->substr(0,5) : '') }}"
           class="form-control @error('hora_inicio') is-invalid @enderror" required>
    @error('hora_inicio') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
  
  <div class="col-md-4">
    <label class="form-label">Hora de término <span class="text-danger">*</span></label>
    <input type="time" name="hora_fim"
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
