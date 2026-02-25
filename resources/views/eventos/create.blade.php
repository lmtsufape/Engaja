@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-engaja mb-0">Nova ação pedagógica</h1>
        <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary">Voltar</a>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <strong>Ops!</strong> Verifique os campos abaixo.
        <ul class="mb-0 mt-1">
            @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
        </ul>
    </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('eventos.store') }}" class="row g-3" enctype="multipart/form-data">
                @csrf

                {{-- Eixo --}}
                <div class="col-md-6">
                    <label for="eixo_id" class="form-label">Eixo <span class="text-danger">*</span></label>
                    <select id="eixo_id" name="eixo_id"
                        class="form-select @error('eixo_id') is-invalid @enderror" required>
                        <option value="">Selecione…</option>
                        @foreach ($eixos as $eixo)
                        <option value="{{ $eixo->id }}" @selected(old('eixo_id') == $eixo->id)>
                            {{ $eixo->nome }}
                        </option>
                        @endforeach
                    </select>
                    @error('eixo_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Nome --}}
                <div class="col-md-6">
                    <label for="nome" class="form-label">Nome da ação pedagógica <span class="text-danger">*</span></label>
                    <input id="nome" name="nome" type="text"
                        value="{{ old('nome') }}"
                        class="form-control @error('nome') is-invalid @enderror" required>
                    @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Tipo --}}
                <div class="col-md-4">
                    <label for="tipo" class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" class="form-select @error('tipo') is-invalid @enderror">
                        <option value="">Selecione...</option>
                        <option value="Cartas para Esperançar" @selected(old('tipo')=="Cartas para Esperançar" )>Cartas para Esperançar</option>
                        <option value="Curso Como Alfabetizar com Paulo Freire" @selected(old('tipo')=="Curso Como Alfabetizar com Paulo Freire" )>Curso Como Alfabetizar com Paulo Freire</option>
                        <option value="Encontros Escuta Territorial" @selected(old('tipo')=="Encontros Escuta Territorial" )>Encontros Escuta Territorial</option>
                        <option value="Encontros de Educandos" @selected(old('tipo')=="Encontros de Educandos" )>Encontros de Educandos</option>
                        <option value="Encontros de Formação" @selected(old('tipo')=="Encontros de Formação" )>Encontros de Formação</option>
                        <option value="Feira Pedagógica, Artístico-Cultural com Educandos" @selected(old('tipo')=="Feira Pedagógica, Artístico-Cultural com Educandos" )>Feira Pedagógica, Artístico-Cultural com Educandos</option>
                        <option value="Lives e Webinars" @selected(old('tipo')=="Lives e Webinars" )>Lives e Webinars</option>
                        <option value="Reunião de Assessoria" @selected(old('tipo')=="Reunião de Assessoria" )>Reunião de Assessoria</option>
                        <option value="Seminários de Práticas" @selected(old('tipo')=="Seminários de Práticas" )>Seminários de Práticas</option>
                        <option value="Veja as Palavras" @selected(old('tipo')=="Veja as Palavras" )>Veja as Palavras</option>
                    </select>
                    @error('tipo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Modalidade --}}
                <div class="col-md-4">
                    <label for="modalidade" class="form-label">Modalidade</label>
                    <select id="modalidade" name="modalidade" class="form-select @error('modalidade') is-invalid @enderror">
                        <option value="">Selecione...</option>
                        <option value="Presencial" @selected(old('modalidade')=="Presencial" )>Presencial</option>
                        <option value="Online" @selected(old('modalidade')=="Online" )>Online</option>
                        <option value="Híbrido" @selected(old('modalidade')=="Híbrido" )>Híbrido</option>
                    </select>
                    @error('modalidade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Local --}}
                <div class="col-md-4">
                    <label for="local" class="form-label">Local</label>
                    <input id="local" name="local" type="text" value="{{ old('local') }}"
                        placeholder="Auditório / Link"
                        class="form-control @error('local') is-invalid @enderror">
                    @error('local')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Datas --}}
                <div class="col-md-3">
                    <label for="data_inicio" class="form-label">Data de início</label>
                    <input id="data_inicio" name="data_inicio" type="date"
                        value="{{ old('data_inicio') }}"
                        class="form-control @error('data_inicio') is-invalid @enderror">
                    @error('data_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="data_fim" class="form-label">Data de término</label>
                    <input id="data_fim" name="data_fim" type="date"
                        value="{{ old('data_fim') }}"
                        class="form-control @error('data_fim') is-invalid @enderror">
                    @error('data_fim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Link --}}
                <div class="col-md-6">
                    <label for="link" class="form-label">Link (se online)</label>
                    <input id="link" name="link" type="url" value="{{ old('link') }}"
                        class="form-control @error('link') is-invalid @enderror" placeholder="https://...">
                    @error('link')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Imagem --}}
                <div class="col-md-6">
                    <label for="imagem" class="form-label">Imagem da ação pedagógica</label>
                    <input id="imagem" name="imagem" type="file"
                        class="form-control @error('imagem') is-invalid @enderror" accept="image/*">
                    <div class="form-text">Formatos: JPG, PNG, SVG | Máx. 2MB</div>
                    @error('imagem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <img id="preview-imagem" class="mt-2 img-fluid d-none rounded"
                         alt="Pré-visualização" style="max-height:160px">
                </div>

                {{-- ══ OBJETIVOS E CONTEXTO ══ --}}
                <div class="col-12">
                    <hr class="my-1">
                    <h5 class="fw-semibold text-muted mb-3">Objetivos e Contexto Pedagógico</h5>
                </div>

                {{-- Objetivo Original --}}
                <div class="col-12">
                    <label for="objetivos_gerais" class="form-label">Objetivos Gerais</label>
                    <textarea id="objetivos_gerais" name="objetivos_gerais" rows="3"
                        class="form-control @error('objetivos_gerais') is-invalid @enderror"
                        placeholder="Descreva os objetivos gerais da ação pedagógica…">{{ old('objetivos_gerais') }}</textarea>
                    @error('objetivos_gerais')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="objetivos_especificos" class="form-label">Objetivos Específicos</label>
                    <textarea id="objetivos_especificos" name="objetivos_especificos" rows="3"
                        class="form-control @error('objetivos_especificos') is-invalid @enderror"
                        placeholder="Descreva os objetivos específicos…">{{ old('objetivos_especificos') }}</textarea>
                    @error('objetivos_especificos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="recursos_materiais_necessarios" class="form-label">Recursos Materiais Necessários</label>
                    <textarea id="recursos_materiais_necessarios" name="recursos_materiais_necessarios" rows="3"
                        class="form-control @error('recursos_materiais_necessarios') is-invalid @enderror"
                        placeholder="Liste os materiais necessários…">{{ old('recursos_materiais_necessarios') }}</textarea>
                    @error('recursos_materiais_necessarios')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="providencias_sme_parceria" class="form-label">Providências junto à SME / Parceria</label>
                    <textarea id="providencias_sme_parceria" name="providencias_sme_parceria" rows="3"
                        class="form-control @error('providencias_sme_parceria') is-invalid @enderror"
                        placeholder="Descreva as providências necessárias…">{{ old('providencias_sme_parceria') }}</textarea>
                    @error('providencias_sme_parceria')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="observacoes_complementares" class="form-label">Observações Complementares</label>
                    <textarea id="observacoes_complementares" name="observacoes_complementares" rows="3"
                        class="form-control @error('observacoes_complementares') is-invalid @enderror"
                        placeholder="Observações adicionais…">{{ old('observacoes_complementares') }}</textarea>
                    @error('observacoes_complementares')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- ══ SITUAÇÕES DESAFIADORAS ══ --}}
                @if(isset($situacoes) && $situacoes->isNotEmpty())
                <div class="col-12">
                    <hr class="my-1">
                    <h5 class="fw-semibold text-muted mb-2">Situações Desafiadoras da EJA</h5>
                    @foreach($situacoes as $categoria => $itens)
                        <p class="fw-semibold text-uppercase small text-secondary mb-1 mt-3">{{ $categoria }}</p>
                        <div class="border rounded p-3" style="max-height:200px; overflow-y:auto;">
                            @foreach($itens as $situacao)
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox"
                                    name="situacoes_desafiadoras[]"
                                    id="sit_{{ $situacao->id }}"
                                    value="{{ $situacao->id }}"
                                    @checked(in_array($situacao->id, old('situacoes_desafiadoras', [])))>
                                <label class="form-check-label small" for="sit_{{ $situacao->id }}">
                                {{ $situacao->nome }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    @endforeach
                    @error('situacoes_desafiadoras')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                @endif

                {{-- ══ MATRIZ DE APRENDIZAGENS ══ --}}
                @if(isset($matrizes) && $matrizes->isNotEmpty())
                <div class="col-12">
                    <hr class="my-1">
                    <h5 class="fw-semibold text-muted mb-2">Matriz de Aprendizagens</h5>
                    <div class="border rounded p-3" style="max-height:320px; overflow-y:auto;">
                        @foreach($matrizes as $matriz)
                        <div class="form-check mb-2">
                            <input class="form-check-input" type="checkbox"
                                name="matrizes[]"
                                id="mat_{{ $matriz->id }}"
                                value="{{ $matriz->id }}"
                                @checked(in_array($matriz->id, old('matrizes', [])))>
                            <label class="form-check-label" for="mat_{{ $matriz->id }}">
                                <strong>{{ $matriz->nome }}</strong>
                                <div class="text-muted small" style="white-space:pre-line">{{ $matriz->descricao }}</div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                    @error('matrizes')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
                @endif

                {{-- ══ SEQUÊNCIA DIDÁTICA ══ --}}
                <div class="col-12">
                    <hr class="my-1">
                    <h5 class="fw-semibold text-muted mb-3">Sequência Didática</h5>

                    <div class="row align-items-center g-2 mb-3">
                        <div class="col-auto">
                            <label class="col-form-label">
                                Esta ação irá se realizar em mais de um dia/período?
                                Se sim, indique quantos dias:
                            </label>
                        </div>
                        <div class="col-auto">
                            <input type="number" id="qtd_dias_create" min="0" max="30"
                                   value="{{ count(old('sequencias', [])) }}"
                                   class="form-control" style="width:90px" placeholder="0">
                        </div>
                    </div>

                    <div id="sequencias-container-create"></div>
                </div>

                {{-- Botões --}}
                <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                    <a href="{{ route('eventos.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-engaja">Salvar ação pedagógica</button>
                </div>

            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('imagem')?.addEventListener('change', function (e) {
        const preview = document.getElementById('preview-imagem');
        const file = e.target.files[0];
        if (file) {
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');
        }
    });

    const sequenciasIniciaisCreate = @json(old('sequencias', []));

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function criarBlocoCreate(index, periodo, descricao) {
        return `
        <div class="card mb-3 border-secondary-subtle">
            <div class="card-header bg-light py-2 d-flex align-items-center gap-2">
                <span class="badge bg-secondary">${index + 1}</span>
                <strong>Dia / Período ${index + 1}</strong>
            </div>
            <div class="card-body row g-3">
                <div class="col-md-4">
                    <label class="form-label">Dia / Período</label>
                    <input type="text"
                           name="sequencias[${index}][periodo]"
                           value="${escHtml(periodo)}"
                           class="form-control"
                           placeholder="Ex.: Dia 1 – Manhã">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Descrição do passo a passo</label>
                    <textarea name="sequencias[${index}][descricao]"
                              class="form-control"
                              rows="3"
                              placeholder="Descreva as atividades previstas...">${escHtml(descricao)}</textarea>
                </div>
            </div>
        </div>`;
    }

    function renderizarSequenciasCreate() {
        const qtd       = parseInt(document.getElementById('qtd_dias_create')?.value) || 0;
        const container = document.getElementById('sequencias-container-create');
        if (!container) return;

        const atual = container.querySelectorAll('.card').length;

        if (qtd > atual) {
            for (let i = atual; i < qtd; i++) {
                const seq = sequenciasIniciaisCreate[i] ?? {};
                container.insertAdjacentHTML('beforeend',
                    criarBlocoCreate(i, seq.periodo ?? '', seq.descricao ?? '')
                );
            }
        } else {
            const cards = container.querySelectorAll('.card');
            for (let i = qtd; i < cards.length; i++) {
                cards[i].remove();
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (sequenciasIniciaisCreate.length > 0) {
            document.getElementById('qtd_dias_create').value = sequenciasIniciaisCreate.length;
        }
        renderizarSequenciasCreate();

        document.getElementById('qtd_dias_create')
            ?.addEventListener('input', renderizarSequenciasCreate);
    });
</script>
@endpush
@endsection