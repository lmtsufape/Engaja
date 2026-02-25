@extends('layouts.app')

@section('content')
<style>
  :root {
    --engaja: #421944;
  }

  .ev-card {
    border-radius: .8rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, .06);
  }

  .ev-icon {
    width: 48px;
    height: 48px;
    border-radius: .75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #eef2e6;
  }

  .ev-chip {
    display: inline-block;
    padding: .35rem .65rem;
    border-radius: 999px;
    border: 1px solid #dee2e6;
    font-size: .85rem;
  }

  .nav-day .nav-link {
    border-radius: 999px;
  }

  .nav-day .nav-link.active {
    background: var(--engaja);
    color: #fff;
  }

  .program-sec {
    position: relative;
  }

  .day-tabs {
    overflow: auto;
    white-space: nowrap;
    gap: .5rem;
  }

  .day-tabs .nav-link {
    border-radius: 999px;
    padding: .4rem .9rem;
    font-weight: 600;
    border: 1px solid #e7e7e7;
    color: #333;
  }

  .day-tabs .nav-link.active {
    background: var(--engaja);
    color: #fff;
    border-color: var(--engaja);
  }

  .timeline {
    position: relative;
    padding-left: 2.25rem;
  }

  .timeline::before {
    content: "";
    position: absolute;
    left: 1rem;
    top: .25rem;
    bottom: .25rem;
    width: 2px;
    background: linear-gradient(#ececec, #d9d9d9);
  }

  .t-item {
    position: relative;
    margin-bottom: 1rem;
  }

  .t-dot {
    display: none;
  }

  .program-card {
    border: 1px solid #ececec;
    border-radius: .9rem;
    padding: 1rem;
    transition: transform .15s ease, box-shadow .15s ease;
    background: #fff;
  }

  .program-card:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .06);
  }

  .program-time {
    font-weight: 800;
    font-size: .95rem;
    color: #6c757d;
    letter-spacing: .3px;
  }

  .program-title {
    font-weight: 700;
    margin: .15rem 0 .35rem;
  }

  .program-meta {
    font-size: .85rem;
    color: #6c757d;
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
  }

  .chip {
    border: 1px solid #e6e6e6;
    border-radius: 999px;
    padding: .2rem .55rem;
    font-size: .8rem;
  }

  .actions .btn {
    padding: .25rem .5rem;
  }

  .empty-state {
    border: 1px dashed #d8d8d8;
    border-radius: .9rem;
    padding: 1.25rem;
    text-align: center;
    color: #6c757d;
  }
</style>

