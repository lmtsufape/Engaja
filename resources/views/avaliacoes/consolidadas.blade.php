@extends('layouts.app')

@section('content')

{{-- ══════════════════════════════════════════════════
     HERO HEADER
══════════════════════════════════════════════════ --}}
<div class="csl-hero">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between gap-3 flex-wrap py-4">
            <div>
                <span class="csl-breadcrumb">
                    <i class="bi bi-bar-chart-line-fill me-1"></i>Relatórios
                </span>
                <h1 class="csl-hero__title">Consolidação de Avaliações</h1>
                <p class="csl-hero__subtitle">Médias, distribuições e respostas consolidadas por modelo de avaliação.</p>
            </div>
            @if ($evento)
            <a id="btn-download-pdf"
               href="{{ route('avaliacoes-consolidadas.pdf', ['evento_id' => $evento->id, 'agrupamento' => $agrupamento]) }}"
               class="btn csl-btn-pdf"
               target="_blank">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>
                Baixar PDF
            </a>
            <a id="btn-download-docx"
               href="{{ route('avaliacoes-consolidadas.pdf', ['evento_id' => $evento->id, 'agrupamento' => $agrupamento, 'formato' => 'docx']) }}"
               class="btn btn-outline-primary">
                <i class="bi bi-file-earmark-word me-1"></i>
                Baixar Word
            </a>
            @endif
        </div>
    </div>
</div>

