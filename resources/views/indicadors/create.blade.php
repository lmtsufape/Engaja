@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-6">
    <h1 class="h3 fw-bold text-engaja mb-4">Novo indicador</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        <form method="POST" action="{{ route('indicadors.store') }}">
          @csrf

          <div class="mb-3">
            <label for="dimensao_id" class="form-label">Dimensão</label>
            <select id="dimensao_id" name="dimensao_id"
              class="form-select @error('dimensao_id') is-invalid @enderror" required>
              <option value="">Selecione...</option>
              @foreach ($dimensoes as $id => $descricao)
              <option value="{{ $id }}" @selected(old('dimensao_id') == $id)>{{ $descricao }}</option>
              @endforeach
            </select>
            @error('dimensao_id')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label for="descricao" class="form-label">Descrição</label>
            <input type="text" id="descricao" name="descricao"
              class="form-control @error('descricao') is-invalid @enderror"
              value="{{ old('descricao') }}" required>
            @error('descricao')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="d-flex justify-content-between">
            <a href="{{ route('indicadors.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-engaja">Salvar</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
