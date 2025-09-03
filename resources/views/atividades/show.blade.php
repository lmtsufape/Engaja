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
        <img
          src="{{ $atividade->evento->imagem ? asset('storage/'.$atividade->evento->imagem) : asset('images/engaja-bg.png') }}"
          class="img-fluid rounded" alt="Capa do evento">
      </div>
    </div>

    <div class="col-md-7">
      <h1 class="h3 fw-bold text-engaja mb-2">{{ $atividade->evento->nome }}</h1>

      <ul class="list-unstyled mb-3">
        @if($atividade->evento->data_horario)
        <li class="mb-1">📅
          {{ \Carbon\Carbon::parse($atividade->evento->data_horario)->locale('pt_BR')->translatedFormat('l, d \d\e F \à\s H\hi') }}
        </li>
        @endif

        @if(!empty($atividade->evento->local))
        <li class="mb-1">📍 {{ $atividade->evento->local }}</li>
        @endif

        @if($atividade->evento->modalidade)
        <li class="mb-1">🛰️ {{ ucfirst($atividade->evento->modalidade) }}</li>
        @endif

        @if($atividade->evento->user?->name)
        <li class="mb-1">👤 Organizado por: {{ $atividade->evento->user->name }}</li>
        @endif
      </ul>

      <div class="d-flex gap-2 flex-wrap">
      </div>
    </div>
  </div>


  <div class="mb-4">
    <div class="d-flex flex-wrap gap-2">
      @if($atividade->evento->eixo?->nome)
      <span class="ev-chip">Eixo: <strong class="ms-1">{{ $atividade->evento->eixo->nome }}</strong></span>
      @endif
      @if($atividade->evento->tipo)
      <span class="ev-chip">Tipo: <strong class="ms-1">{{ $atividade->evento->tipo }}</strong></span>
      @endif
      @if(!is_null($atividade->evento->duracao))
      <span class="ev-chip">Duração: <strong class="ms-1">{{ $atividade->evento->duracao }} dias</strong></span>
      @endif
      @if($atividade->evento->modalidade)
      <span class="ev-chip">Modalidade: <strong class="ms-1">{{ $atividade->evento->modalidade }}</strong></span>
      @endif
    </div>
  </div>

  {{-- Descrição / Objetivo --}}
  @if($atividade->evento->resumo)
  <div class="mb-4">
    <h2 class="h5 fw-bold mb-2">Descrição da ação pedagógica</h2>
    <div class="ev-card p-3">
      <p class="mb-0">{{ $atividade->evento->resumo }}</p>
    </div>
  </div>
  @endif

  @if($atividade->evento->objetivo)
  <div class="mb-4">
    <h2 class="h5 fw-bold mb-2">Objetivos da ação pedagógica</h2>
    <div class="ev-card p-3">
      <p class="mb-0">{{ $atividade->evento->objetivo }}</p>
    </div>
  </div>
  @endif



  {{-- TODO --}}
  {{-- Link com formulário --}}
  {{-- QR Code para presença --}}
  <div class="mb-3">
      <h5 class="h5 fw-bold mb-2">Confirmação de presença</h5>
      <img src="data:image/png;base64, {!! base64_encode(
          QrCode::format('png')
              ->style('round')
              ->color(129,18,131)
              ->eye('circle')
              ->eyeColor(0, 0,156,209,0,156,209)
              ->eyeColor(1, 0,190,175,0,190,175)
              ->eyeColor(2, 192,12,142,192,12,142)
              ->size(200)
              ->margin(0)
              ->merge(storage_path('app/public/engaja-qr.png'), 0.3, true)
              ->errorCorrection('H')
              ->generate(route('atividades.show', $atividade->id))
      ) !!}" alt="QR Code">
  </div>

  {{-- TODO --}}
  {{-- Botão ativar/desativar presença --}}
  <form action="/" method="POST" class="mb-3">
      @csrf
      @method('PATCH')
      <button type="submit" class="btn {{ $atividade->presenca_ativa ? 'btn-danger' : 'btn-success' }}">
          {{ $atividade->presenca_ativa ? 'Desativar Presença' : 'Ativar Presença' }}
      </button>
  </form>

  {{-- Lista de participantes --}}
  <h5 class="h6 fw-bold mb-2">Participantes que já confirmaram presença</h5>
  <ul class="list-group">
      @foreach($atividade->presencas as $presenca)
          <li class="list-group-item d-flex justify-content-between align-items-center">
              {{ $presenca->inscricao->participante->user->name }}
              @if($presenca->status === 'justificado')
                  <span class="badge bg-warning text-dark">Justificado</span>
              @else
                  <span class="badge bg-success">Presente</span>
              @endif
          </li>
      @endforeach
  </ul>

</div>
@endsection
