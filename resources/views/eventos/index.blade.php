@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 fw-bold text-engaja mb-0">Ações pedagógicas</h1>

            @hasanyrole('administrador|formador')
            <a href="{{ route('eventos.create') }}" class="btn btn-engaja">Nova ação pedagógica</a>
            @endhasanyrole
        </div>
        {{-- Filtros / busca --}}
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-4">
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                    placeholder="Buscar por nome, tipo, objetivo…">
            </div>
            <div class="col-md-3">
                <select name="eixo" class="form-select">
                    <option value="">Todos os eixos</option>
                    @foreach($eixos as $eixo)
                        <option value="{{ $eixo->id }}" @selected(request('eixo') == $eixo->id)>{{ $eixo->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" name="de" value="{{ request('de') }}" class="form-control" placeholder="de">
            </div>
            <div class="col-md-2 d-grid">
                <button class="btn btn-outline-secondary">Filtrar</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Eixo</th>
                        <th>Tipo</th>
                        <th>Período</th>
                        <th>Criado por</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($eventos as $ev)
                        <tr>
                            <td class="fw-semibold">{{ $ev->nome }}</td>
                            <td>{{ $ev->eixo->nome ?? '—' }}</td>
                            <td>{{ $ev->tipo ?? '—' }}</td>
                            <td>
                                @php
                                    $inicio = $ev->data_inicio ? \Carbon\Carbon::parse($ev->data_inicio)->format('d/m/Y') : null;
                                    $fim = $ev->data_fim ? \Carbon\Carbon::parse($ev->data_fim)->format('d/m/Y') : null;
                                    $mostrarFim = $fim && (!$inicio || $fim !== $inicio);
                                @endphp
                                @if($inicio || $fim)
                                    {{ $inicio ?? '—' }} @if($mostrarFim)<br><small class="text-muted">até {{ $fim }}</small>@endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $ev->user->name ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('eventos.show', $ev) }}" class="btn btn-sm btn-outline-primary">
                                    Ver
                                </a>
                                @can('update', $ev)
                                <a href="{{ route('eventos.edit', $ev) }}" class="btn btn-sm btn-outline-secondary">Editar</a>

                                <form action="{{ route('eventos.destroy', $ev) }}" method="POST" class="d-inline" data-confirm="Tem certeza que deseja excluir esta ação pedagógica?">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Excluir</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Nenhuma ação pedagógica encontrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $eventos->withQueryString()->links() }}
    </div>
@endsection
