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

  .ev-chip {
    display: inline-block;
    padding: .35rem .65rem;
    border-radius: 999px;
    border: 1px solid #dee2e6;
    font-size: .85rem;
  }

  .program-card {
    border: 1px solid #ececec;
    border-radius: .9rem;
    padding: 1rem;
    background: #fff;
  }

  .program-time {
    font-weight: 800;
    font-size: .95rem;
    color: #6c757d;
    letter-spacing: .3px;
  }

  .chip {
    border: 1px solid #e6e6e6;
    border-radius: 999px;
    padding: .2rem .55rem;
    font-size: .8rem;
  }
</style>

@php
$ini = \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i');
$dia = \Carbon\Carbon::parse($atividade->dia)
->locale('pt_BR')->translatedFormat('l, d \\d\\e F \\d\\e Y');
@endphp
<div class="container py-4">

  {{-- Cabeçalho do momento --}}
  <div class="d-flex justify-content-between align-items-start mb-4">
    <x-header-atividade :atividade="$atividade" />
    <div class="d-flex flex-wrap gap-2 mb-3">
      @if($podeImportar)
      <a href="{{ route('atividades.presencas.import', $atividade) }}" class="btn btn-engaja btn-sm">
        Importar presenças
      </a>
      @endif

      @can('presenca.abrir')
      <form action="{{ route('atividades.presenca.toggle', $atividade) }}" method="POST" class="d-inline">
        @csrf @method('PATCH')
        <button class="btn {{ $atividade->presenca_ativa ? 'btn-danger' : 'btn-success' }} btn-sm">
          {{ $atividade->presenca_ativa ? 'Fechar presença' : 'Abrir presença' }}
        </button>
      </form>
      @endcan

      @auth
      <form action="{{ route('atividades.presenca.checkin', $atividade) }}" method="POST" class="d-inline">
        @csrf
        <button class="btn btn-primary btn-sm" {{ $atividade->presenca_ativa ? '' : 'disabled' }}>
          Confirmar minha presença
        </button>
      </form>
      @else
      @if($atividade->presenca_ativa)
      <a class="btn btn-primary btn-sm" href="{{ route('presenca.confirmar', $atividade) }}">
        Confirmar presença
      </a>
      @endif
      @endauth
    </div>
  </div>

  @auth
  {{-- QR Code de presença --}}
  <div class="mb-4">
    <h2 class="h6 fw-bold mb-2">Confirmação de presença (QR)</h2>
    <div class="d-flex align-items-center gap-3 flex-wrap">
      <div class="p-2 border rounded bg-white">
        <img src="data:image/png;base64, {!! base64_encode(
          QrCode::format('png')
              ->style('round')
              ->color(129,18,131)
              ->eye('circle')
              ->eyeColor(0, 0,156,209,0,156,209)
              ->eyeColor(1, 44,181,124,44,181,124)
              ->eyeColor(2, 192,12,142,192,12,142)
              ->size(200)
              ->margin(0)
              ->merge(public_path('/images/favicon-eja.png'), 0.3, true)
              ->errorCorrection('H')
              ->generate(route('presenca.confirmar', $atividade))
        ) !!}" alt="QR Code">
      </div>

      {{-- Botão ativar/desativar presença --}}
      {{--
      <form action="{{ route('atividades.toggle-presenca', $atividade) }}" method="POST">
      @csrf @method('PATCH')
      <button type="submit" class="btn {{ $atividade->presenca_ativa ? 'btn-danger' : 'btn-success' }}">
        {{ $atividade->presenca_ativa ? 'Desativar presença' : 'Ativar presença' }}
      </button>
      </form>
      --}}
    </div>
  </div>
  @endauth

  @can('presenca.abrir')
  {{-- Lista de presenças --}}
  <div class="mb-4">
    <h2 class="h6 fw-bold mb-2">Participantes com presença registrada</h2>

    @php
    $lista = $atividade->presencas()->with([
    'inscricao.participante.user:id,name,email',
    'inscricao.participante.municipio.estado:id,nome,sigla'
    ])->orderByDesc('id')->paginate(25);
    @endphp

    @if($lista->count() === 0)
    <div class="ev-card p-3 text-muted">Nenhuma presença registrada para este momento.</div>
    @else
    <div class="table-responsive">
      <table class="table table-sm table-bordered align-middle bg-white">
        <thead class="table-light">
          <tr>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Município</th>
            <th style="min-width:140px;">Status</th>
            <!-- <th>Justificativa</th> -->
            <th style="min-width:140px;">Marcado em</th>
          </tr>
        </thead>
        <tbody>
          @foreach($lista as $pr)
          @php
          $p = $pr->inscricao->participante ?? null;
          $u = $p?->user;
          $m = $p?->municipio;
          $uf = $m?->estado?->sigla;
          $munLabel = $m ? ($m->nome . ($uf ? " - $uf" : "")) : '—';
          $status = $pr->status_participacao ?? $pr->status ?? null;
          @endphp
          <tr>
            <td>{{ $u->name ?? '—' }}</td>
            <td>{{ $u->email ?? '—' }}</td>
            <td>{{ $munLabel }}</td>
            <td>
              @switch($status)
              @case('presente') <span class="badge bg-success">Presente</span> @break
              @case('ausente') <span class="badge bg-secondary">Ausente</span> @break
              @case('justificado') <span class="badge bg-warning text-dark">Justificado</span> @break
              @default <span class="badge bg-light text-muted">—</span>
              @endswitch
            </td>
            <!-- <td>{{ $pr->justificativa ?? '—' }}</td> -->
            <td>{{ optional($pr->updated_at ?? $pr->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="d-flex justify-content-between align-items-center">
      <div class="small text-muted">Exibindo {{ $lista->count() }} de {{ $lista->total() }}</div>
      {{ $lista->links() }}
    </div>
    @endif
  </div>
  @endcan

</div>
@endsection
