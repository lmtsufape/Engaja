@php
    $municipioSelecionado = (string) old('municipio_id', '');
    $municipioAtual = $municipioSelecionado !== ''
        ? $municipios->firstWhere('id', (int) $municipioSelecionado)
        : null;
    $estadoSelecionado = (string) old('estado_id', $municipioAtual?->estado_id ?? '');
    $estados = $municipios
        ->pluck('estado')
        ->filter()
        ->unique('id')
        ->sortBy('nome', SORT_NATURAL | SORT_FLAG_CASE)
        ->values();
    $municipiosCadastroJson = $municipios->map(fn ($municipio) => [
        'id' => (string) $municipio->id,
        'nome' => $municipio->nome,
        'estado_id' => (string) $municipio->estado_id,
        'uf' => $municipio->estado?->sigla ?? '',
    ])->values();
@endphp

<div class="col-md-6">
    <label for="estado_id" class="form-label">Estado</label>
    <select id="estado_id" name="estado_id" class="form-select">
        <option value="">— Selecione o estado —</option>
        @foreach($estados as $estado)
            <option value="{{ $estado->id }}" @selected($estadoSelecionado === (string) $estado->id)>
                {{ $estado->nome }} — {{ $estado->sigla }}
            </option>
        @endforeach
    </select>
</div>

<div class="col-md-6">
    <label for="municipio_id" class="form-label">Município</label>
    <select
        id="municipio_id"
        name="municipio_id"
        class="form-select @error('municipio_id') is-invalid @enderror"
        data-selected="{{ $municipioSelecionado }}"
        @disabled($estadoSelecionado === '')>
        <option value="">— Selecione primeiro o estado —</option>
    </select>
    @error('municipio_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<script type="application/json" id="cadastro-municipios-json">{!! json_encode($municipiosCadastroJson, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
<script>
    (() => {
        const estadoSelect = document.getElementById('estado_id');
        const municipioSelect = document.getElementById('municipio_id');
        const dataElement = document.getElementById('cadastro-municipios-json');

        if (!estadoSelect || !municipioSelect || !dataElement) return;

        let municipios = [];
        try {
            municipios = JSON.parse(dataElement.textContent || '[]');
        } catch (error) {
            municipios = [];
        }

        const selectedMunicipio = municipioSelect.dataset.selected || '';

        function preencherMunicipios(preservarSelecionado = false) {
            const estadoId = String(estadoSelect.value || '');
            const valorSelecionado = preservarSelecionado ? selectedMunicipio : '';

            municipioSelect.innerHTML = '';
            municipioSelect.appendChild(new Option(
                estadoId ? '— Selecione o município —' : '— Selecione primeiro o estado —',
                ''
            ));

            municipios
                .filter((municipio) => municipio.estado_id === estadoId)
                .sort((a, b) => a.nome.localeCompare(b.nome, 'pt-BR'))
                .forEach((municipio) => {
                    const option = new Option(`${municipio.nome} — ${municipio.uf}`, municipio.id);
                    option.selected = municipio.id === valorSelecionado;
                    municipioSelect.appendChild(option);
                });

            municipioSelect.disabled = estadoId === '';
        }

        estadoSelect.addEventListener('change', () => preencherMunicipios(false));
        preencherMunicipios(true);
    })();
</script>
