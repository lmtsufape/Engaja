@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Inscritos — {{ $evento->nome }}</h1>
    <a href="{{ route('inscricoes.import', $evento) }}" class="btn btn-sm btn-outline-primary">Importar inscrições</a>
  </div>

  {{-- Filtros --}}
  <form method="GET" action="{{ route('inscricoes.inscritos', $evento) }}" class="row g-2 align-items-end mb-3">
    <div class="col-lg-4">
      <label class="form-label mb-1">Buscar (nome, e-mail, CPF, telefone)</label>
      <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Digite para buscar...">
    </div>
    <div class="col-lg-3">
      <label class="form-label mb-1">Município</label>
      <select name="municipio_id" class="form-select form-select-sm">
        <option value="">— Todos —</option>
        @foreach($municipios as $m)
          <option value="{{ $m->id }}" @selected((string)$municipioId === (string)$m->id)>
            {{ $m->nome_com_estado }}
          </option>
        @endforeach
      </select>
    </div>
    <div class="col-lg-3">
      <label class="form-label mb-1">Momento</label>
      <select name="atividade_id" class="form-select form-select-sm">
        <option value="">- Todos -</option>
        @foreach($atividades as $at)
          @php
            $dia = \Carbon\Carbon::parse($at->dia)->format('d/m/Y');
            $hora = $at->hora_inicio ? \Carbon\Carbon::parse($at->hora_inicio)->format('H:i') : null;
            $label = ($at->descricao ?: 'Momento') . ' — ' . $dia . ($hora ? ' '.$hora : '');
          @endphp
          <option value="{{ $at->id }}" @selected((string)$atividadeId === (string)$at->id)>{{ $label }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-lg-2">
      <label class="form-label mb-1">Por página</label>
      <select name="per_page" class="form-select form-select-sm">
        @foreach([25,50,100,200] as $pp)
          <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-lg-12 col-xl-0 col-sm-12 col-md-auto d-grid">
      <button class="btn btn-sm btn-primary">Filtrar</button>
    </div>
  </form>

  {{-- Resumo --}}
  <div class="small text-muted mb-2">
    Total: {{ $inscricoes->total() }} - Pagina {{ $inscricoes->currentPage() }} de {{ $inscricoes->lastPage() }}
  </div>

  {{-- Tabela --}}
  <div class="table-responsive">
    <table class="table table-sm align-middle table-bordered bg-white">
      <thead class="table-light">
        <tr>
          <!-- <th style="width:70px;">#ID</th> -->
          <th>Nome</th>
          <th>Email</th>
          <th style="min-width:120px;">CPF</th>
          <th style="min-width:120px;">Telefone</th>
          <th style="min-width:220px;">Municipio</th>
          <th style="min-width:220px;">Momento</th>
          <!-- <th style="min-width:140px;">Data entrada</th> -->
          <th style="min-width:160px;">Inscrito em</th>
          {{-- Opcional: ações (ver, remover, etc.) --}}
          {{-- <th style="width:110px;">Ações</th> --}}
        </tr>
      </thead>
      <tbody>
        @forelse($inscricoes as $inscricao)
          @php
            $participante = $inscricao->participante;
            $user = optional($participante?->user);
            $municipio = optional($participante?->municipio);
            $atividade = optional($inscricao->atividade);
            $cpfValido = $participante?->cpf_valido;
          @endphp
          <tr>
            <!-- <td>{{ $inscricao->id }}</td> -->
            <td>{{ $user->name ?? '-' }}</td>
            <td>{{ $user->email ?? '-' }}</td>
            <td>
              @if($participante && !$cpfValido)
                <span class="text-danger" data-bs-toggle="tooltip" title="CPF invalido">
                  {{ $participante->cpf }}
                </span>
              @else
                {{ $participante?->cpf ?? '-' }}
              @endif
            </td>
            <td>{{ $participante?->telefone ?? '-' }}</td>
            <td>
              @if($municipio)
                {{ $municipio->nome_com_estado }}
              @else
                -
              @endif
            </td>
            <td>
              @if($atividade)
                <div>{{ $atividade->descricao ?: 'Momento' }}</div>
                <div class="text-muted small">
                  {{ \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') }}
                  @if($atividade->hora_inicio)
                    as {{ \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') }}
                  @endif
                </div>
              @else
                -
              @endif
            </td>
            <td>
              {{ optional($inscricao->created_at)->format('d/m/Y H:i') ?? '-' }}
            </td>

            {{-- Acoes futuras aqui --}}
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center text-muted">Nenhum inscrito encontrado.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="d-flex justify-content-between align-items-center">
    <div class="small text-muted">Exibindo {{ $inscricoes->count() }} de {{ $inscricoes->total() }}</div>
    {{ $inscricoes->links() }}
  </div>
</div>
@endsection
