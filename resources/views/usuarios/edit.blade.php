@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
  <div>
    <p class="text-uppercase text-muted small mb-1">Administracao</p>
    <h1 class="h4 fw-bold text-engaja mb-0">Editar usuario</h1>
    <div class="text-muted small">Atualize dados cadastrais e do participante.</div>
  </div>
  <a href="{{ route('usuarios.index') }}" class="btn btn-outline-secondary btn-sm">Voltar</a>
</div>

<form method="POST" action="{{ route('usuarios.update', $user) }}" class="needs-validation" novalidate>
  @csrf
  @method('put')

  <div class="row g-4">
    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <strong>Dados do usuario</strong>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label for="name" class="form-label">Nome</label>
              <input id="name" type="text" name="name"
                value="{{ old('name', $user->name) }}"
                class="form-control @error('name') is-invalid @enderror" required>
              @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="email" class="form-label">E-mail</label>
              <input id="email" type="email" name="email"
                value="{{ old('email', $user->email) }}"
                class="form-control @error('email') is-invalid @enderror" required>
              @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>

        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <strong>Dados do participante</strong>
        </div>
        <div class="card-body">
          @php
          $participante = $user->participante ?? null;
          @endphp
          <div class="row g-3">
            <div class="col-md-6">
              @php
                $cpfRaw = old('cpf', $participante->cpf ?? '');
                $cpfDigits = preg_replace('/\D+/', '', $cpfRaw);
                $cpfFormatado = strlen($cpfDigits) === 11
                  ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfDigits)
                  : $cpfRaw;
              @endphp
              <label for="cpf" class="form-label">CPF</label>
              <input id="cpf" type="text" name="cpf"
                inputmode="numeric" maxlength="14"
                value="{{ $cpfFormatado }}"
                class="form-control @error('cpf') is-invalid @enderror"
                placeholder="000.000.000-00">
              @error('cpf') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="telefone" class="form-label">Telefone</label>
              <input id="telefone" type="text" name="telefone"
                inputmode="numeric" maxlength="15"
                value="{{ old('telefone', $participante->telefone ?? '') }}"
                class="form-control @error('telefone') is-invalid @enderror"
                placeholder="(99) 99999-9999">
              @error('telefone') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="tipo_organizacao" class="form-label">Tipo de instituição</label>
              @php $currentTipoOrg = old('tipo_organizacao', $participante->tipo_organizacao ?? ''); @endphp
              <select id="tipo_organizacao" name="tipo_organizacao"
                class="form-select @error('tipo_organizacao') is-invalid @enderror">
                <option value="">Selecione...</option>
                @foreach($organizacoes as $org)
                <option value="{{ $org }}" @selected($currentTipoOrg===$org)>{{ $org }}</option>
                @endforeach
              </select>
              @error('tipo_organizacao') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="escola_unidade" class="form-label">Instituição</label>
              <input id="escola_unidade" type="text" name="escola_unidade"
                value="{{ old('escola_unidade', $participante->escola_unidade ?? '') }}"
                class="form-control @error('escola_unidade') is-invalid @enderror">
              @error('escola_unidade') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="tag" class="form-label">Vinculo no projeto</label>
              <select id="tag" name="tag" class="form-select @error('tag') is-invalid @enderror">
                <option value="">Selecione...</option>
                @foreach($participanteTags as $tagOption)
                <option value="{{ $tagOption }}" @selected(old('tag', $participante->tag ?? '') === $tagOption)>
                  {{ $tagOption }}
                </option>
                @endforeach
              </select>
              @error('tag') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-md-6">
              <label for="municipio_id" class="form-label">Municipio</label>
              <select id="municipio_id" name="municipio_id"
                class="form-select @error('municipio_id') is-invalid @enderror">
                <option value="">-- Nenhum --</option>
                @foreach($municipios as $m)
                <option value="{{ $m->id }}" @selected((string)old('municipio_id', $participante->municipio_id ?? '') === (string)$m->id)>
                  {{ $m->nome }} @if($m->estado?->sigla) - {{ $m->estado->sigla }}@endif
                </option>
                @endforeach
              </select>
              @error('municipio_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex justify-content-end mt-4">
    <button type="submit" class="btn btn-engaja">Salvar usuario</button>
  </div>
</form>

<script>
  const onlyDigits = s => (s || '').replace(/\D+/g, '');

  function maskCPF(v) {
    const d = onlyDigits(v).slice(0, 11);
    const p1 = d.slice(0, 3);
    const p2 = d.slice(3, 6);
    const p3 = d.slice(6, 9);
    const p4 = d.slice(9, 11);
    let out = p1;
    if (p2) out += '.' + p2;
    if (p3) out += '.' + p3;
    if (p4) out += '-' + p4;
    return out;
  }

  function maskPhone(v) {
    const d = onlyDigits(v).slice(0, 11);
    const is11 = d.length > 10;
    const dd = d.slice(0, 2);
    const p1 = is11 ? d.slice(2, 7) : d.slice(2, 6);
    const p2 = is11 ? d.slice(7, 11) : d.slice(6, 10);
    let out = '';
    if (dd) out = `(${dd}`;
    if (dd && (p1 || p2)) out += ') ';
    if (p1) out += p1;
    if (p2) out += '-' + p2;
    return out;
  }

  const cpfEl = document.getElementById('cpf');
  const telEl = document.getElementById('telefone');

  if (cpfEl) {
    cpfEl.addEventListener('input', e => {
      e.target.value = maskCPF(e.target.value);
      e.target.setSelectionRange(e.target.value.length, e.target.value.length);
    });
  }
  if (telEl) {
    telEl.addEventListener('input', e => {
      e.target.value = maskPhone(e.target.value);
      e.target.setSelectionRange(e.target.value.length, e.target.value.length);
    });
  }
</script>
@endsection
