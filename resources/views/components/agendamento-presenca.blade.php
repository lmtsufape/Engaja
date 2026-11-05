@props(['id', 'ativo' => false])

<div data-agendamento-presenca>
    <input type="hidden" name="agendamento_presenca_ativo" value="0">
    <div class="form-check mb-2">
        <input type="checkbox" class="form-check-input" name="agendamento_presenca_ativo"
               id="{{ $id }}" value="1" @checked($ativo) data-agendamento-toggle>
        <label class="form-check-label fw-semibold" for="{{ $id }}">Ativar agendamento de presença</label>
    </div>
    <div class="form-text mb-3">
        Marque para definir a abertura e/ou o fechamento automático (horário de Brasília).
        Desmarque e salve para limpar as datas e manter o controle manual da presença.
    </div>
    <fieldset @disabled(! $ativo) data-agendamento-campos>
        {{ $slot }}
    </fieldset>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-agendamento-presenca]').forEach(function (container) {
                const toggle = container.querySelector('[data-agendamento-toggle]');
                const campos = container.querySelector('[data-agendamento-campos]');

                function atualizar() {
                    campos.disabled = !toggle.checked;
                    if (!toggle.checked) {
                        campos.querySelectorAll('input[type="datetime-local"]').forEach(input => { input.value = ''; });
                    }
                }

                toggle.addEventListener('change', atualizar);
                atualizar();
            });
        });
    </script>
@endonce