<div class="container">

  {{-- Cabeçalho --}}
  <div class="row g-4 align-items-center mb-4">
    <div class="col-md-5">
      <div class="ev-card bg-light p-4 text-center">
        <img src="{{ $evento->imagem ? asset('storage/' . $evento->imagem) : asset('images/logo-aeb.png') }}"
          class="img-fluid rounded" alt="Capa do evento">
      </div>
    </div>
    @php
    $participanteId = optional(auth()->user()?->participante)->id;
    $jaInscrito = false;
    if ($participanteId) {
    $jaInscrito = \Illuminate\Support\Facades\DB::table('inscricaos')
    ->where('evento_id', $evento->id)
    ->where('participante_id', $participanteId)
    ->whereNull('deleted_at')
    ->exists();
    }
    @endphp
    <div class="col-md-7">
      <h1 class="h3 fw-bold text-engaja mb-2">{{ $evento->nome }}</h1>

      @php
        $dataInicio = $evento->data_inicio ? \Carbon\Carbon::parse($evento->data_inicio) : null;
        $dataFim = $evento->data_fim ? \Carbon\Carbon::parse($evento->data_fim) : null;
        $mesmoDia = $dataInicio && $dataFim && $dataInicio->isSameDay($dataFim);
      @endphp

      <ul class="list-unstyled mb-3">
        @if($dataInicio || $dataFim)
        <li class="mb-1">
          📅
          {{ $dataInicio ? $dataInicio->locale('pt_BR')->translatedFormat('l, d \d\e F \d\e Y') : 'Início não informado' }}
          @if($dataFim && !$mesmoDia)
          <br><small class="text-muted">Até {{ $dataFim->locale('pt_BR')->translatedFormat('l, d \d\e F \d\e Y') }}</small>
          @endif
        </li>
        @endif

        @if(!empty($evento->local))
        <li class="mb-1">📍 {{ $evento->local }}</li>
        @endif

        @if($evento->modalidade)
        <li class="mb-1">🛰️ {{ ucfirst($evento->modalidade) }}</li>
        @endif

        @if($evento->user?->name)
        <li class="mb-1">👤 Organizado por: {{ $evento->user->name }}</li>
        @endif
      </ul>

      <div class="d-flex gap-2 flex-wrap">
        @if($evento->link)
        <a href="{{ $evento->link }}" target="_blank" class="btn btn-outline-secondary">Acessar link</a>
        @endif

        @hasanyrole('administrador|gerente|eq_pedagogica')
        <div class="actions d-flex gap-2 flex-shrink-0 align-items-center">
        <a href="{{ route('inscricoes.selecionar', $evento)}}" class="btn btn-engaja">Selecionar participantes</a>
        <a href="{{ route('inscricoes.import', $evento)}}" class="btn btn-outline-primary">Importar planilha</a>
        @endhasanyrole

        @can('participante.ver')
        <a href="{{ route('inscricoes.inscritos', $evento) }}" class="btn btn-outline-primary">
          Ver inscritos
        </a>
        @endcan

        @role('administrador|gerente')
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal"
          data-bs-target="#modalRelatoriosEvento">
          Relatórios
        </button>
        @endrole

        @can('update', $evento)
        <a href="{{ route('eventos.edit', $evento) }}" class="btn btn-outline-secondary">Editar</a>
        @role('administrador')
        <form action="{{ route('eventos.destroy', $evento) }}" method="POST"
          class="d-flex m-0 p-0" data-confirm="Tem certeza que deseja excluir esta ação pedagógica?">
          @csrf @method('DELETE')
          <button class="btn btn-outline-danger">Excluir</button>
        </form>
        @endrole
        </div>
        @endcan
      </div>
    </div>
  </div>

  {{-- Chips --}}
  @php
  $totalInscritos = $evento->participantes()->wherePivotNull('deleted_at')->count();
  @endphp
  <div class="mb-4">
    <div class="d-flex flex-wrap gap-2">
      @if($evento->eixo?->nome)
      <span class="ev-chip">Eixo: <strong class="ms-1">{{ $evento->eixo->nome }}</strong></span>
      @endif
      @if($evento->tipo)
      <span class="ev-chip">Tipo: <strong class="ms-1">{{ $evento->tipo }}</strong></span>
      @endif
      @if($dataInicio || $dataFim)
      @php
        $chipInicio = $dataInicio ? $dataInicio->format('d/m/Y') : null;
        $chipFim = $dataFim && !$mesmoDia ? $dataFim->format('d/m/Y') : null;
      @endphp
      <span class="ev-chip">
        Período:
        <strong class="ms-1">{{ $chipInicio ?? '—' }}</strong>
        @if($chipFim)
        <span class="text-muted px-1">até</span>
        <strong>{{ $chipFim }}</strong>
        @endif
      </span>
      @endif
      @if($evento->modalidade)
      <span class="ev-chip">Modalidade: <strong class="ms-1">{{ $evento->modalidade }}</strong></span>
      @endif
      <span class="ev-chip">Inscritos: <strong class="ms-1">{{ $totalInscritos }}</strong></span>
    </div>
  </div>

  {{-- Descrição / Objetivo --}}
  @if($evento->resumo)
  <div class="mb-4">
    <h2 class="h5 fw-bold mb-2">Descrição</h2>
    <div class="ev-card p-3">
      <p class="mb-0">{{ $evento->resumo }}</p>
    </div>
  </div>
  @endif

  @if($evento->objetivo)
  <div class="mb-4">
    <h2 class="h5 fw-bold mb-2">Objetivos</h2>
    <div class="ev-card p-3">
      <p class="mb-0">{{ $evento->objetivo }}</p>
    </div>
  </div>
  @endif

  {{-- Programação --}}
  @php
  use Carbon\Carbon;
  $porDia = $evento->atividades
  ->sortBy(fn($a) => Carbon::parse($a->dia)->toDateString() . ' ' . Carbon::parse($a->hora_inicio)->format('H:i'))
  ->groupBy(fn($a) => Carbon::parse($a->dia)->toDateString());
  $dias = $porDia->keys()->values();
  @endphp

  <div class="program-sec mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="h5 fw-bold mb-0">Programação</h2>

      <div class="d-flex gap-2">
        @hasanyrole('administrador|gerente|eq_pedagogica')
        {{-- Botão Interceptado --}}
        <button type="button"
                class="btn btn-engaja btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#modalChecklistPreAcao">
            + Novo momento
        </button>
        @endhasanyrole

        <a href="{{ route('eventos.atividades.index', $evento) }}" class="btn btn-outline-secondary btn-sm">
          Ver todos
        </a>
      </div>
    </div>

    @if($porDia->isNotEmpty())
    <ul class="nav day-tabs mb-3" role="tablist">
      @foreach($dias as $i => $dia)
      @php
      $label = \Carbon\Carbon::parse($dia)
      ->locale('pt_BR')
      ->translatedFormat('D\, j M \d\e Y'); // ex.: "seg • 9 de set"
      @endphp
      <li class="nav-item" role="presentation">
        <button class="nav-link {{ $i === 0 ? 'active' : '' }}" id="tab-{{ $i }}" data-bs-toggle="pill"
          data-bs-target="#pane-{{ $i }}" type="button" role="tab" aria-controls="pane-{{ $i }}"
          aria-selected="{{ $i === 0 ? 'true' : 'false' }}">
          {{ $label }}
        </button>
      </li>
      @endforeach
    </ul>
    @endif

    <div class="tab-content">
      @if($porDia->isEmpty())
      <div class="empty-state">
        <div class="mb-1" style="font-size:1.6rem">🗓️</div>
        Nenhum momento cadastrado ainda.
      </div>
      @else
      @foreach($dias as $i => $dia)
      @php $lista = $porDia[$dia]; @endphp

      <div class="tab-pane fade {{ $i === 0 ? 'show active' : '' }}" id="pane-{{ $i }}" role="tabpanel"
        aria-labelledby="tab-{{ $i }}">
        <div class="timeline">
          @foreach($lista as $at)
          @php

          $ini = \Carbon\Carbon::parse($at->hora_inicio);
          $fimObj = !empty($at->hora_fim) ? \Carbon\Carbon::parse($at->hora_fim) : null;

            if ($fimObj && $fimObj->lessThanOrEqualTo($ini)) {
            $fimObj->addDay();
            }

            $iniStr = $ini->format('H:i');
            $fimStr = $fimObj ? $fimObj->format('H:i') : null;

            $chLabel = null;
            if ($fimObj) {
            $mins = $ini->diffInMinutes($fimObj, false);
            if ($mins < 0) { $mins +=24*60; } // segurança extra
              $h=intdiv($mins, 60);
              $m=$mins % 60;
              $chLabel=$h> 0 ? ($h.'h'.($m ? ' '.$m.'min' : '')) : ($m.'min');
              }

              $momento = trim($at->descricao ?? '') !== '' ? $at->descricao : 'Momento';
              $local = $at->local ?? null;
              $municipio = $at->municipios->isNotEmpty()
                ? $at->municipios->map(fn($m) => $m->nome_com_estado ?? $m->nome)->join(', ')
                : null;
              $publicoEsperado = $at->publico_esperado;
              $cargaHoraria = $at->carga_horaria;
              $cargaLabel = !is_null($cargaHoraria) ? number_format($cargaHoraria, 0, ',', '.') . 'h' : null;
              @endphp

              <div class="t-item">
                <span class="t-dot"></span>
                <div class="program-card">
                  <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                      <div class="program-time">{{ $iniStr }}{{ $fimStr ? ' – ' . $fimStr : '' }}</div>
                      <div class="program-title">{{ $momento }}</div>

                      @if($local || $municipio || $chLabel || $publicoEsperado || $cargaLabel)
                      <div class="program-meta">
                        @if($municipio) <span class="chip">Município: {{ $municipio }}</span> @endif
                        @if($local) <span class="chip">Local: {{ $local }}</span> @endif
                        @if($chLabel) <span class="chip">Duração: {{ $chLabel }}</span> @endif
                        @if($publicoEsperado) <span class="chip">Público esperado: {{ number_format($publicoEsperado, 0, ',', '.') }} pessoas</span> @endif
                        @if($cargaLabel) <span class="chip">Carga horária: {{ $cargaLabel }}</span> @endif
                      </div>
                      @endif
                    </div>
                    @can('atividade.ver')
                    <div class="d-flex align-items-center gap-4 flex-shrink-0">
                      <a href="{{ $at->avaliacaoAtividade 
                            ? route('avaliacao-atividade.edit',   $at) 
                            : route('avaliacao-atividade.create', $at) }}" 
                        class="btn btn-sm {{ $at->avaliacaoAtividade ? 'btn-warning' : 'btn-outline-warning' }}">
                        {{ $at->avaliacaoAtividade ? '📋 Avaliação ' : '📋 Avaliar' }}
                      </a>
                    <div class="actions d-flex gap-2 flex-shrink-0 align-items-center">
                      <a href="{{ route('atividades.show', $at) }}" class="btn btn-sm btn-outline-primary">
                          Ver
                      </a>
                    @endcan

                    @hasanyrole('administrador|gerente|eq_pedagogica')
                      <a href="{{ route('atividades.edit', $at) }}" class="btn btn-sm btn-outline-secondary">
                          Editar
                      </a>
                    @endhasanyrole

                    @hasanyrole('administrador|gerente')
                      <form action="{{ route('atividades.destroy', $at) }}" method="POST"
                            class="d-inline m-0 p-0"
                            data-confirm="Tem certeza que deseja excluir este momento?">
                          @csrf @method('DELETE')
                          <button class="btn btn-sm btn-outline-danger">Excluir</button>
                      </form>
                    @endhasanyrole

                  </div>

                  </div>
                </div>
              </div>
              @endforeach
        </div>
      </div>
      @endforeach

      @endif
    </div>
  </div>

