<div>
    <div wire:loading class="text-center p-4">
        Carregando dados...
    </div>

    <div wire:loading.remove>
        @if (empty($dados))
            <div class="alert alert-warning text-center my-4">
                Nenhum dado disponível para este gráfico de dimensão.
            </div>
        @else
            <div class="card-grafico-bi card-grafico-bi-dimensao"
                data-chart="distribuicao-dimensao"
                data-dados='@json($dados, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)'
                data-titulo="{{ e($titulo) }}"
                data-tipo-valor="{{ e($tipoValor) }}"
                data-tipo-grafico="{{ e($tipoGrafico) }}"
                data-mostrar-valores="{{ $mostrarValores ? '1' : '0' }}"
                data-usar-percentual="{{ $usarPercentual ? '1' : '0' }}"
                data-casas-decimais-percentual="{{ (int) $casasDecimaisPercentual }}">
            </div>
        @endif
    </div>
</div>
