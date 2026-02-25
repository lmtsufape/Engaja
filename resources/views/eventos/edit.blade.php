@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 fw-bold text-engaja mb-0">Editar ação pedagógica</h1>
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
            <form method="POST" action="{{ route('eventos.update', $evento) }}"
                  class="row g-3" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Eixo --}}
                <div class="col-md-6">
                    <label for="eixo_id" class="form-label">Eixo <span class="text-danger">*</span></label>
                    <select id="eixo_id" name="eixo_id"
                            class="form-select @error('eixo_id') is-invalid @enderror" required>
                        @foreach ($eixos as $eixo)
                        <option value="{{ $eixo->id }}"
                            @selected(old('eixo_id', $evento->eixo_id) == $eixo->id)>
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
                           value="{{ old('nome', $evento->nome) }}"
                           class="form-control @error('nome') is-invalid @enderror" required>
                    @error('nome')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Tipo (RESTURADO O SELECT ORIGINAL) --}}
                <div class="col-md-4">
                    <label for="tipo" class="form-label">Tipo</label>
                    @php $tipoSelecionado = old('tipo', $evento->tipo); @endphp
                    <select id="tipo" name="tipo" class="form-select @error('tipo') is-invalid @enderror">
                        <option value="">Selecione...</option>
                        <option value="Cartas para Esperançar" @selected($tipoSelecionado=="Cartas para Esperançar")>Cartas para Esperançar</option>
                        <option value="Curso Como Alfabetizar com Paulo Freire" @selected($tipoSelecionado=="Curso Como Alfabetizar com Paulo Freire")>Curso Como Alfabetizar com Paulo Freire</option>
                        <option value="Encontros Escuta Territorial" @selected($tipoSelecionado=="Encontros Escuta Territorial")>Encontros Escuta Territorial</option>
                        <option value="Encontros de Educandos" @selected($tipoSelecionado=="Encontros de Educandos")>Encontros de Educandos</option>
                        <option value="Encontros de Formação" @selected($tipoSelecionado=="Encontros de Formação")>Encontros de Formação</option>
                        <option value="Feira Pedagógica, Artístico-Cultural com Educandos" @selected($tipoSelecionado=="Feira Pedagógica, Artístico-Cultural com Educandos")>Feira Pedagógica, Artístico-Cultural com Educandos</option>
                        <option value="Lives e Webinars" @selected($tipoSelecionado=="Lives e Webinars")>Lives e Webinars</option>
                        <option value="Reunião de Assessoria" @selected($tipoSelecionado=="Reunião de Assessoria")>Reunião de Assessoria</option>
                        <option value="Seminários de Práticas" @selected($tipoSelecionado=="Seminários de Práticas")>Seminários de Práticas</option>
                        <option value="Veja as Palavras" @selected($tipoSelecionado=="Veja as Palavras")>Veja as Palavras</option>
                    </select>
                    @error('tipo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Modalidade (RESTURADO O SELECT ORIGINAL) --}}
                <div class="col-md-4">
                    <label for="modalidade" class="form-label">Modalidade</label>
                    <select id="modalidade" name="modalidade" class="form-select @error('modalidade') is-invalid @enderror">
                        <option value="">Selecione...</option>
                        <option value="Presencial" @selected(old('modalidade', $evento->modalidade)=="Presencial")>Presencial</option>
                        <option value="Online" @selected(old('modalidade', $evento->modalidade)=="Online")>Online</option>
                        <option value="Híbrido" @selected(old('modalidade', $evento->modalidade)=="Híbrido")>Híbrido</option>
                    </select>
                    @error('modalidade') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                {{-- Local --}}
                <div class="col-md-4">
                    <label for="local" class="form-label">Local</label>
                    <input id="local" name="local" type="text"
                           value="{{ old('local', $evento->local) }}" class="form-control" placeholder="Auditório / Link">
                    @error('local')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Datas --}}
                <div class="col-md-3">
                    <label for="data_inicio" class="form-label">Data de início</label>
                    <input id="data_inicio" name="data_inicio" type="date"
                           value="{{ old('data_inicio', optional($evento->data_inicio ? \Carbon\Carbon::parse($evento->data_inicio) : null)?->format('Y-m-d')) }}"
                           class="form-control @error('data_inicio') is-invalid @enderror">
                    @error('data_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="data_fim" class="form-label">Data de término</label>
                    <input id="data_fim" name="data_fim" type="date"
                           value="{{ old('data_fim', optional($evento->data_fim ? \Carbon\Carbon::parse($evento->data_fim) : null)?->format('Y-m-d')) }}"
                           class="form-control @error('data_fim') is-invalid @enderror">
                    @error('data_fim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Link --}}
                <div class="col-md-6">
                    <label for="link" class="form-label">Link (se online)</label>
                    <input id="link" name="link" type="url"
                           value="{{ old('link', $evento->link) }}"
                           class="form-control @error('link') is-invalid @enderror" placeholder="https://...">
                    @error('link')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- Imagem --}}
                <div class="col-md-6">
                    <label class="form-label d-block">Imagem da ação pedagógica</label>
                    @if ($evento->imagem)
                        <img src="{{ asset('storage/'.$evento->imagem) }}"
                             alt="Imagem atual" class="img-fluid rounded mb-2" style="max-height:160px">
                    @else
                        <img src="{{ asset('images/logo-aeb.png') }}"
                             alt="Sem imagem" class="img-fluid rounded mb-2"
                             style="max-height:160px; opacity:.4">
                    @endif
                    <input id="imagem" name="imagem" type="file"
                           class="form-control @error('imagem') is-invalid @enderror" accept="image/*">
                    <div class="form-text">Deixe em branco para manter a atual. Máx. 2MB</div>
                    @error('imagem')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    
                    {{-- Pré-visualização da nova imagem --}}
                    <img id="preview-imagem" class="mt-2 img-fluid d-none rounded" alt="Pré-visualização" style="max-height: 160px">
                </div>

                {{-- ══ OBJETIVOS E CONTEXTO ══ --}}
                 <div class="col-12">
                    <hr class="my-1">
                    <h5 class="fw-semibold text-muted mb-3">Objetivos e Contexto Pedagógico</h5>
                </div>

                <div class="col-12">
                    <label for="objetivos_gerais" class="form-label">Objetivos Gerais</label>
                    <textarea id="objetivos_gerais" name="objetivos_gerais" rows="3"
                        class="form-control @error('objetivos_gerais') is-invalid @enderror"
                        placeholder="Descreva os objetivos gerais…">{{ old('objetivos_gerais', $evento->objetivos_gerais) }}</textarea>
                    @error('objetivos_gerais')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="objetivos_especificos" class="form-label">Objetivos Específicos</label>
                    <textarea id="objetivos_especificos" name="objetivos_especificos" rows="3"
                        class="form-control @error('objetivos_especificos') is-invalid @enderror"
                        placeholder="Descreva os objetivos específicos…">{{ old('objetivos_especificos', $evento->objetivos_especificos) }}</textarea>
                    @error('objetivos_especificos')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="recursos_materiais_necessarios" class="form-label">Recursos Materiais Necessários</label>
                    <textarea id="recursos_materiais_necessarios" name="recursos_materiais_necessarios" rows="3"
                        class="form-control @error('recursos_materiais_necessarios') is-invalid @enderror"
                        placeholder="Liste os recursos materiais…">{{ old('recursos_materiais_necessarios', $evento->recursos_materiais_necessarios) }}</textarea>
                    @error('recursos_materiais_necessarios')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="providencias_sme_parceria" class="form-label">Providências junto à SME / Parceria</label>
                    <textarea id="providencias_sme_parceria" name="providencias_sme_parceria" rows="3"
                        class="form-control @error('providencias_sme_parceria') is-invalid @enderror"
                        placeholder="Descreva as providências necessárias…">{{ old('providencias_sme_parceria', $evento->providencias_sme_parceria) }}</textarea>
                    @error('providencias_sme_parceria')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label for="observacoes_complementares" class="form-label">Observações Complementares</label>
                    <textarea id="observacoes_complementares" name="observacoes_complementares" rows="3"
                        class="form-control @error('observacoes_complementares') is-invalid @enderror"
                        placeholder="Observações adicionais…">{{ old('observacoes_complementares', $evento->observacoes_complementares) }}</textarea>
                    @error('observacoes_complementares')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                {{-- ══ SITUAÇÕES DESAFIADORAS ══ --}}
                @if(isset($situacoes) && $situacoes->isNotEmpty())
                @php
                    $sitsSelecionadas = old('situacoes_desafiadoras',
                        $evento->situacoesDesafiadoras->pluck('id')->toArray());
                @endphp
                <div class="col-12">
                    <hr class="my-1">
                    <h5 class="fw-semibold text-muted mb-2">Situações Desafiadoras da EJA</h5>
                    @foreach($situacoes as $categoria => $itens)
                        <p class="text-uppercase small text-secondary mb-1 mt-3">{{ $categoria }}</p>
                        <div class="border rounded p-3" style="max-height:200px; overflow-y:auto;">
                            @foreach($itens as $situacao)
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox"
                                    name="situacoes_desafiadoras[]"
                                    id="sit_{{ $situacao->id }}"
                                    value="{{ $situacao->id }}"
                                    @checked(in_array($situacao->id, $sitsSelecionadas))>
                                <label class="form-check-label small" for="sit_{{ $situacao->id }}">
                                {{ $situacao->nome }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                @endif

                {{-- ══ MATRIZ DE APRENDIZAGENS ══ --}}
                @if(isset($matrizes) && $matrizes->isNotEmpty())
                @php
                    $matrizesSelecionadas = old('matrizes',
                        $evento->matrizes->pluck('id')->toArray());
                @endphp
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
                                @checked(in_array($matriz->id, $matrizesSelecionadas))>
                            <label class="form-check-label" for="mat_{{ $matriz->id }}">
                                <strong>{{ $matriz->nome }}</strong>
                                <div class="text-muted small" style="white-space:pre-line">{{ $matriz->descricao }}</div>
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- ══ SEQUÊNCIA DIDÁTICA ══ --}}
                @php
                    $sequenciasExistentes = old('sequencias',
                        $evento->sequenciasDidaticas->map(fn($s) => [
                            'periodo'   => $s->periodo,
                            'descricao' => $s->descricao,
                        ])->toArray()
                    );
                @endphp
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
                            <input type="number" id="qtd_dias" min="0" max="30"
                                value="{{ count($sequenciasExistentes) }}"
                                oninput="ajustarBlocosSequencia(this.value)"
                                class="form-control" style="width:90px">
                        </div>
                    </div>

                    <div id="blocos-sequencia"></div>
                </div>

                {{-- Botões --}}
                <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                    <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-engaja">Salvar alterações</button>
                </div>

            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Preview de Imagem
    document.getElementById('imagem')?.addEventListener('change', function (e) {
        const file = e.target.files?.[0];
        const img  = document.getElementById('preview-imagem');
        if (file && img) {
            img.src = URL.createObjectURL(file);
            img.classList.remove('d-none');
        }
    });

    // Sequência Didática — vanilla JS
    const sequenciasIniciais = @json($sequenciasExistentes);

    function escHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function criarBlocoSequencia(index, periodo, descricao) {
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
                    <textarea
                        name="sequencias[${index}][descricao]"
                        rows="3"
                        class="form-control"
                        placeholder="Descreva as atividades previstas…">${escHtml(descricao)}</textarea>
                </div>
            </div>
        </div>`;
    }

    function ajustarBlocosSequencia(qtd) {
        qtd = Math.max(0, Math.min(30, parseInt(qtd) || 0));
        const container = document.getElementById('blocos-sequencia');
        const atual = container.children.length;

        if (qtd > atual) {
            for (let i = atual; i < qtd; i++) {
                const seq = sequenciasIniciais[i] ?? { periodo: '', descricao: '' };
                container.insertAdjacentHTML('beforeend', criarBlocoSequencia(i, seq.periodo, seq.descricao));
            }
        } else {
            while (container.children.length > qtd) {
                container.removeChild(container.lastChild);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        ajustarBlocosSequencia(sequenciasIniciais.length);
    });
</script>
@endpush
@endsection