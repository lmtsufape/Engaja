@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="mb-4">Minhas Presenças</h2>
        <form method="GET" class="mb-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Ação pedagógica (evento)</label>
                    <select name="evento_id" class="form-select">
                        <option value="">Todas</option>
                        @foreach($eventos as $evento)
                           <option value="{{ $evento->id }}" {{ ($filtros['evento_id'] ?? '') == $evento->id ? 'selected' : '' }}>
                                {{ $evento->nome }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">De</label>
                    <input type="date" name="data_de" class="form-control" value="{{ $filtros['data_de'] }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Até</label>
                    <input type="date" name="data_ate" class="form-control" value="{{ $filtros['data_ate'] }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Buscar (momento/ação)</label>
                    <input type="text" name="busca" class="form-control" value="{{ $filtros['busca'] }}">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100">Filtrar</button>
                    <a href="{{ route('profile.presencas') }}" class="btn btn-secondary w-100">Limpar</a>
                </div>
            </div>
        </form>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Hora</th>
                        <th>Momento</th>
                        <th>Ação Pedagógica</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($atividades as $atividade)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($atividade['data'])->format('d/m/Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($atividade['hora'])->format('H:i') }}</td>
                            <td>{{ $atividade['momento'] }}</td>
                            <td>{{ $atividade['evento'] }}</td>
                            <td>
                                @if($atividade['status'] === 'Presente')
                                    <span class="badge bg-success">Presente</span>
                                @else
                                    <span class="badge bg-danger">Ausente</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Nenhuma presença encontrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection