@extends('layouts.app')

@section('content')
@php
  use Carbon\Carbon;
@endphp
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
      <h1 class="h4 mb-1 fw-bold">Inscrever participantes existentes</h1>
      <div class="text-muted small">
        Ação pedagógica: <strong>{{ $evento->nome }}</strong>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('inscricoes.import', $evento) }}" class="btn btn-sm btn-outline-primary">Importar planilha</a>
      <a href="{{ route('eventos.show', $evento) }}" class="btn btn-sm btn-outline-secondary">Voltar</a>
    </div>
  </div>

  <div class="card shadow-sm mb-3">
    <div class="card-body">
      @if($atividadeSelecionada)
        @php
          $diaSel = Carbon::parse($atividadeSelecionada->dia)->translatedFormat('d/m/Y');
          $horaSel = $atividadeSelecionada->hora_inicio ? Carbon::parse($atividadeSelecionada->hora_inicio)->format('H:i') : null;
        @endphp
        <div class="mt-3 small text-muted">
          Momento selecionado: <strong>{{ $atividadeSelecionada->descricao ?: 'Momento' }}</strong> - {{ $diaSel }}{{ $horaSel ? ' às '.$horaSel : '' }}
        </div>
      @else
        <div class="mt-3 alert alert-info mb-0">
          Escolha um momento para habilitar o cadastro dos participantes selecionados.
        </div>
        <br>
      @endif
      <form method="GET" action="{{ route('inscricoes.selecionar', $evento) }}">
        <div class="row g-3 mb-2">
          <div class="col-12">
            <label class="form-label mb-1">Selecione o momento <span class="text-danger">*</span></label>
            <select name="atividade_id" class="form-select form-select-sm">
              <option value="">Selecione...</option>
              @foreach($atividades as $at)
                @php
                  $dia = Carbon::parse($at->dia)->format('d/m/Y');
                  $hora = $at->hora_inicio ? Carbon::parse($at->hora_inicio)->format('H:i') : null;
                  $label = trim(($at->descricao ?: 'Momento') . ' - ' . $dia . ($hora ? ' ' . $hora : ''));
                @endphp
                <option value="{{ $at->id }}" @selected((string)$atividadeId === (string)$at->id)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="row g-3 align-items-end">
          <div class="col-xl-3 col-md-4">
            <label class="form-label mb-1">Buscar</label>
            <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Nome, email, CPF...">
          </div>

          <div class="col-xl-2 col-md-4">
            <label class="form-label mb-1">Município</label>
            <select name="municipio_id" class="form-select form-select-sm">
              <option value="">Todos</option>
              @foreach($municipios as $m)
                <option value="{{ $m->id }}" @selected((string)$municipioId === (string)$m->id)>
                  {{ $m->nome_com_estado ?? ($m->nome . ($m->estado?->sigla ? ' - '.$m->estado?->sigla : '')) }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-xl-2 col-md-4">
            <label class="form-label mb-1">Tag</label>
            <select name="tag" class="form-select form-select-sm">
              <option value="">Todas</option>
              @foreach($participanteTags as $tag)
                <option value="{{ $tag }}" @selected($tagSelecionada === $tag)>{{ $tag }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-xl-1 col-md-4">
            <label class="form-label mb-1">Por página</label>
            <select name="per_page" class="form-select form-select-sm">
              @foreach([25,50,100,200] as $pp)
                <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
              @endforeach
            </select>
          </div>

          <div class="col-xl-4 col-md-4">
            <label class="form-label mb-1">Disponibilidade</label>
            <div class="form-check form-switch">
              <input type="hidden" name="apenas_disponiveis" value="0">
              <input class="form-check-input" type="checkbox" role="switch" id="apenasDisponiveisSwitch"
                name="apenas_disponiveis" value="1" @checked($apenasDisponiveis) @disabled(!$atividadeId)>
              <label class="form-check-label small" for="apenasDisponiveisSwitch">
                Mostrar apenas quem não está no momento
              </label>
            </div>
          </div>

          <div class="col-12 d-flex gap-2">
            <button class="btn btn-sm btn-primary">Filtrar</button>
            <a href="{{ route('inscricoes.selecionar', $evento) }}" class="btn btn-sm btn-outline-secondary">Limpar</a>
          </div>
        </div>
      </form>
    </div>
  </div>

  <form method="POST" action="{{ route('inscricoes.selecionar.store', $evento) }}" class="card shadow-sm">
    @csrf
    <input type="hidden" name="atividade_id" value="{{ $atividadeId }}">
    <input type="hidden" name="q" value="{{ $search }}">
    <input type="hidden" name="municipio_id" value="{{ $municipioId }}">
    <input type="hidden" name="tag" value="{{ $tagSelecionada }}">
    <input type="hidden" name="per_page" value="{{ $perPage }}">
    <input type="hidden" name="apenas_disponiveis" value="{{ $apenasDisponiveis ? 1 : 0 }}">

    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <strong>{{ $participantes->total() }}</strong> participantes encontrados
        <span class="text-muted small ms-1">
          Página {{ $participantes->currentPage() }} de {{ $participantes->lastPage() }}
        </span>
      </div>
      <div class="d-flex gap-2 align-items-center">
        <div class="form-check mb-0">
          <input class="form-check-input" type="checkbox" id="selectAll">
          <label class="form-check-label small" for="selectAll">Selecionar página</label>
        </div>
        <button type="submit" class="btn btn-engaja btn-sm" @disabled(!$atividadeId || $participantes->isEmpty())>
          Inscrever selecionados
        </button>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-sm align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width:40px;"></th>
            <th>Nome</th>
            <th>Email</th>
            <th>CPF</th>
            <th>Município</th>
            <th>Tag</th>
            <th>Status no evento</th>
          </tr>
        </thead>
        <tbody>
          @forelse($participantes as $participante)
            @php
              $user = $participante->user;
              $municipio = $participante->municipio;
              $jaNoMomento = in_array($participante->id, $inscritosNaAtividade, true);
              $jaNoEvento = in_array($participante->id, $inscritosNoEvento, true);
            @endphp
            <tr>
              <td>
                <div class="form-check mb-0">
                  <input class="form-check-input participant-checkbox" type="checkbox"
                    name="participantes[]" value="{{ $participante->id }}"
                    @disabled($jaNoMomento)>
                </div>
              </td>
              <td>{{ $user->name ?? '-' }}</td>
              <td>{{ $user->email ?? '-' }}</td>
              <td>{{ $participante->cpf ?? '-' }}</td>
              <td>{{ $municipio?->nome_com_estado ?? '-' }}</td>
              <td>{{ $participante->tag ?? '-' }}</td>
              <td>
                @if($jaNoMomento)
                  <span class="badge bg-success-subtle text-success">Já inscrito neste momento</span>
                @elseif($jaNoEvento)
                  <span class="badge bg-primary-subtle text-primary">Já inscrito no evento</span>
                @else
                  <span class="badge bg-secondary-subtle text-secondary">Disponível</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="7" class="text-center text-muted py-4">Nenhum participante encontrado com os filtros selecionados.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div class="small text-muted">
        Exibindo {{ $participantes->count() }} registros nesta página
      </div>
      @error('participantes')
        <div class="text-danger small">{{ $message }}</div>
      @enderror
      <div>
        {{ $participantes->links() }}
      </div>
    </div>
  </form>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('selectAll');
    if (!selectAll) return;
    selectAll.addEventListener('change', () => {
      document.querySelectorAll('.participant-checkbox:not(:disabled)').forEach((checkbox) => {
        checkbox.checked = selectAll.checked;
      });
    });
  });
</script>
@endsection
