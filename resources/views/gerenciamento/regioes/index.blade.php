@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <p class="text-uppercase text-muted small mb-1">Administração</p>
      <h1 class="h4 fw-bold mb-0">Regiões</h1>
      <p class="text-muted mb-0">Gerencie as regiões cadastradas.</p>
    </div>
    <button class="btn btn-engaja" data-bs-toggle="modal" data-bs-target="#modalCreateRegiao">Nova região</button>
  </div>

  <div class="table-responsive shadow-sm rounded-3 bg-white">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>Nome</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($regioes as $regiao)
          <tr>
            <td>{{ $regiao->nome }}</td>
            <td class="text-end">
              <button
                class="btn btn-outline-secondary btn-sm btn-edit-regiao"
                data-id="{{ $regiao->id }}"
                data-nome="{{ $regiao->nome }}"
                data-action="{{ route('regioes.update', $regiao) }}"
                data-delete="{{ route('regioes.destroy', $regiao) }}"
                data-bs-toggle="modal"
                data-bs-target="#modalEditRegiao"
              >Editar</button>
            </td>
          </tr>
        @empty
          <tr><td colspan="2" class="text-center text-muted py-4">Nenhuma região cadastrada.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Criar -->
<div class="modal fade" id="modalCreateRegiao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('regioes.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Nova região</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
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

<!-- Modal Editar -->
<div class="modal fade" id="modalEditRegiao" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="formEditRegiao">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Editar região</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" id="editRegiaoNome" class="form-control" required>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-outline-danger" id="btnDeleteRegiao">Excluir</button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-engaja">Salvar</button>
          </div>
        </div>
      </form>
      <form method="POST" id="formDeleteRegiao" class="d-none">
        @csrf
        @method('DELETE')
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
  const editButtons = document.querySelectorAll('.btn-edit-regiao');
  const formEdit = document.getElementById('formEditRegiao');
  const formDelete = document.getElementById('formDeleteRegiao');
  const inputNome = document.getElementById('editRegiaoNome');
  editButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      const action = btn.dataset.action;
      const del = btn.dataset.delete;
      const nome = btn.dataset.nome;
      formEdit.setAttribute('action', action);
      formDelete.setAttribute('action', del);
      inputNome.value = nome || '';
    });
  });
  const btnDelete = document.getElementById('btnDeleteRegiao');
  if (btnDelete && formDelete) {
    btnDelete.addEventListener('click', () => {
      if (confirm('Confirma remover esta região?')) {
        formDelete.submit();
      }
    });
  }
});
</script>
@endpush
@endsection
