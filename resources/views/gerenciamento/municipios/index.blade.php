@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <p class="text-uppercase text-muted small mb-1">Administração</p>
      <h1 class="h4 fw-bold mb-0">Municípios</h1>
      <p class="text-muted mb-0">Gerencie os municípios cadastrados.</p>
    </div>
    <button class="btn btn-engaja btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateMunicipio">Novo município</button>
  </div>

  <div class="table-responsive shadow-sm rounded-3 bg-white">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Estado</th>
          <th>Região</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($municipios as $municipio)
          <tr>
            <td>{{ $municipio->nome }}</td>
            <td>{{ $municipio->estado?->nome }} ({{ $municipio->estado?->sigla }})</td>
            <td>{{ $municipio->estado?->regiao?->nome ?? '-' }}</td>
            <td class="text-end">
              <button
                class="btn btn-outline-secondary btn-sm btn-edit-municipio"
                data-id="{{ $municipio->id }}"
                data-nome="{{ $municipio->nome }}"
                data-estado="{{ $municipio->estado_id }}"
                data-action="{{ route('municipios.update', $municipio) }}"
                data-delete="{{ route('municipios.destroy', $municipio) }}"
                data-bs-toggle="modal"
                data-bs-target="#modalEditMunicipio"
              >Editar</button>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center text-muted py-4">Nenhum município cadastrado.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Criar Município -->
<div class="modal fade" id="modalCreateMunicipio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('municipios.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Novo município</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Estado</label>
            <select name="estado_id" class="form-select" required>
              <option value="">Selecione</option>
              @foreach($estados as $e)
                <option value="{{ $e->id }}">{{ $e->nome }} ({{ $e->sigla }}) - {{ $e->regiao->nome ?? '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button class="btn btn-engaja">Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Editar Município -->
<div class="modal fade" id="modalEditMunicipio" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="formEditMunicipio">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Editar município</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Estado</label>
            <select name="estado_id" id="editMunicipioEstado" class="form-select" required>
              @foreach($estados as $e)
                <option value="{{ $e->id }}">{{ $e->nome }} ({{ $e->sigla }}) - {{ $e->regiao->nome ?? '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" id="editMunicipioNome" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-outline-danger" id="btnDeleteMunicipio">Excluir</button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-engaja">Salvar</button>
          </div>
        </div>
      </form>
      <form method="POST" id="formDeleteMunicipio" class="d-none">
        @csrf @method('DELETE')
      </form>
    </div>
  </div>
</div>

@push('styles')
<style>
  .btn-engaja { background:#4a0e4e; color:#fff; border:1px solid #4a0e4e; }
  .btn-engaja:hover { background:#3c0b3f; color:#fff; border-color:#3c0b3f; }
</style>
@endpush
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const editBtns = document.querySelectorAll('.btn-edit-municipio');
  const formEdit = document.getElementById('formEditMunicipio');
  const formDelete = document.getElementById('formDeleteMunicipio');
  const inputNome = document.getElementById('editMunicipioNome');
  const selectEstado = document.getElementById('editMunicipioEstado');

  editBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      formEdit.setAttribute('action', btn.dataset.action);
      formDelete.setAttribute('action', btn.dataset.delete);
      inputNome.value = btn.dataset.nome || '';
      if (selectEstado) selectEstado.value = btn.dataset.estado || '';
    });
  });

  const btnDelete = document.getElementById('btnDeleteMunicipio');
  if (btnDelete && formDelete) {
    btnDelete.addEventListener('click', () => {
      if (confirm('Confirma remover este município?')) {
        formDelete.submit();
      }
    });
  }
});
</script>
@endpush
@endsection
