@extends('layouts.app')

@section('content')
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h1 class="h4 mb-1">Participantes do agendamento</h1>
      <div class="text-muted small">
        {{ optional($agendamento->data_horario)->format('d/m/Y H:i') }} · {{ $agendamento->atividadeAcao?->nome ?? 'Atividade/Ação' }}
      </div>
    </div>
    @unless($agendamento->efetivado)
      <div class="d-flex gap-2">
        <a href="{{ route('agendamentos.participantes.import', $agendamento) }}" class="btn btn-outline-primary">Importar XLSX</a>
        <a href="{{ route('agendamentos.participantes.create', $agendamento) }}" class="btn btn-engaja">Novo participante</a>
      </div>
    @endunless
  </div>

  <div class="alert alert-light border mb-3">
    <strong>Agendamento selecionado:</strong>
    {{ optional($agendamento->data_horario)->format('d/m/Y H:i') }} ·
    {{ $agendamento->atividadeAcao?->nome ?? 'Atividade/Ação' }} ·
    {{ $agendamento->local_acao }}
    @if($agendamento->turma)
      · Turma {{ $agendamento->turma }}
    @endif
  </div>

  <form method="GET" action="{{ route('agendamentos.participantes.index', $agendamento) }}" class="row g-2 align-items-end mb-3">
    <div class="col-md-6">
      <label class="form-label mb-1">Buscar</label>
      <input type="text" name="q" value="{{ $search }}" class="form-control form-control-sm" placeholder="Nome, e-mail, CPF ou telefone">
    </div>
    <div class="col-md-2">
      <label class="form-label mb-1">Por página</label>
      <select name="per_page" class="form-select form-select-sm">
        @foreach([25, 50, 100] as $pp)
          <option value="{{ $pp }}" @selected($perPage == $pp)>{{ $pp }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2 d-grid">
      <button class="btn btn-sm btn-primary">Filtrar</button>
    </div>
    <div class="col-md-2 d-grid">
      <a href="{{ route('agendamentos.show', $agendamento) }}" class="btn btn-sm btn-outline-secondary">Voltar</a>
    </div>
  </form>

  @php
      $columns = [
          ['field' => 'nome', 'headerName' => 'Nome', 'flex' => 2],
          ['field' => 'cpf', 'headerName' => 'CPF', 'flex' => 1],
          ['field' => 'email', 'headerName' => 'E-mail', 'flex' => 2],
          ['field' => 'nascimento', 'headerName' => 'Nascimento', 'flex' => 1],
          ['field' => 'telefone', 'headerName' => 'Telefone', 'flex' => 1],
          ['field' => 'vinculo', 'headerName' => 'Vínculo', 'flex' => 1],
          ['field' => 'turma', 'headerName' => 'Turma', 'flex' => 1],
          ['field' => 'origem', 'headerName' => 'Origem', 'flex' => 1],
      ];

      if (! $agendamento->efetivado) {
          $columns[] = ['field' => 'acoes', 'headerName' => 'Ações', 'flex' => 1, 'html' => true, 'align' => 'center'];
      }

      $rows = $participantes->map(function ($participante) use ($agendamento) {
          $row = [
              'nome' => $participante->nome,
              'cpf' => $participante->cpf ?: '—',
              'email' => $participante->email ?: '—',
              'nascimento' => $participante->data_nascimento ? \Illuminate\Support\Carbon::parse($participante->data_nascimento)->format('d/m/Y') : '—',
              'telefone' => $participante->telefone ?: '—',
              'vinculo' => $participante->vinculo ?: '—',
              'turma' => $participante->turma ?: '—',
              'origem' => $participante->origem,
          ];

          if (! $agendamento->efetivado) {
              $row['acoes'] = '<a href="' . route('agendamentos.participantes.edit', [$agendamento, $participante]) . '" class="btn btn-sm btn-outline-secondary">Editar</a> '
                  . '<form method="POST" action="' . route('agendamentos.participantes.destroy', [$agendamento, $participante]) . '" class="d-inline" data-confirm="Tem certeza que deseja excluir este participante?">'
                  . csrf_field() . method_field('DELETE')
                  . '<button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>'
                  . '</form>';
          }

          return $row;
      })->values();
  @endphp

  <x-data-table id="grid-agendamento-participantes" :columns="$columns" :rows="$rows" :pagination="false" class="bg-white" />

  <div class="d-flex justify-content-between align-items-center mt-2">
    <div class="small text-muted">Total: {{ $participantes->total() }}</div>
    {{ $participantes->links() }}
  </div>
</div>
@endsection
