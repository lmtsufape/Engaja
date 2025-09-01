@csrf
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
    <label class="form-label">Carga horária (horas) <span class="text-danger">*</span></label>
    <input type="number" min="1" name="carga_horaria"
           value="{{ old('carga_horaria', $atividade->carga_horaria ?? '') }}"
           class="form-control @error('carga_horaria') is-invalid @enderror" required>
    @error('carga_horaria') <div class="invalid-feedback">{{ $message }}</div> @enderror
  </div>
</div>

<div class="d-flex justify-content-end gap-2 mt-3">
  <a href="{{ route('eventos.atividades.index', $evento) }}" class="btn btn-outline-secondary">Cancelar</a>
  <button class="btn btn-engaja">{{ $submitLabel ?? 'Salvar' }}</button>
</div>
