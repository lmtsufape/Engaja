@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Inscritos — {{ $evento->nome }}</h1>
    @hasanyrole('administrador|gerente|eq_pedagogica')
    <div class="d-flex gap-2">
      <a href="{{ route('inscricoes.import', $evento) }}" class="btn btn-sm btn-outline-primary">Importar inscrições</a>
      <a href="{{ route('inscricoes.moodle.import', $evento) }}" class="btn btn-sm btn-warning fw-semibold">Importação Moodle</a>
    </div>
    @endhasrole
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

  @php
      $columns = [
          ['field' => 'nome', 'headerName' => 'Nome', 'flex' => 2],
          ['field' => 'email', 'headerName' => 'Email', 'flex' => 2],
          ['field' => 'cpf', 'headerName' => 'CPF', 'flex' => 1],
          ['field' => 'telefone', 'headerName' => 'Telefone', 'flex' => 1],
          ['field' => 'municipio', 'headerName' => 'Município', 'flex' => 2],
          ['field' => 'momento', 'headerName' => 'Momento', 'flex' => 2],
          ['field' => 'inscrito_em', 'headerName' => 'Inscrito em', 'flex' => 1],
      ];

      $rows = collect($inscricoes->items())->map(function ($inscricao) {
          $participante = $inscricao->participante;
          $user = optional($participante?->user);
          $municipio = optional($participante?->municipio);
          $atividade = optional($inscricao->atividade);
          $cpfValido = $participante?->cpf_valido;

          $momento = '-';
          if ($atividade->descricao !== null || $atividade->dia !== null) {
              $momentoTexto = $atividade->descricao ?: 'Momento';
              $dia = $atividade->dia ? \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') : null;
              $hora = $atividade->hora_inicio ? \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') : null;
              $momento = trim($momentoTexto . ($dia ? " — {$dia}" : '') . ($hora ? " {$hora}" : ''));
          }

          return [
              'nome' => $user->name ?? '-',
              'email' => $user->email ?? '-',
              'cpf' => ($participante && ! $cpfValido) ? ($participante->cpf . ' (inválido)') : ($participante?->cpf ?? '-'),
              'telefone' => $participante?->telefone ?? '-',
              'municipio' => $municipio ? $municipio->nome_com_estado : '-',
              'momento' => $momento,
              'inscrito_em' => optional($inscricao->created_at)->format('d/m/Y H:i') ?? '-',
          ];
      })->values();
  @endphp

  {{-- Tabela --}}
  <x-data-table
      id="grid-inscricoes"
      :columns="$columns"
      :rows="$rows"
      :pagination="false"
  />

  <div class="d-flex justify-content-between align-items-center mt-3">
    <div class="small text-muted">Exibindo {{ $inscricoes->count() }} de {{ $inscricoes->total() }}</div>
    {{ $inscricoes->links() }}
  </div>
</div>
@endsection
