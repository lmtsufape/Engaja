@csrf

{{-- ── Cabeçalho com dados do Momento ───────────────────────────── --}}
<div class="alert alert-light border mb-4">
    <div class="row g-2">
        <div class="col-md-5">
            <span class="text-muted small d-block">Momento</span>
            <strong>{{ $atividade->descricao }}</strong>
        </div>
        <div class="col-md-3">
            <span class="text-muted small d-block">Data de realização</span>
            <strong>
                {{ \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') }}
                — {{ \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') }}
            </strong>
        </div>
        <div class="col-md-4">
            <span class="text-muted small d-block">Município(s)</span>
            <strong>
                {{ $atividade->municipios->isNotEmpty()
                    ? $atividade->municipios->map(fn($m) => $m->nome_com_estado ?? $m->nome)->join(', ')
                    : '—' }}
            </strong>
        </div>
    </div>
</div>

{{-- ── Links rápidos ─────────────────────────────────────────────── --}}
@php $primeiraAval = $atividade->avaliacoes->first() ?? null; @endphp
<div class="d-flex flex-wrap gap-2 mb-4">
    @if($primeiraAval)
    <a href="{{ route('atividades.avaliacoes', $atividade) }}"
       class="btn btn-sm btn-outline-info" target="_blank">
        📊 Gráficos da Avaliação dos Participantes
    </a>
    @endif

    <a href="{{ route('atividades.show', $atividade) }}"
       class="btn btn-sm btn-outline-secondary" target="_blank">
        📋 Lista de Presença
    </a>

    <a href="{{ route('eventos.planejamento.pdf', $atividade->evento) }}"
       class="btn btn-sm btn-outline-danger" target="_blank">
        📄 Planejamento da Ação
    </a>
</div>

<div class="row g-4">

    {{-- ── 1. Nome do(a) Educador(a) ─────────────────────────────── --}}
    <div class="col-md-8">
        <label class="form-label fw-semibold">Nome Completo do(a) Educador(a)</label>
        <input type="text" name="nome_educador"
            value="{{ old('nome_educador', $avaliacao->nome_educador) }}"
            class="form-control @error('nome_educador') is-invalid @enderror"
            placeholder="Nome completo">
        @error('nome_educador')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- ── 2. Quadro Resumo de Público (somente leitura) ──────────── --}}
    <div class="col-12">
        <h6 class="fw-semibold mb-2" style="color:#421944;">📊 Quadro Resumo de Público</h6>
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0" style="max-width:600px;">
                <tbody>
                    <tr>
                        <th class="bg-light" style="width:70%">Quantidade prevista de participantes</th>
                        <td class="text-center fw-semibold">{{ $resumoPublico['prevista'] ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Quantidade de inscritos</th>
                        <td class="text-center fw-semibold">{{ $resumoPublico['inscritos'] ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Quantidade de presentes na Ação</th>
                        <td class="text-center fw-semibold">{{ $resumoPublico['presentes'] ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Participantes ligados aos movimentos sociais</th>
                        <td class="text-center fw-semibold">{{ $resumoPublico['movimentos'] ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th class="bg-light">Participantes com vínculo com a Prefeitura</th>
                        <td class="text-center fw-semibold">{{ $resumoPublico['prefeitura'] ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        {{-- Campos ocultos para preservar os valores já registados --}}
        <input type="hidden" name="qtd_participantes_movimentos_sociais"
               value="{{ old('qtd_participantes_movimentos_sociais', $avaliacao->qtd_participantes_movimentos_sociais) }}">
        <input type="hidden" name="qtd_participantes_prefeitura"
               value="{{ old('qtd_participantes_prefeitura', $avaliacao->qtd_participantes_prefeitura) }}">
    </div>

    <div class="col-12"><hr class="my-0"></div>

    {{-- ── 3. Avaliação da Logística ──────────────────────────────── --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Avaliação da Logística</label>
        <div class="form-text mb-2">
            (Faça a sua avaliação desta ação em relação aos aspectos logísticos como transporte,
            hospedagem, alimentação e recursos disponibilizados, quando for o caso)
        </div>
        <textarea name="avaliacao_logistica" rows="4"
            class="form-control @error('avaliacao_logistica') is-invalid @enderror"
            placeholder="Sua avaliação...">{{ old('avaliacao_logistica', $avaliacao->avaliacao_logistica) }}</textarea>
        @error('avaliacao_logistica')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- ── 4. Avaliação do acolhimento e apoio da SME ─────────────── --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Avaliação do acolhimento e apoio da SME</label>
        <div class="form-text mb-2">
            (Faça a sua avaliação quando a prontidão da SME em resolver eventuais imprevistos, em apoiar
            a organização da ação, bem como da sua presença e participação nos momentos chaves como
            abertura e encerramento)
        </div>
        <textarea name="avaliacao_acolhimento_sme" rows="4"
            class="form-control @error('avaliacao_acolhimento_sme') is-invalid @enderror"
            placeholder="Sua avaliação...">{{ old('avaliacao_acolhimento_sme', $avaliacao->avaliacao_acolhimento_sme) }}</textarea>
        @error('avaliacao_acolhimento_sme')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- ── 5. Atuação da Equipe do IPF ───────────────────────────── --}}
    <div class="col-12">
        <label class="form-label fw-semibold">Atuação da Equipe do IPF</label>
        <div class="form-text mb-2">
            (Faça uma avaliação do trabalho e atuação da equipe do IPF que esteve envolvida nesta ação,
            tendo por referência o nosso guia de ética e de convivência)
        </div>
        <textarea name="avaliacao_atuacao_equipe" rows="4"
            class="form-control @error('avaliacao_atuacao_equipe') is-invalid @enderror"
            placeholder="Sua avaliação...">{{ old('avaliacao_atuacao_equipe', $avaliacao->avaliacao_atuacao_equipe) }}</textarea>
        @error('avaliacao_atuacao_equipe')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- ── 6. Desenvolvimento da Ação (4 textareas) ──────────────── --}}
    <div class="col-12">
        <h6 class="fw-semibold mb-3" style="color:#421944;">📝 Desenvolvimento da Ação</h6>
        <div class="row g-3">

            <div class="col-12">
                <label class="form-label fw-semibold">
                    O Planejamento desta ação se mostrou suficiente e adequado?
                </label>
                <div class="form-text mb-2">
                    (Considerar nesta sua análise as situações desafiadoras da Leitura do Mundo,
                    a Matriz de Aprendizagens e os ODS associados a essa ação)
                </div>
                <textarea name="avaliacao_planejamento" rows="4"
                    class="form-control @error('avaliacao_planejamento') is-invalid @enderror"
                    placeholder="Sua avaliação...">{{ old('avaliacao_planejamento', $avaliacao->avaliacao_planejamento) }}</textarea>
                @error('avaliacao_planejamento')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">
                    Os recursos materiais utilizados atenderam aos objetivos da ação?
                </label>
                <textarea name="avaliacao_recursos_materiais" rows="4"
                    class="form-control @error('avaliacao_recursos_materiais') is-invalid @enderror"
                    placeholder="Sua avaliação...">{{ old('avaliacao_recursos_materiais', $avaliacao->avaliacao_recursos_materiais) }}</textarea>
                @error('avaliacao_recursos_materiais')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">
                    Os links e QR codes de acesso à lista de presença, ficha de avaliação do encontro,
                    entre outros, funcionaram corretamente?
                </label>
                <textarea name="avaliacao_links_presenca" rows="4"
                    class="form-control @error('avaliacao_links_presenca') is-invalid @enderror"
                    placeholder="Sua avaliação...">{{ old('avaliacao_links_presenca', $avaliacao->avaliacao_links_presenca) }}</textarea>
                @error('avaliacao_links_presenca')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
                <label class="form-label fw-semibold">
                    Que destaques sobre essa ação você acha importante fazer?
                </label>
                <textarea name="avaliacao_destaques" rows="4"
                    class="form-control @error('avaliacao_destaques') is-invalid @enderror"
                    placeholder="Sua avaliação...">{{ old('avaliacao_destaques', $avaliacao->avaliacao_destaques) }}</textarea>
                @error('avaliacao_destaques')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

        </div>
    </div>

    {{-- ── 7. Checklist Pós-Ação ──────────────────────────────────── --}}
    @php
        $checklistPosAcaoItems = [
            'upload_evidencias'       => 'Fez o upload das evidências (fotos, vídeos com depoimentos) na pasta correspondente a essa ação dentro do Drive',
            'lista_presenca_digital'  => 'Conferiu as listas de presença digital (link acima), garantindo que todos os campos estejam devidamente preenchidos',
            'lista_presenca_impressa' => 'Conferiu as listas de presença impressa, garantindo que todos os campos estejam devidamente preenchidos',
            'upload_lista_impressa'   => 'Fez o upload das listas de presença impressas na pasta dentro do Drive, depois de devidamente conferida e ajustada',
        ];
        $checklistSalvo = old('checklist_pos_acao', $avaliacao->checklist_pos_acao ?? []);
    @endphp

    <div class="col-12">
        <h6 class="fw-semibold mb-1" style="color:#421944;">✅ Checklist Pós-Ação</h6>
        <div class="form-text mb-3">(Clique nas tarefas pós ação já concluídas)</div>

        @foreach($checklistPosAcaoItems as $valor => $label)
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox"
                   name="checklist_pos_acao[]"
                   value="{{ $valor }}"
                   id="chk_{{ $valor }}"
                   @checked(in_array($valor, $checklistSalvo ?? []))>
            <label class="form-check-label" for="chk_{{ $valor }}">
                {{ $label }}
            </label>
        </div>
        @endforeach
    </div>

    {{-- ── Botões de ação ─────────────────────────────────────────── --}}
    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
        <a href="{{ route('eventos.show', $atividade->evento_id) }}"
           class="btn btn-outline-secondary">Cancelar</a>
        @if(($avaliacao->id ?? null))
        <a href="{{ route('avaliacao-atividade.download', $avaliacao) }}"
           class="btn btn-outline-dark" target="_blank">Baixar PDF</a>
        @endif
        <button type="submit" class="btn btn-engaja">
            {{ $submitLabel ?? 'Salvar relatório' }}
        </button>
    </div>

</div>