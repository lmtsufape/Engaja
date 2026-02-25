@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h4 fw-bold text-engaja mb-0">Novo momento — {{ $evento->nome }}</h1>
    <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">Voltar à ação pedagógica</a>
  </div>

  @if ($errors->any())
    <div class="alert alert-danger"><strong>Ops!</strong> Verifique os campos abaixo.</div>
  @endif

  <div class="card shadow-sm">
    <div class="card-body">
      <form method="POST" action="{{ route('eventos.atividades.store', $evento) }}" id="form-novo-momento">
        {{-- campos hidden para enviar checklist junto com o form --}}
        <div id="hidden-checklist-encerramento"></div>
        @include('atividades._form', [
          'evento' => $evento,
          'municipios' => $municipios,
          'atividadesCopiaveis' => $atividadesCopiaveis,
          'submitLabel' => 'Salvar momento'
        ])
      </form>
    </div>
  </div>
</div>

{{-- Modal Checklist de Encerramento --}}
<x-checklist-modal
    id="modalChecklistPosAcao"
    title="Checklist de Encerramento"
    btn-label="Confirmar e Salvar"
    tipo="encerramento"
    :marcados="[]"
    :items="[
        'Verifiquei se os municípios estão corretos?',
        'Confirmei a carga horária e os horários de início e término?',
        'O público esperado e os dados do momento estão preenchidos corretamente?'
    ]"
/>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-novo-momento');

    if (form) {
        form.addEventListener('submit', function (e) {
            if (!this.dataset.checklistConfirmed) {
                e.preventDefault();
                const modal = new bootstrap.Modal(document.getElementById('modalChecklistPosAcao'));
                modal.show();
            }
        });
    }

    const btnConfirmarPos = document.querySelector('.js-checklist-confirm[data-modal="modalChecklistPosAcao"]');
    if (btnConfirmarPos) {
        btnConfirmarPos.addEventListener('click', function () {
            const container = document.getElementById('hidden-checklist-encerramento');
            container.innerHTML = '';
            document.querySelectorAll('#modalChecklistPosAcao .js-checklist-item:checked').forEach(cb => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'checklist_encerramento[]';
                input.value = cb.dataset.index;
                container.appendChild(input);
            });

            const modalEl = document.getElementById('modalChecklistPosAcao');
            bootstrap.Modal.getInstance(modalEl)?.hide();
            form.dataset.checklistConfirmed = 'true';
            form.submit();
        });
    }
});
</script>
@endpush