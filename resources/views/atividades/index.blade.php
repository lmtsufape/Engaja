@extends('layouts.app')

@section('content')
  <div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h4 fw-bold text-engaja mb-0">Momentos — {{ $evento->nome }}</h1>

        @hasanyrole('administrador|formador')
        <small class="text-muted">Gerencie a programação da ação pedagógica</small>
        @endhasanyrole
      </div>

      @hasanyrole('administrador|formador')
      <a href="{{ route('eventos.atividades.create', $evento) }}" class="btn btn-engaja">+ Novo momento</a>
      @endhasanyrole
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead class="table-light">
          <tr>
            <th>Dia</th>
            <th>Hora início</th>
            <th>Hora de término</th>
            <th>Municípios</th>
            <th>Público esperado</th>
            <th>Carga horária</th>
            @hasanyrole('administrador|formador')
            <th class="text-end">Ações</th>
            @endhasanyrole
          </tr>
        </thead>
        @php $temPermissao = auth()->user()?->hasAnyRole('administrador', 'formador'); @endphp
        <tbody>
          @forelse($atividades as $at)
            @php
              $munLabel = $at->municipios->isNotEmpty()
                ? $at->municipios->map(fn($m) => $m->nome_com_estado ?? $m->nome)->join(', ')
                : '-';
            @endphp
            <tr>
              <td>{{ \Carbon\Carbon::parse($at->dia)->format('d/m/Y') }}</td>
              <td>{{ \Carbon\Carbon::parse($at->hora_inicio)->format('H:i') }}</td>
              <td>{{ \Carbon\Carbon::parse($at->hora_fim)->format('H:i') }}</td>
              <td>{{ $munLabel }}</td>
              <td>{{ $at->publico_esperado ? number_format($at->publico_esperado, 0, ',', '.') : '—' }}</td>
              <td>
                @php
                  $carga = $at->carga_horaria;
                  $cargaLabel = !is_null($carga) ? number_format($carga, 0, ',', '.') . 'h' : '—';
                @endphp
                {{ $cargaLabel }}
              </td>
              @hasanyrole('administrador|formador')
              <td class="text-end">
                <a href="{{ route('atividades.show', $at) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                <a href="{{ route('atividades.edit', $at) }}" class="btn btn-sm btn-outline-secondary">Editar</a>
                <form class="d-inline" method="POST" action="{{ route('atividades.destroy', $at) }}"
                  data-confirm="Tem certeza que deseja excluir este momento?">
                  @csrf @method('DELETE')
                  <button class="btn btn-sm btn-outline-danger">Excluir</button>
                </form>
              </td>
              @endhasanyrole
            </tr>
          @empty
            <tr>
              <td colspan="{{ $temPermissao ? 7 : 6 }}" class="text-center text-muted py-4">Nenhum momento cadastrada.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    {{ $atividades->links() }}

    <div class="mt-3">
      <a href="{{ route('eventos.show', $evento) }}" class="btn btn-outline-secondary">Voltar à ação pedagógica</a>
    </div>
  </div>
@endsection
