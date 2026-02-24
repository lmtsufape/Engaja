<div>
    <div wire:loading class="text-center p-4">
        Carregando dados...
    </div>
    <div wire:loading.remove>
        @if (empty($dados))
            <div class="alert alert-warning text-center my-4">Nenhum dado disponivel para este grafico.</div>
        @else
            <div class="card-grafico-bi" data-chart="ranking-municipios"
                data-dados='@json($dados, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)'
                data-titulo="{{ e($titulo) }}" data-tipo-valor="{{ e($tipoValor) }}"
                data-label-indicador-percentual="{{ e($labelIndicadorPercentual) }}"
                data-label-indicador-absoluto="{{ e($labelIndicadorAbsoluto) }}"
                data-cor="{{ e($cor) }}">
            </div>

            @if(!empty($indicadorPercentual) && !empty($indicadorAbsoluto))
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <span class="badge text-bg-light border">
                        {{ $labelIndicadorAbsoluto }} (total): <strong>{{ number_format((float) ($resumo['total_absoluto'] ?? 0), 0, ',', '.') }}</strong>
                    </span>
                    <span class="badge text-bg-light border">
                        {{ $labelIndicadorPercentual }} (media): <strong>{{ number_format((float) ($resumo['media_percentual'] ?? 0), 2, ',', '.') }}%</strong>
                    </span>
                    <span class="badge text-bg-light border">
                        Municipios com dado: <strong>{{ (int) ($resumo['municipios'] ?? 0) }}</strong>
                    </span>
                </div>
            @endif
        @endif
    </div>
</div>
