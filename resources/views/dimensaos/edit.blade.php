@extends('layouts.app')

@section('content')
<div class="row justify-content-center">
  <div class="col-lg-6">
    <h1 class="h3 fw-bold text-engaja mb-4">Editar dimensão</h1>

    <div class="card shadow-sm">
      <div class="card-body">
        <form method="POST" action="{{ route('dimensaos.update', $dimensao) }}">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label for="descricao" class="form-label">Descrição</label>
            <input type="text" id="descricao" name="descricao"
              class="form-control @error('descricao') is-invalid @enderror"
              value="{{ old('descricao', $dimensao->descricao) }}" required>
            @error('descricao')
            <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <div class="d-flex justify-content-between">
            <a href="{{ route('dimensaos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-engaja">Salvar alterações</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