</div>
@hasanyrole('administrador|gerente')
<div class="modal fade" id="modalRelatoriosEvento" tabindex="-1" aria-labelledby="modalRelatoriosEventoLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title fw-bold" id="modalRelatoriosEventoLabel">Relatórios da ação pedagógica</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-4">
          Apenas participantes com presença confirmada serão considerados.
        </p>
        <div class="row g-3">
          <div class="col-md-6">
            <div class="h-100 border rounded p-3">
              <h6 class="fw-bold mb-1">Participantes únicos</h6>
              <p class="text-muted small mb-3">Consolida todos os participantes que tiveram presença confirmada em
                qualquer momento desta ação.</p>
              <a href="{{ route('eventos.relatorios', ['evento' => $evento, 'tipo' => 'geral']) }}"
                class="btn btn-engaja w-100">
                Baixar XLSX
              </a>
            </div>
          </div>
          <div class="col-md-6">
            <div class="h-100 border rounded p-3">
              <h6 class="fw-bold mb-1">Participantes por momento</h6>
              <p class="text-muted small mb-3">Lista os presentes por momento, com data e horários.</p>
              <a href="{{ route('eventos.relatorios', ['evento' => $evento, 'tipo' => 'momentos']) }}"
                class="btn btn-outline-secondary w-100">
                Baixar XLSX
              </a>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>