<div class="container py-4">

    {{-- ══════════════════════════════════════════════════
         FILTROS
    ══════════════════════════════════════════════════ --}}
    <div class="csl-filter-card mb-5">
        <form id="filtros-form" method="GET" action="{{ route('avaliacoes-consolidadas.index') }}">
            <div class="row g-3 align-items-center">
                <div class="col-lg-7">
                    <label for="evento_id" class="csl-filter-label">
                        <i class="bi bi-calendar-event me-1"></i> Ação Pedagógica
                    </label>
                    <div class="csl-select-wrapper">
                        <select name="evento_id" id="evento_id" class="csl-select js-autosubmit">
                            @foreach ($eventos as $eventoOpcao)
                                <option value="{{ $eventoOpcao->id }}" @selected($evento && $evento->id === $eventoOpcao->id)>
                                    {{ $eventoOpcao->nome }}
                                </option>
                            @endforeach
                        </select>
                        <i class="bi bi-chevron-down csl-select-chevron"></i>
                    </div>
                </div>
                <div class="col-lg-5">
                    <label for="agrupamento" class="csl-filter-label">
                        <i class="bi bi-collection me-1"></i> Agrupar por
                    </label>
                    <div class="csl-segmented" id="agrupamento-group">
                        @foreach (['geral' => 'Todos', 'regiao' => 'Região', 'municipio' => 'Município'] as $val => $label)
                            <button type="button"
                                    class="csl-seg-btn {{ $agrupamento === $val ? 'active' : '' }}"
                                    data-value="{{ $val }}">
                                {{ $label }}
                            </button>
                        @endforeach
                        <input type="hidden" name="agrupamento" id="agrupamento" value="{{ $agrupamento }}">
                    </div>
                </div>
            </div>
        </form>
    </div>

    @if (! $evento)
        {{-- ESTADO VAZIO: sem seleção --}}
        <div class="csl-empty-state">
            <div class="csl-empty-icon">
                <i class="bi bi-bar-chart-steps"></i>
            </div>
            <h5>Selecione uma ação pedagógica</h5>
            <p>Os dados de avaliação consolidados aparecerão aqui assim que você escolher uma ação acima.</p>
        </div>

    @elseif (empty($grupos))
        <div class="csl-empty-state">
            <div class="csl-empty-icon csl-empty-icon--muted">
                <i class="bi bi-inbox"></i>
            </div>
            <h5>Sem respostas registradas</h5>
            <p>Nenhuma avaliação respondida foi encontrada para esta ação pedagógica.</p>
        </div>

    @else
        @php
            $totalSubmissoes = collect($grupos)->sum(fn($g) => collect($g['templates'])->sum('submissoes'));
            $totalRespostas  = collect($grupos)->sum(fn($g) => collect($g['templates'])->sum('respostas'));
            $totalModelos    = collect($grupos)->sum(fn($g) => count($g['templates']));
        @endphp

        <div class="csl-stats-row mb-5">
            <div class="csl-stat-card csl-stat-card--purple">
                <div class="csl-stat-body">
                    <div class="csl-stat-value">{{ number_format($totalSubmissoes, 0, ',', '.') }}</div>
                    <div class="csl-stat-label">Submissões</div>
                </div>
                <div class="csl-stat-icon csl-stat-icon--purple">
                    <i class="bi bi-people-fill"></i>
                </div>
            </div>
            <div class="csl-stat-card csl-stat-card--blue">
                <div class="csl-stat-body">
                    <div class="csl-stat-value">{{ number_format($totalRespostas, 0, ',', '.') }}</div>
                    <div class="csl-stat-label">Respostas coletadas</div>
                </div>
                <div class="csl-stat-icon csl-stat-icon--blue">
                    <i class="bi bi-chat-dots-fill"></i>
                </div>
            </div>
            <div class="csl-stat-card csl-stat-card--green">
                <div class="csl-stat-body">
                    <div class="csl-stat-value">{{ $totalModelos }}</div>
                    <div class="csl-stat-label">Modelo(s) de avaliação</div>
                </div>
                <div class="csl-stat-icon csl-stat-icon--green">
                    <i class="bi bi-clipboard2-check-fill"></i>
                </div>
            </div>
            <div class="csl-stat-card csl-stat-card--orange">
                <div class="csl-stat-body">
                    <div class="csl-stat-value csl-stat-value--sm">
                        @switch($agrupamento)
                            @case('regiao')    Por Região    @break
                            @case('municipio') Por Município @break
                            @default           Todos os municípios
                        @endswitch
                    </div>
                    <div class="csl-stat-label">Agrupamento ativo</div>
                </div>
                <div class="csl-stat-icon csl-stat-icon--orange">
                    @switch($agrupamento)
                        @case('regiao')    <i class="bi bi-map-fill"></i>    @break
                        @case('municipio') <i class="bi bi-geo-alt-fill"></i> @break
                        @default           <i class="bi bi-globe2"></i>
                    @endswitch
                </div>
            </div>
        </div>

        {{-- ══════════════════════════════════════════════════
             GRUPOS
        ══════════════════════════════════════════════════ --}}
        @foreach ($grupos as $grupoIndex => $grupo)
            <div class="csl-group mb-5">
                {{-- Cabeçalho do grupo --}}
                <div class="csl-group__header mb-4">
                    <div>
                        <h2 class="csl-group__name">{{ $grupo['nome'] }}</h2>
                        <span class="csl-group__meta">{{ count($grupo['templates']) }} modelo(s) de avaliação</span>
                    </div>
                    <div class="ms-auto">
                        <span class="csl-group__badge">
                            {{ collect($grupo['templates'])->sum('submissoes') }} submissões
                        </span>
                    </div>
                </div>

                @foreach ($grupo['templates'] as $templateIndex => $template)
                    @php
                        $rootId   = 'avaliacoes-consolidado-'.$grupoIndex.'-'.$templateIndex;
                        $mediaGeral = $template['media_geral'];
                        $mediaClass = $mediaGeral === null ? '' : ($mediaGeral >= 4 ? 'csl-media--green' : ($mediaGeral >= 3 ? 'csl-media--yellow' : 'csl-media--red'));
                    @endphp

                    <div class="csl-template-card mb-4">
                        {{-- Cabeçalho do template --}}
                        <div class="csl-template-card__header">
                            <div class="csl-template-card__info">
                                <div class="csl-model-badge">
                                    <i class="bi bi-clipboard2-check-fill me-1"></i>
                                    Modelo de Avaliação
                                </div>
                                <h3 class="csl-template-card__name">{{ $template['template_nome'] }}</h3>
                                <div class="csl-template-card__stats">
                                    <span>
                                        <i class="bi bi-person-check me-1"></i>
                                        <strong>{{ number_format($template['submissoes'] ?? 0, 0, ',', '.') }}</strong> submissões
                                    </span>
                                    <span class="csl-dot"></span>
                                    <span>
                                        <i class="bi bi-chat-dots me-1"></i>
                                        <strong>{{ number_format($template['respostas'] ?? 0, 0, ',', '.') }}</strong> respostas
                                    </span>
                                    <span class="csl-dot"></span>
                                    <span>
                                        <i class="bi bi-list-ol me-1"></i>
                                        <strong>{{ count($template['perguntas'] ?? []) }}</strong> perguntas
                                    </span>
                                </div>
                            </div>

                            {{-- Média geral --}}
                            @if ($mediaGeral !== null)
                            <div class="csl-media-block {{ $mediaClass }}">
                                <div class="csl-media-block__value">
                                    {{ number_format($mediaGeral, 1, ',', '.') }}
                                </div>
                                <div class="csl-media-block__label">Média geral</div>
                                <div class="csl-media-block__sub">
                                    {{ number_format($template['respostas_com_media'] ?? 0, 0, ',', '.') }} resp. em escala
                                </div>
                            </div>
                            @else
                            <div class="csl-media-block csl-media--muted">
                                <div class="csl-media-block__value">—</div>
                                <div class="csl-media-block__label">Sem escala</div>
                            </div>
                            @endif
                        </div>

                        {{-- Corpo: perguntas renderizadas via JS --}}
                        <div class="csl-template-card__body">
                            <div id="{{ $rootId }}" data-avaliacoes-inline-root data-text-modal-id="textAnswersModalConsolidado">
                                <script type="application/json" data-avaliacoes-perguntas>@json($template['perguntas'] ?? [])</script>
                                <div class="vstack gap-4" data-cards-questoes></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endforeach
    @endif
