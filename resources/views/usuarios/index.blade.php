@extends('layouts.app')

@section('content')
<div class="mb-4">
    <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
        <div>
            <p class="text-uppercase text-muted small mb-1">Administração</p>
            <h1 class="h4 fw-bold text-engaja mb-0">Gerenciar Usuários</h1>
        </div>
        @hasanyrole('administrador|gerente|articulador')
            <a href="{{ route('usuarios.create') }}" class="btn btn-engaja">Cadastrar Usuário</a>
        @endhasanyrole
    </div>

    <div class="filter-bar shadow-sm">
        <form action="{{ route('usuarios.index') }}" method="GET" class="row g-2 align-items-center">

            {{-- campo de busca --}}
            <div class="col-12 col-md-3">
                <div class="input-group">
              <span class="input-group-text bg-white border-end-0 text-muted">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                  </svg>
              </span>
                    <input type="text" name="q" class="form-control border-start-0 ps-0" placeholder="Buscar nome ou e-mail..." value="{{ $search }}">
                </div>
            </div>

            {{-- select de região --}}
            <div class="col-12 col-md-2">
                <select name="regiao" id="filtro_regiao" class="form-select text-muted">
                    <option value="">Todas as Regiões</option>
                    @foreach($regioes as $regiao)
                        <option value="{{ $regiao->id }}" {{ $regiao_id == $regiao->id ? 'selected' : '' }}>
                            {{ $regiao->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- select de estado --}}
            <div class="col-12 col-md-2">
                <select name="estado" id="filtro_estado" class="form-select text-muted" {{ empty($regiao_id) && empty($estado_id) ? 'disabled' : '' }}>
                    <option value="">Todos os Estados</option>
                    @foreach($estados as $estado)
                        <option value="{{ $estado->id }}" data-regiao="{{ $estado->regiao_id }}" {{ $estado_id == $estado->id ? 'selected' : '' }}>
                            {{ $estado->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- select de município --}}
            <div class="col-12 col-md-3">
                <select name="municipio" id="filtro_municipio" class="form-select text-muted" {{ empty($estado_id) && empty($municipio_id) ? 'disabled' : '' }}>
                    <option value="">Todos os Municípios</option>
                    @foreach($municipios as $municipio)
                        <option value="{{ $municipio->id }}" data-estado="{{ $municipio->estado_id }}" {{ $municipio_id == $municipio->id ? 'selected' : '' }}>
                            {{ $municipio->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-engaja w-100">Filtrar</button>
                @if($search || $regiao_id || $estado_id || $municipio_id)
                    <a href="{{ route('usuarios.index') }}" class="btn btn-light border w-100">Limpar</a>
                @endif
            </div>
        </form>
    </div>
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
                <th class="text-end pe-4">Ação</th>
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
    @can('user.ver')
      <a href="{{ route('usuarios.export') }}" class="btn btn-engaja">
        Exportar planilha de usuários
      </a>
    @endcan
    @hasanyrole('administrador|gerente')
      <button type="button" class="btn btn-outline-secondary" id="btn-select-all-page">Selecionar todos da página</button>
      <button type="button" class="btn btn-outline-secondary" id="btn-select-all-global">Selecionar todos (todas as páginas)</button>
      <button type="button" class="btn btn-engaja" id="btn-open-modal">Emitir certificados</button>
    @endhasanyrole
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


  //função que uso para gerenciar as seleções e hierarquia do filtro de regiao/estado/municipio
  const regiaoSelect = document.getElementById('filtro_regiao');
  const estadoSelect = document.getElementById('filtro_estado');
  const municipioSelect = document.getElementById('filtro_municipio');

  function filterOptions(parentSelect, childSelect, dataAttribute) {
      const parentId = parentSelect.value;
      Array.from(childSelect.options).forEach(option => {
          if (option.value === "") return;

          if (option.getAttribute(dataAttribute) === parentId) {
              option.style.display = '';
          } else {
              option.style.display = 'none';
          }
      });
  }

  if (regiaoSelect && estadoSelect && municipioSelect) {

      regiaoSelect.addEventListener('change', function() {
          estadoSelect.value = '';
          municipioSelect.value = '';

          municipioSelect.disabled = true;

          if (this.value) {
              estadoSelect.disabled = false;
              filterOptions(this, estadoSelect, 'data-regiao');
          } else {
              estadoSelect.disabled = true;
          }
      });

      estadoSelect.addEventListener('change', function() {
          municipioSelect.value = '';

          if (this.value) {
              municipioSelect.disabled = false;
              filterOptions(this, municipioSelect, 'data-estado');
          } else {
              municipioSelect.disabled = true;
          }
      });

      if (regiaoSelect.value) {
          estadoSelect.disabled = false;
          filterOptions(regiaoSelect, estadoSelect, 'data-regiao');
      } else {
          estadoSelect.disabled = true;
      }

      if (estadoSelect.value) {
          municipioSelect.disabled = false;
          filterOptions(estadoSelect, municipioSelect, 'data-estado');
      } else {
          municipioSelect.disabled = true;
      }
  }
</script>
@endpush
