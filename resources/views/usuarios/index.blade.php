@extends('layouts.app')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between mb-4">
  <div>
    <p class="text-uppercase text-muted small mb-1">Administracao</p>
    <h1 class="h4 fw-bold text-engaja mb-0">Gerenciar usuarios</h1>
    {{-- <div class="text-muted small">Disponivel apenas para administradores e gestores.</div> --}}
  </div>
  <form method="GET" action="{{ route('usuarios.index') }}" class="d-flex gap-2">
    <input type="text" name="q" value="{{ $search ?? '' }}" class="form-control" placeholder="Buscar por nome ou e-mail" aria-label="Buscar usuarios">
    <button class="btn btn-engaja" type="submit">Buscar</button>
  </form>
</div>

@if ($users->isEmpty())
  <div class="alert alert-info">
    @if (!empty($search))
      Nenhum usuario encontrado para "{{ $search }}".
    @else
      Nao ha usuarios editaveis no momento.
    @endif
  </div>
@else
  <form method="POST" action="{{ route('usuarios.certificados.emitir') }}" id="form-emitir-certificados">
    @csrf
    <input type="hidden" name="modelo_id" id="modelo_id_hidden">
    <div class="card shadow-sm">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th class="ps-4" style="width: 40px;">
                  <input type="checkbox" id="check-all">
                </th>
                <th>Nome</th>
                <th>E-mail</th>
                <th>CPF</th>
                <th>Telefone</th>
                <th class="text-end pe-4">Acao</th>
              </tr>
            </thead>
            <tbody>
              @foreach ($users as $u)
                @php
                  $cpfRaw = $u->participante->cpf ?? null;
                  $cpfFmt = $cpfRaw ? preg_replace('/(\\d{3})(\\d{3})(\\d{3})(\\d{2})/', '$1.$2.$3-$4', $cpfRaw) : '--';
                  $telRaw = $u->participante->telefone ?? null;
                  $telFmt = $telRaw
                    ? preg_replace('/(\\d{2})(\\d{4,5})(\\d{4})/', '($1) $2-$3', $telRaw)
                    : '--';
                @endphp
                <tr>
                  <td class="ps-4">
                    <input type="checkbox" name="participantes[]" value="{{ $u->participante->id ?? '' }}" @disabled(!$u->participante)>
                  </td>
                  <td>
                    <div class="fw-semibold">{{ $u->name }}</div>
                  </td>
                  <td>{{ $u->email }}</td>
                  <td>{{ $cpfFmt }}</td>
                  <td>{{ $telFmt }}</td>
                  <td class="text-end pe-4">
                    <a href="{{ route('usuarios.edit', $u) }}" class="btn btn-sm btn-engaja">
                      Editar
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <div class="text-muted small">Selecione participantes e clique em Emitir certificados.</div>
        {{ $users->links() }}
      </div>
    </div>

    <div class="mt-3 text-end d-flex flex-wrap justify-content-end gap-2">
      <button type="button" class="btn btn-outline-secondary" id="btn-select-all-page">Selecionar todos da página</button>
      <button type="button" class="btn btn-outline-secondary" id="btn-select-all-global">Selecionar todos (todas as páginas)</button>
      <button type="button" class="btn btn-engaja" id="btn-open-modal">Emitir certificados</button>
    </div>
  </form>

  <input type="hidden" name="select_all_pages" id="select_all_pages_hidden" form="form-emitir-certificados" value="">

  {{-- Modal seleção de modelo --}}
  <div class="modal fade" id="modalModeloCertificado" tabindex="-1" aria-labelledby="modalModeloCertificadoLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalModeloCertificadoLabel">Emitir certificados</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="select-modelo" class="form-label">Modelo de certificado</label>
            <select id="select-modelo" class="form-select">
              <option value="" selected disabled>Selecione um modelo</option>
              @foreach ($modelosCertificado as $modelo)
                <option value="{{ $modelo->id }}">{{ $modelo->nome }}</option>
              @endforeach
            </select>
            <small class="text-muted d-block mt-2">Um certificado será gerado por evento, somando as horas das presenças pendentes do participante.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-engaja" id="btn-confirmar-emissao">Confirmar emissão</button>
        </div>
      </div>
    </div>
  </div>
@endif
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const checkAll = document.getElementById('check-all');
    const btnOpen = document.getElementById('btn-open-modal');
    const btnConfirm = document.getElementById('btn-confirmar-emissao');
    const selectModelo = document.getElementById('select-modelo');
    const modeloHidden = document.getElementById('modelo_id_hidden');
    const form = document.getElementById('form-emitir-certificados');
    const btnSelectAllPage = document.getElementById('btn-select-all-page');
    const btnSelectAllGlobal = document.getElementById('btn-select-all-global');
    const selectAllPagesHidden = document.getElementById('select_all_pages_hidden');

    if (checkAll) {
      checkAll.addEventListener('change', () => {
        document.querySelectorAll('input[name="participantes[]"]:not(:disabled)').forEach(cb => {
          cb.checked = checkAll.checked;
        });
      });
    }

    if (btnOpen) {
      btnOpen.addEventListener('click', () => {
        const selecionados = Array.from(document.querySelectorAll('input[name="participantes[]"]:checked'));
        if (selecionados.length === 0) {
          alert('Selecione ao menos um participante.');
          return;
        }
        const modal = new bootstrap.Modal(document.getElementById('modalModeloCertificado'));
        modal.show();
      });
    }

    if (btnSelectAllPage) {
      btnSelectAllPage.addEventListener('click', () => {
        document.querySelectorAll('input[name="participantes[]"]:not(:disabled)').forEach(cb => {
          cb.checked = true;
        });
        if (checkAll) checkAll.checked = true;
        if (selectAllPagesHidden) selectAllPagesHidden.value = '';
      });
    }

    if (btnSelectAllGlobal) {
      btnSelectAllGlobal.addEventListener('click', () => {
        document.querySelectorAll('input[name="participantes[]"]:not(:disabled)').forEach(cb => {
          cb.checked = true;
        });
        if (checkAll) checkAll.checked = true;
        if (selectAllPagesHidden) selectAllPagesHidden.value = '1';
      });
    }

    if (btnConfirm && selectModelo && modeloHidden && form) {
      btnConfirm.addEventListener('click', () => {
        const modeloId = selectModelo.value;
        if (!modeloId) {
          alert('Selecione um modelo de certificado.');
          return;
        }
        modeloHidden.value = modeloId;
        form.submit();
      });
    }
  });
</script>
@endpush