</div>

{{-- Modal de respostas de texto --}}
<div class="modal fade" id="textAnswersModalConsolidado" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 csl-modal-content">
            <div class="modal-header csl-modal-header">
                <h5 class="modal-title js-text-modal-title fw-bold">
                    <i class="bi bi-chat-square-quote me-2"></i>Respostas abertas
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-muted small mb-3 js-text-modal-count"></div>
                <div class="vstack gap-2 js-text-modal-list csl-modal-list"></div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
/* ──────────────────────────────────────────────────────
   HERO
────────────────────────────────────────────────────── */
.csl-hero {
    background: linear-gradient(135deg, #421944 0%, #62305f 60%, #7a3a72 100%);
    color: #fff;
    margin-bottom: 0;
    border-bottom: 3px solid rgba(255,255,255,.08);
}
.csl-breadcrumb {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: rgba(255,255,255,.6);
    font-weight: 600;
}
.csl-hero__title {
    font-size: 1.75rem;
    font-weight: 800;
    color: #fff;
    margin: .25rem 0 .3rem;
    letter-spacing: -.3px;
}
.csl-hero__subtitle {
    font-size: .875rem;
    color: rgba(255,255,255,.72);
    margin: 0;
}
.csl-btn-pdf {
    background: rgba(255,255,255,.12);
    color: #fff;
    border: 1.5px solid rgba(255,255,255,.3);
    border-radius: .75rem;
    padding: .55rem 1.1rem;
    font-size: .875rem;
    font-weight: 600;
    white-space: nowrap;
    backdrop-filter: blur(4px);
    transition: background .2s;
}
.csl-btn-pdf:hover {
    background: rgba(255,255,255,.22);
    color: #fff;
    border-color: rgba(255,255,255,.5);
}

/* ──────────────────────────────────────────────────────
   FILTROS
────────────────────────────────────────────────────── */
.csl-filter-card {
    background: #fff;
    border-radius: 1.1rem;
    box-shadow: 0 4px 24px rgba(66,25,68,.08), 0 1px 4px rgba(0,0,0,.04);
    padding: 1.5rem;
}
.csl-filter-label {
    display: block;
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #6b7280;
    margin-bottom: .5rem;
}
.csl-select-wrapper {
    position: relative;
}
.csl-select {
    width: 100%;
    appearance: none;
    -webkit-appearance: none;
    border: 1.5px solid #e5e7eb;
    border-radius: .75rem;
    padding: .65rem 2.5rem .65rem 1rem;
    font-size: .9rem;
    font-weight: 600;
    color: #1f2937;
    background: #f9fafb;
    cursor: pointer;
    transition: border-color .2s, box-shadow .2s;
    font-family: inherit;
}
.csl-select:focus {
    outline: none;
    border-color: #421944;
    box-shadow: 0 0 0 3px rgba(66,25,68,.12);
    background: #fff;
}
.csl-select-chevron {
    position: absolute;
    right: .9rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: .8rem;
    color: #6b7280;
    pointer-events: none;
}

/* Segmented control */
.csl-segmented {
    display: inline-flex;
    background: #f3f4f6;
    border-radius: .75rem;
    padding: .25rem;
    gap: .2rem;
    width: 100%;
}
.csl-seg-btn {
    flex: 1;
    border: none;
    background: transparent;
    border-radius: .55rem;
    padding: .5rem .75rem;
    font-size: .82rem;
    font-weight: 600;
    color: #6b7280;
    cursor: pointer;
    transition: all .18s;
    font-family: inherit;
    white-space: nowrap;
}
.csl-seg-btn:hover { color: #421944; }
.csl-seg-btn.active {
    background: #fff;
    color: #421944;
    box-shadow: 0 2px 8px rgba(66,25,68,.14), 0 1px 3px rgba(0,0,0,.08);
    font-weight: 700;
}

/* ──────────────────────────────────────────────────────
   ESTADO VAZIO
────────────────────────────────────────────────────── */
.csl-empty-state {
    text-align: center;
    padding: 4rem 1.5rem;
    background: #fff;
    border-radius: 1.1rem;
    box-shadow: 0 2px 12px rgba(0,0,0,.04);
}
.csl-empty-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f3eaf4, #e8d5f0);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.25rem;
    font-size: 1.8rem;
    color: #421944;
}
.csl-empty-icon--muted {
    background: #f3f4f6;
    color: #9ca3af;
}
.csl-empty-state h5 {
    font-weight: 700;
    color: #1f2937;
    margin-bottom: .5rem;
}
.csl-empty-state p {
    color: #6b7280;
    font-size: .9rem;
    max-width: 360px;
    margin: 0 auto;
}

/* ──────────────────────────────────────────────────────
   BARRA DE RESUMO
────────────────────────────────────────────────────── */
/* ──────────────────────────────────────────────────────
   STAT CARDS
────────────────────────────────────────────────────── */
.csl-stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}
.csl-stat-card {
    position: relative;
    overflow: hidden;
    background: linear-gradient(180deg, #fff 0%, #fbfbfc 100%);
    border-radius: 1rem;
    box-shadow: 0 2px 12px rgba(66,25,68,.07), 0 1px 3px rgba(0,0,0,.04);
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    border: 1px solid #ece7ee;
    transition: box-shadow .2s ease, transform .15s ease, border-color .2s ease;
    min-width: 0;
}
.csl-stat-card::before {
    content: "";
    position: absolute;
    inset: 0 auto auto 0;
    width: 100%;
    height: 3px;
    background: var(--stat-accent, #421944);
    opacity: .95;
}
.csl-stat-card--purple {
    --stat-accent: #7b2d6f;
    --stat-soft: #f5e9f4;
}
.csl-stat-card--blue {
    --stat-accent: #0369a1;
    --stat-soft: #e0f2fe;
}
.csl-stat-card--green {
    --stat-accent: #0f766e;
    --stat-soft: #d7f3ef;
}
.csl-stat-card--orange {
    --stat-accent: #b45309;
    --stat-soft: #fef3c7;
}
.csl-stat-card:hover {
    box-shadow: 0 8px 28px rgba(66,25,68,.12);
    transform: translateY(-2px);
    border-color: rgba(66,25,68,.12);
}
.csl-stat-icon {
    width: 2.75rem;
    height: 2.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    line-height: 1;
    flex-shrink: 0;
    background: var(--stat-soft, #f3f4f6);
    color: var(--stat-accent, #421944);
    border-radius: .75rem;
}
.csl-stat-icon--purple { --stat-accent: #7b2d6f; --stat-soft: #f5e9f4; }
.csl-stat-icon--blue   { --stat-accent: #0369a1; --stat-soft: #e0f2fe; }
.csl-stat-icon--green  { --stat-accent: #0f766e; --stat-soft: #d7f3ef; }
.csl-stat-icon--orange { --stat-accent: #b45309; --stat-soft: #fef3c7; }
.csl-stat-body {
    min-width: 0;
    flex: 1;
}
.csl-stat-value {
    font-size: clamp(1.35rem, 1.1vw + 1rem, 1.7rem);
    font-weight: 800;
    color: #111827;
    line-height: 1.1;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.csl-stat-value--sm {
    font-size: 1rem;
    line-height: 1.2;
    white-space: normal;
}
.csl-stat-label {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #9ca3af;
    font-weight: 600;
    margin-top: .25rem;
    white-space: normal;
    overflow: visible;
    text-overflow: clip;
    line-height: 1.25;
}
@media (max-width: 900px) {
    .csl-stats-row { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 480px) {
    .csl-stats-row { grid-template-columns: 1fr; }
    .csl-stat-card {
        padding: 1rem 1.25rem;
    }
    .csl-stat-value {
        font-size: 1.35rem;
    }
}

/* ──────────────────────────────────────────────────────
   GRUPO
────────────────────────────────────────────────────── */
.csl-group__header {
    display: flex;
    align-items: center;
    gap: .9rem;
}
.csl-group__name {
    font-size: 1.15rem;
    font-weight: 800;
    color: #1f2937;
    margin: 0;
    line-height: 1.2;
}
.csl-group__meta {
    font-size: .78rem;
    color: #9ca3af;
    font-weight: 600;
}
.csl-group__badge {
    background: #f3eaf4;
    color: #421944;
    font-size: .78rem;
    font-weight: 700;
    border-radius: 2rem;
    padding: .3rem .9rem;
    white-space: nowrap;
}

/* ──────────────────────────────────────────────────────
   TEMPLATE CARD
────────────────────────────────────────────────────── */
.csl-template-card {
    background: #fff;
    border-radius: 1.1rem;
    box-shadow: 0 4px 24px rgba(66,25,68,.07), 0 1px 4px rgba(0,0,0,.04);
    overflow: hidden;
    border: 1px solid #f0eaf4;
    transition: box-shadow .2s;
}
.csl-template-card:hover {
    box-shadow: 0 8px 32px rgba(66,25,68,.12), 0 2px 8px rgba(0,0,0,.06);
}
.csl-template-card__header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1.5rem;
    background: linear-gradient(to bottom, #faf7fb, #fff);
    border-bottom: 1px solid #f0eaf4;
    flex-wrap: wrap;
}
.csl-template-card__info {
    flex: 1;
    min-width: 0;
}
.csl-model-badge {
    display: inline-flex;
    align-items: center;
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #421944;
    background: #f3eaf4;
    border: 1px solid #d9bfdc;
    border-radius: 2rem;
    padding: .2rem .65rem;
    margin-bottom: .5rem;
}
.csl-template-card__name {
    font-size: 1.05rem;
    font-weight: 700;
    color: #1f2937;
    margin: 0 0 .5rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 580px;
}
.csl-template-card__stats {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem;
    font-size: .8rem;
    color: #6b7280;
}
.csl-dot {
    display: inline-block;
    width: 3px;
    height: 3px;
    border-radius: 50%;
    background: #d1d5db;
}
.csl-template-card__body {
    padding: 1.5rem;
}

/* Média geral block */
.csl-media-block {
    text-align: center;
    padding: .85rem 1.25rem;
    border-radius: .85rem;
    min-width: 120px;
    flex-shrink: 0;
}
.csl-media-block__value {
    font-size: 2rem;
    font-weight: 900;
    line-height: 1;
}
.csl-media-block__label {
    font-size: .68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    opacity: .7;
    margin-top: .2rem;
}
.csl-media-block__sub {
    font-size: .68rem;
    opacity: .55;
    margin-top: .15rem;
}
.csl-media--green  { background: #dcfce7; color: #16a34a; }
.csl-media--yellow { background: #fef9c3; color: #ca8a04; }
.csl-media--red    { background: #fee2e2; color: #dc2626; }
.csl-media--muted  { background: #f3f4f6; color: #9ca3af; }

/* ──────────────────────────────────────────────────────
   CARDS DE QUESTÕES (via JS)
────────────────────────────────────────────────────── */
[data-cards-questoes] .row.g-3 > .col-md-6:only-child,
[data-cards-questoes] .row.g-3 > .col-md-6:nth-child(odd):nth-last-child(1) {
    flex: 0 0 100%;
    max-width: 100%;
}
[data-cards-questoes] .row > [class*="col-"],
[data-cards-questoes] .question-body {
    min-width: 0;
}
/* Loading state */
body.csl-loading * { cursor: wait !important; }

/* ──────────────────────────────────────────────────────
   MODAL DE RESPOSTAS DE TEXTO
────────────────────────────────────────────────────── */
.csl-modal-content {
    border-radius: 1rem;
    overflow: hidden;
}
.csl-modal-header {
    background: #421944;
    color: #fff;
    border: none;
}
.csl-modal-list {
    max-height: 60vh;
    overflow: auto;
}

@media (max-width: 576px) {
    .csl-summary-bar { flex-direction: column; }
    .csl-summary-divider { display: none; }
    .csl-template-card__header { flex-direction: column; }
    [data-cards-questoes] .question-header {
        flex-direction: column;
        align-items: flex-start;
        gap: .5rem;
    }
    [data-cards-questoes] .question-controls,
    [data-cards-questoes] .question-controls select {
        width: 100%;
        max-width: none;
    }
}
@media (max-width: 768px) {
    .csl-hero { padding-bottom: 0; }
    .csl-summary-bar {
        grid-template-columns: 1fr 1fr;
    }
    .csl-summary-item--wide {
        grid-column: 1 / -1;
        border-right: none;
        border-bottom: 1px solid #f0f0f4;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@vite(['resources/js/avaliacoes-distribuicao-charts.js'])
<script>
(function () {
    const form       = document.getElementById('filtros-form');
    const selEvento  = document.getElementById('evento_id');
    const selAgrup   = document.getElementById('agrupamento');
    const btnPdf     = document.getElementById('btn-download-pdf');
    const btnDocx    = document.getElementById('btn-download-docx');
    const baseUrlPdf = '{{ route('avaliacoes-consolidadas.pdf') }}';

    // ── Feedback visual de loading ao submeter ─────────────
    function submitComLoading() {
        document.body.classList.add('csl-loading');
        // Marca o segmented btn imediatamente
        form.submit();
    }

    // ── Segmented control ──────────────────────────────────
    document.querySelectorAll('.csl-seg-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.csl-seg-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            selAgrup.value = btn.dataset.value;
            atualizarPdf();
            submitComLoading();
        });
    });

    // ── Auto-submit no select de evento ───────────────────
    selEvento.addEventListener('change', function () {
        atualizarPdf();
        submitComLoading();
    });

    // ── Atualizar href do PDF sincronizado ─────────────────
    function atualizarPdf() {
        const query = '?evento_id=' + selEvento.value + '&agrupamento=' + selAgrup.value;
        if (btnPdf) btnPdf.href = baseUrlPdf + query;
        if (btnDocx) btnDocx.href = baseUrlPdf + query + '&formato=docx';
    }

    // Inicializa PDF href
    atualizarPdf();
})();
</script>
@endpush
