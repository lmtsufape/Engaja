@props(['name', 'value' => '', 'placeholder' => ''])

@php
    $editorId = 'quill_'.uniqid();
@endphp

<div class="quill-wrapper mb-3" wire:ignore>
    <div id="{{ $editorId }}" class="bg-white"></div>
    <input type="hidden" name="{{ $name }}" id="{{ $editorId }}_input" value="{{ old($name, $value) }}">
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const editorDiv = document.getElementById('{{ $editorId }}');
        const hiddenInput = document.getElementById('{{ $editorId }}_input');
        
        if (editorDiv && typeof Quill !== 'undefined') {
            const quill = new Quill(editorDiv, {
                theme: 'snow',
                placeholder: '{{ $placeholder }}',
                modules: {
                    toolbar: [
                        [{ 'header': [1, 2, 3, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ 'color': [] }, { 'background': [] }],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        ['clean']
                    ]
                }
            });
            
            // Set initial value
            if (hiddenInput.value) {
                quill.clipboard.dangerouslyPasteHTML(hiddenInput.value);
            }

            // Sync on change
            quill.on('text-change', function() {
                hiddenInput.value = quill.root.innerHTML;
            });
        }
    });
</script>
@endpush

<style>
    .quill-wrapper .ql-toolbar {
        border-top-left-radius: 0.375rem;
        border-top-right-radius: 0.375rem;
        background-color: #f8f9fa;
        border-color: #dee2e6;
    }
    .quill-wrapper .ql-container {
        border-bottom-left-radius: 0.375rem;
        border-bottom-right-radius: 0.375rem;
        border-color: #dee2e6;
        min-height: 250px;
        font-size: 1rem;
    }
    .quill-wrapper .ql-editor {
        min-height: 250px;
    }
</style>
