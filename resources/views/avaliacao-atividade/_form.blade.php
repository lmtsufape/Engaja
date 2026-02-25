@csrf

{{-- Cabeçalho com dados do momento --}}
<div class="alert alert-light border mb-4">
    <div class="row g-2">
        <div class="col-md-5">
            <span class="text-muted small d-block">Momento</span>
            <strong>{{ $atividade->descricao }}</strong>
        </div>
        <div class="col-md-3">
            <span class="text-muted small d-block">Data</span>
            <strong>
                {{ \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') }}
                — {{ \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') }}
            </strong>
        </div>
        <div class="col-md-4">
            <span class="text-muted small d-block">Municípios</span>
            <strong>
                {{ $atividade->municipios->isNotEmpty()
                    ? $atividade->municipios->map(fn($m) => $m->nome_com_estado ?? $m->nome)->join(', ')
                    : '—' }}
            </strong>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- Nome do educador --}}
    <div class="col-md-6">
        <label class="form-label">Nome do(a) Educador(a) / Formador(a)</label>
        <input type="text" name="nome_educador"
            value="{{ old('nome_educador', $avaliacao->nome_educador) }}"
            class="form-control @error('nome_educador') is-invalid @enderror">
        @error('nome_educador')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Participantes prefeitura --}}
    <div class="col-md-3">
        <label class="form-label">Participantes — Prefeitura</label>
        <input type="number" name="qtd_participantes_prefeitura" min="0" max="9999"
            value="{{ old('qtd_participantes_prefeitura', $avaliacao->qtd_participantes_prefeitura) }}"
            class="form-control @error('qtd_participantes_prefeitura') is-invalid @enderror">
        @error('qtd_participantes_prefeitura')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- Participantes movimentos sociais --}}
    <div class="col-md-3">
        <label class="form-label">Participantes — Movimentos Sociais</label>
        <input type="number" name="qtd_participantes_movimentos_sociais" min="0" max="9999"
            value="{{ old('qtd_participantes_movimentos_sociais', $avaliacao->qtd_participantes_movimentos_sociais) }}"
            class="form-control @error('qtd_participantes_movimentos_sociais') is-invalid @enderror">
        @error('qtd_participantes_movimentos_sociais')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <hr class="my-1">
        <h6 class="fw-semibold text-muted">Avaliações Qualitativas</h6>
    </div>

    @foreach([
        'avaliacao_logistica'          => 'Logística',
        'avaliacao_acolhimento_sme'    => 'Acolhimento / SME',
        'avaliacao_recursos_materiais' => 'Recursos Materiais',
        'avaliacao_planejamento'       => 'Planejamento',
        'avaliacao_links_presenca'     => 'Links e Presença',
        'avaliacao_destaques'          => 'Destaques',
        'avaliacao_atuacao_equipe'     => 'Atuação da Equipe',
    ] as $campo => $label)
    <div class="col-12">
        <label class="form-label">{{ $label }}</label>
        <textarea name="{{ $campo }}" rows="3"
            class="form-control @error($campo) is-invalid @enderror"
            placeholder="Descreva...">{{ old($campo, $avaliacao->$campo) }}</textarea>
        @error($campo)<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @endforeach

    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
        <a href="{{ route('eventos.show', $atividade->evento_id) }}"
           class="btn btn-outline-secondary">Cancelar</a>
        <button type="submit" class="btn btn-engaja">
            {{ $submitLabel ?? 'Salvar relatório' }}
        </button>
    </div>

</div>