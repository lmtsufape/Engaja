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

  @php
      $columns = [
          ['field' => 'nome', 'headerName' => 'Nome', 'flex' => 2],
          ['field' => 'acoes', 'headerName' => 'Ações', 'flex' => 1, 'html' => true, 'align' => 'end'],
      ];

      $rows = $regioes->map(fn ($regiao) => [
          'nome' => $regiao->nome,
          'acoes' => '<button class="btn btn-outline-secondary btn-sm btn-edit-regiao" data-id="' . $regiao->id . '" data-nome="' . e($regiao->nome) . '" data-action="' . route('regioes.update', $regiao) . '" data-delete="' . route('regioes.destroy', $regiao) . '" data-bs-toggle="modal" data-bs-target="#modalEditRegiao">Editar</button>',
      ])->values();
  @endphp

  <div class="card shadow-sm">
      <x-data-table id="grid-regioes" :columns="$columns" :rows="$rows" :pagination="false" />
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
  const formEdit = document.getElementById('formEditRegiao');
  const formDelete = document.getElementById('formDeleteRegiao');
  const inputNome = document.getElementById('editRegiaoNome');

  // Delegação de evento: os botões .btn-edit-regiao são renderizados pelo
  // AG Grid de forma assíncrona (depois do DOMContentLoaded).
  document.addEventListener('click', (event) => {
    const btn = event.target.closest('.btn-edit-regiao');
    if (!btn) return;

    formEdit.setAttribute('action', btn.dataset.action);
    formDelete.setAttribute('action', btn.dataset.delete);
    inputNome.value = btn.dataset.nome || '';
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
