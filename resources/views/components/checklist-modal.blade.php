@props([
    'id'       => 'checklistModal',
    'title'    => 'Checklist',
    'items'    => [],
    'btnLabel' => 'Prosseguir',
])

<style>
.checklist-card {
    cursor: pointer;
    border: 2px solid #dee2e6 !important;
    border-radius: 10px;
    padding: 14px 18px;
    transition: all 0.18s ease;
    user-select: none;
    position: relative;
}
.checklist-card:hover {
    border-color: #421944 !important;
    background-color: #f9f4fa !important;
}
.checklist-card.checked {
    border-color: #421944 !important;
    background-color: #421944 !important;
    color: #fff !important;
}
.checklist-card.checked .checklist-check-icon {
    opacity: 1;
    transform: scale(1);
}
.checklist-check-icon {
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.15s ease;
    flex-shrink: 0;
    width: 22px;
    height: 22px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #421944;
    font-size: 13px;
    font-weight: 900;
}
/* input escondido */
.checklist-card input[type="checkbox"] {
    display: none;
}
</style>

<div class="modal fade"
     id="{{ $id }}"
     tabindex="-1"
     aria-labelledby="{{ $id }}Label"
     aria-modal="true"
     role="dialog"
     data-bs-backdrop="static"
     data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header bg-engaja text-white border-0">
                <h5 class="modal-title fw-bold" id="{{ $id }}Label">
                    ✅ {{ $title }}
                </h5>
            </div>

            <div class="modal-body pt-2">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <p class="text-muted small mb-0">
                        Selecione todos os itens para prosseguir.
                    </p>
                    <span class="fw-semibold small js-counter text-engaja" data-modal="{{ $id }}">
                        0 / {{ count($items) }}
                    </span>
                </div>

                {{-- Barra de progresso --}}
                <div class="progress mb-4" style="height: 5px; border-radius:99px;">
                    <div class="progress-bar js-progress"
                         data-modal="{{ $id }}"
                         role="progressbar"
                         style="width:0%; background-color:#421944;"
                         aria-valuenow="0"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>

                <div class="vstack gap-2">
                    @foreach($items as $index => $item)
                    <label class="checklist-card d-flex align-items-center gap-3"
                           data-modal="{{ $id }}">
                        <input class="js-checklist-item"
                               type="checkbox"
                               id="{{ $id }}_item_{{ $index }}"
                               data-modal="{{ $id }}"
                               data-total="{{ count($items) }}">
                        <span class="checklist-check-icon">✓</span>
                        <span class="checklist-card-text">{{ $item }}</span>
                    </label>
                    @endforeach
                </div>
            </div>

            <div class="modal-footer gap-2 border-0 pt-0">
                <button type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal">
                    Cancelar
                </button>
                <button type="button"
                        class="btn btn-engaja js-checklist-confirm"
                        data-modal="{{ $id }}"
                        disabled>
                    {{ $btnLabel }}
                </button>
            </div>

        </div>
    </div>
</div>