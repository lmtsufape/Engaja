@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <h1 class="h3 fw-bold text-engaja mb-0">Ações pedagógicas</h1>

            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-outline-primary" id="btn-emitir-certificados" data-bs-toggle="modal" data-bs-target="#modalEmitirCertificados" disabled>Emitir certificados</button>
                @hasanyrole('administrador|formador')
                <a href="{{ route('eventos.create') }}" class="btn btn-engaja">Nova ação pedagógica</a>
                @endhasanyrole
            </div>
        </div>
        {{-- Filtros / busca --}}
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                    placeholder="Buscar por nome, tipo, objetivo">
            </div>
            <div class="col-md-3">
                <select name="eixo" class="form-select">
                    <option value="">Todos os eixos</option>
                    @foreach($eixos as $eixo)
                        <option value="{{ $eixo->id }}" @selected(request('eixo') == $eixo->id)>{{ $eixo->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="de" value="{{ request('de') }}" class="form-control" placeholder="de">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-secondary">Filtrar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width: 36px;"><input type="checkbox" id="check-all"></th>
                        <th>Nome</th>
                        <th>Eixo</th>
                        <th>Tipo</th>
                        <th>Período</th>
                        <th>Criado por</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($eventos as $ev)
                        <tr>
                            <td><input type="checkbox" class="form-check-input evento-check" value="{{ $ev->id }}"></td>
                            <td class="fw-semibold">{{ $ev->nome }}</td>
                            <td>{{ $ev->eixo->nome ?? '-' }}</td>
                            <td>{{ $ev->tipo ?? '-' }}</td>
                            <td>
                                @php
                                    $inicio = $ev->data_inicio ? \Carbon\Carbon::parse($ev->data_inicio)->format('d/m/Y') : null;
                                    $fim = $ev->data_fim ? \Carbon\Carbon::parse($ev->data_fim)->format('d/m/Y') : null;
                                    $mostrarFim = $fim && (!$inicio || $fim !== $inicio);
                                @endphp
                                @if($inicio || $fim)
                                    {{ $inicio ?? '-' }} @if($mostrarFim)<br><small class="text-muted">até {{ $fim }}</small>@endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $ev->user->name ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('eventos.show', $ev) }}" class="btn btn-sm btn-outline-primary">
                                    Ver
                                </a>
                                @can('update', $ev)
                                <a href="{{ route('eventos.edit', $ev) }}" class="btn btn-sm btn-outline-secondary">Editar</a>

                                <form action="{{ route('eventos.destroy', $ev) }}" method="POST" class="d-inline" data-confirm="Tem certeza que deseja excluir esta ação pedagógica?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhuma ação pedagógica encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $eventos->withQueryString()->links() }}
    </div>

    <form method="POST" action="{{ route('certificados.emitir') }}">
        @csrf
        <input type="hidden" name="eventos" id="eventosSelecionados">
        <div class="modal fade" id="modalEmitirCertificados" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Emitir certificados</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2">Selecione o modelo que será usado para emitir os certificados das ações selecionadas.</p>
                        <div class="mb-3">
                            <label class="form-label" for="modelo_id">Modelo de certificado</label>
                            <select name="modelo_id" id="modelo_id" class="form-select" required>
                                <option value="">-- Escolha um modelo --</option>
                                @foreach($modelosCertificados as $modelo)
                                    <option value="{{ $modelo->id }}">{{ $modelo->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="alert alert-info small mb-0">
                            Serão substituídas as tags: <code>%participante%</code> (nome do participante) e <code>%acao%</code> (nome da ação pedagógica).
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-outline-primary" id="btn-preview-certificado" disabled>Pré-visualizar</button>
                        <button type="submit" class="btn btn-engaja" id="btn-confirmar-emissao" disabled>Emitir agora</button>
                    </div>
                </div>
            </div>
        </div>
    </form>

@push('scripts')
<script>
  (function() {
    const selectModelo = document.getElementById('modelo_id');
    const hiddenEventos = document.getElementById('eventosSelecionados');
    const btnPreview = document.getElementById('btn-preview-certificado');
    const btnEmitir = document.getElementById('btn-confirmar-emissao');

    const toggleButtons = () => {
      const hasModelo = selectModelo && selectModelo.value;
      const hasEventos = hiddenEventos && hiddenEventos.value;
      const enable = Boolean(hasModelo && hasEventos);
      if (btnPreview) btnPreview.disabled = !enable;
      if (btnEmitir) btnEmitir.disabled = !enable;
    };

    if (selectModelo) {
      selectModelo.addEventListener('change', toggleButtons);
    }

    // Se o JS que popula eventosSelecionados já existir, este listener garante atualização
    if (hiddenEventos) {
      hiddenEventos.addEventListener('change', toggleButtons);
    }

    if (btnPreview) {
      btnPreview.addEventListener('click', () => {
        if (!selectModelo.value || !hiddenEventos.value) return;
        const params = new URLSearchParams({
          modelo_id: selectModelo.value,
          eventos: hiddenEventos.value,
        });
        window.open(`{{ route('certificados.preview') }}?${params.toString()}`, '_blank');
      });
    }

    // Chamada inicial
    toggleButtons();
  })();
</script>
@endpush
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const checkAll = document.getElementById('check-all');
    const checks = Array.from(document.querySelectorAll('.evento-check'));
    const btnEmitir = document.getElementById('btn-emitir-certificados');
    const modalEl = document.getElementById('modalEmitirCertificados');
    let modalInstance = null;
    const inputEventos = document.getElementById('eventosSelecionados');
    const btnConfirmar = document.getElementById('btn-confirmar-emissao');

    const syncButtons = () => {
        const selecionados = checks.filter(c => c.checked).map(c => c.value);
        const hasSel = selecionados.length > 0;
        if (btnEmitir) btnEmitir.disabled = !hasSel;
        if (btnConfirmar) btnConfirmar.disabled = !hasSel;
        if (inputEventos) inputEventos.value = selecionados.join(',');
    };

    if (checkAll) {
        checkAll.addEventListener('change', () => {
            checks.forEach(c => { c.checked = checkAll.checked; });
            syncButtons();
        });
    }

    checks.forEach(c => c.addEventListener('change', syncButtons));

    if (modalEl && window.bootstrap?.Modal) {
        modalInstance = window.bootstrap.Modal.getOrCreateInstance(modalEl);
        modalEl.addEventListener('show.bs.modal', syncButtons);
    }

    if (btnEmitir && modalEl) {
        btnEmitir.addEventListener('click', (e) => {
            if (btnEmitir.disabled) {
                e.preventDefault();
                return;
            }
            // abre manualmente caso Bootstrap JS não esteja presente
            if (modalInstance && modalInstance.show) {
                modalInstance.show();
            } else {
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                modalEl.removeAttribute('aria-hidden');
            }
        });
    }

    syncButtons();
});
</script>
@endpush
