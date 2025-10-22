@push('scripts')
  @once
    <script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
  @endonce
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      if (typeof ClassicEditor === 'undefined') {
        console.error('ClassicEditor nao carregado');
        return;
      }

      document.querySelectorAll('[data-wysiwyg]').forEach(function (element) {
        if (element.dataset.wysiwygInitialized === 'true') {
          return;
        }

        ClassicEditor.create(element, {
          toolbar: ['bold', 'italic', 'underline', 'link', 'bulletedList', 'numberedList', 'undo', 'redo']
        }).then(function () {
          element.dataset.wysiwygInitialized = 'true';
        }).catch(function (error) {
          console.error('Erro ao inicializar editor WYSIWYG', error);
        });
      });
    });
  </script>
@endpush
