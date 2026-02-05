@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
      <p class="text-uppercase text-muted small mb-1">Administração</p>
      <h1 class="h4 fw-bold mb-0">Estados</h1>
      <p class="text-muted mb-0">Gerencie os estados cadastrados.</p>
    </div>
    <button class="btn btn-engaja btn-sm" data-bs-toggle="modal" data-bs-target="#modalCreateEstado">Novo estado</button>
  </div>

  <div class="table-responsive shadow-sm rounded-3 bg-white">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Região</th>
          <th>Sigla</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($estados as $estado)
          <tr>
            <td>{{ $estado->nome }}</td>
            <td>{{ $estado->regiao->nome ?? '-' }}</td>
            <td>{{ $estado->sigla }}</td>
            <td class="text-end">
              <button
                class="btn btn-outline-secondary btn-sm btn-edit-estado"
                data-id="{{ $estado->id }}"
                data-nome="{{ $estado->nome }}"
                data-sigla="{{ $estado->sigla }}"
                data-regiao="{{ $estado->regiao_id }}"
                data-action="{{ route('estados.update', $estado) }}"
                data-delete="{{ route('estados.destroy', $estado) }}"
                data-bs-toggle="modal"
                data-bs-target="#modalEditEstado"
              >Editar</button>
            </td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center text-muted py-4">Nenhum estado cadastrado.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<!-- Modal Criar Estado -->
<div class="modal fade" id="modalCreateEstado" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="{{ route('estados.store') }}">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title">Novo estado</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Região</label>
            <select name="regiao_id" class="form-select" required>
              <option value="">Selecione</option>
              @foreach($regioes as $r)
                <option value="{{ $r->id }}">{{ $r->nome }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Sigla</label>
            <input type="text" name="sigla" class="form-control" maxlength="5" required>
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

<!-- Modal Editar Estado -->
<div class="modal fade" id="modalEditEstado" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" id="formEditEstado">
        @csrf
        @method('PUT')
        <div class="modal-header">
          <h5 class="modal-title">Editar estado</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Região</label>
            <select name="regiao_id" id="editEstadoRegiao" class="form-select" required>
              @foreach($regioes as $r)
                <option value="{{ $r->id }}">{{ $r->nome }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" id="editEstadoNome" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Sigla</label>
            <input type="text" name="sigla" id="editEstadoSigla" class="form-control" maxlength="5" required>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-outline-danger" id="btnDeleteEstado">Excluir</button>
          <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
            <button class="btn btn-engaja">Salvar</button>
          </div>
        </div>
      </form>
      <form method="POST" id="formDeleteEstado" class="d-none">
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
  const editBtns = document.querySelectorAll('.btn-edit-estado');
  const formEdit = document.getElementById('formEditEstado');
  const formDelete = document.getElementById('formDeleteEstado');
  const inputNome = document.getElementById('editEstadoNome');
  const inputSigla = document.getElementById('editEstadoSigla');
  const selectRegiao = document.getElementById('editEstadoRegiao');
  editBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      formEdit.setAttribute('action', btn.dataset.action);
      formDelete.setAttribute('action', btn.dataset.delete);
      inputNome.value = btn.dataset.nome || '';
      inputSigla.value = btn.dataset.sigla || '';
      if (selectRegiao) selectRegiao.value = btn.dataset.regiao || '';
    });
  });
  const btnDelete = document.getElementById('btnDeleteEstado');
  if (btnDelete && formDelete) {
    btnDelete.addEventListener('click', () => {
      if (confirm('Confirma remover este estado?')) {
        formDelete.submit();
      }
    });
  }
});
</script>
@endpush
@endsection