@endhasanyrole

{{-- Instância do Modal de Pré-ação --}}
<x-checklist-modal
    id="modalChecklistPreAcao"
    title="Checklist de Planejamento"
    btn-label="Prosseguir para criar momento"
    :items="[
        'Ao planejar cada ação, recorri aos objetivos gerais do projeto, em diálogo com os dados da Leitura do Mundo?',
        'Ao planejar, estabeleci conexão com as outras ações do projeto? (Ex: Cartas para Esperançar, Semear Palavras)',
        'Preparei listas de presença impressas de acordo com os dados a serem inseridos no sistema ENGAJA?',
        'Preparei formulários de avaliação de cada ação de formação, para medir os impactos?',
        'Organizei a lista de materiais necessários e apresentei à coordenação com antecedência?',
        'Organizei a demanda de infraestrutura local com antecedência?',
        'A inscrição do público esperado na formação foi feita?',
        'A informação sobre o dia e horário chegou com antecedência aos públicos participantes?',
        'Os materiais institucionais do projeto para entregar aos participantes estão organizados?',
        'Equipe Pedagógica e Educadores estão com clareza de quem fará o que durante os encontros?',
        'Planejei os momentos de registros audiovisual de cada ação?',
        'Sei como nomear os arquivos e o local onde compartilhar os registros processuais?',
        'Estou de posse de todos os contatos estratégicos em caso de necessidade?'
    ]"
/>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const btnConfirmarPreAcao = document.querySelector('.js-checklist-confirm[data-modal="modalChecklistPreAcao"]');
    
    if (btnConfirmarPreAcao) {
        btnConfirmarPreAcao.addEventListener('click', function () {
            const modalEl = document.getElementById('modalChecklistPreAcao');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal?.hide();
            
            window.location.href = "{{ route('eventos.atividades.create', $evento) }}";
        });
    }
});
</script>
@endpush