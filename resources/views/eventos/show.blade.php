@extends('layouts.app')

@section('content')
<style>
  .ev-card { border-radius: .8rem; box-shadow: 0 4px 12px rgba(0,0,0,.06); }
  .ev-icon { width:48px; height:48px; border-radius: .75rem; display:flex; align-items:center; justify-content:center; background:#eef2e6; }
  .ev-chip  { display:inline-block; padding:.35rem .65rem; border-radius: 999px; border:1px solid #dee2e6; font-size:.85rem; }
  .nav-day .nav-link { border-radius: 999px; }
  .nav-day .nav-link.active { background:#421944; color:#fff; }
  .program-card { border-radius:.8rem; border:1px solid #e7e7e7; }
  .program-card .time { font-weight:700; color:#6c757d; }
</style>

<div class="container">

  {{-- Cabeçalho --}}
  <div class="row g-4 align-items-center mb-4">
    <div class="col-md-5">
      <div class="ev-card bg-light p-4 text-center">
        <img
          src="{{ $evento->imagem ? asset('storage/'.$evento->imagem) : asset('images/engaja-bg.png') }}"
          class="img-fluid rounded" alt="Capa do evento">
      </div>
    </div>

    <div class="col-md-7">
      <h1 class="h3 fw-bold text-engaja mb-2">{{ $evento->nome }}</h1>

      <ul class="list-unstyled mb-3">
        @if($evento->data_horario)
          <li class="mb-1">📅
            {{ \Carbon\Carbon::parse($evento->data_horario)->translatedFormat('l, d \d\e F \à\s H\hi') }}
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

        {{-- botão de inscrição (ajuste a rota quando tiver o módulo de inscrições) --}}
        <a href="#" class="btn btn-engaja">Inscrever-se</a>

        @can('update', $evento)
          <a href="{{ route('eventos.edit', $evento) }}" class="btn btn-outline-secondary">Editar</a>
        @endcan
        @can('delete', $evento)
          <form action="{{ route('eventos.destroy', $evento) }}" method="POST" onsubmit="return confirm('Excluir este evento?');" class="d-inline">
            @csrf @method('DELETE')
            <button class="btn btn-outline-danger">Excluir</button>
          </form>
        @endcan
      </div>
    </div>
  </div>

  {{-- Chips --}}
  <div class="mb-4">
    <div class="d-flex flex-wrap gap-2">
      @if($evento->eixo?->nome)
        <span class="ev-chip">Eixo: <strong class="ms-1">{{ $evento->eixo->nome }}</strong></span>
      @endif
      @if($evento->tipo)
        <span class="ev-chip">Tipo: <strong class="ms-1">{{ $evento->tipo }}</strong></span>
      @endif
      @if(!is_null($evento->duracao))
        <span class="ev-chip">Duração: <strong class="ms-1">{{ $evento->duracao }} dias</strong></span>
      @endif
      @if($evento->modalidade)
        <span class="ev-chip">Modalidade: <strong class="ms-1">{{ $evento->modalidade }}</strong></span>
      @endif
    </div>
  </div>

  {{-- Descrição / Objetivo --}}
  @if($evento->resumo)
    <div class="mb-4">
      <h2 class="h5 fw-bold mb-2">Descrição do Evento</h2>
      <div class="ev-card p-3">
        <p class="mb-0">{{ $evento->resumo }}</p>
      </div>
    </div>
  @endif

  @if($evento->objetivo)
    <div class="mb-4">
      <h2 class="h5 fw-bold mb-2">Objetivos do Evento</h2>
      <div class="ev-card p-3">
        <p class="mb-0">{{ $evento->objetivo }}</p>
      </div>
    </div>
  @endif
</div>
@endsection
