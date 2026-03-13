@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-engaja mb-0">Relatórios de Avaliação da Ação</h1>
            <small class="text-muted">
                {{ auth()->user()?->hasAnyRole(['administrador', 'gerente'])
                    ? 'Relatórios pós-ação preenchidos por utilizadores do sistema'
                    : 'As suas avaliações individuais pós-ação' }}
            </small>
        </div>
    </div>

    {{-- Filtro --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <form method="GET" action="{{ route('avaliacao-atividade.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label mb-1 small">Buscar (momento, ação ou educador)</label>
                    <input type="text" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Digite para filtrar...">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-engaja" style="background-color:#421944; color:white;">Aplicar</button>
                    <a href="{{ route('avaliacao-atividade.index') }}" class="btn btn-outline-secondary ms-1">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    @if($relatorios->isEmpty())
        <div class="alert alert-info">Nenhum relatório encontrado.</div>
    @else
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Momento</th>
                        <th>Ação Pedagógica</th>
                        <th>Município(s)</th>
                        @if(auth()->user()?->hasAnyRole(['administrador', 'gerente']))
                        @endif
                        <th>Data do Momento</th>
                        <th>Última atualização</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($relatorios as $relatorio)
                    @php
                        $at = $relatorio->atividade;
                        $checklistSalvo = $relatorio->checklist_pos_acao ?? [];
                        $totalCheck = 4;
                        $marcados = count($checklistSalvo);
                        $checkCompleto = $marcados >= $totalCheck;
                    @endphp
                    <tr>
                        <td>
                            @if($at->hora_inicio || $at->hora_fim)
                                <div class="text-muted small">
                                    {{ $at->hora_inicio ? \Carbon\Carbon::parse($at->hora_inicio)->format('H:i') : '?' }}
                                    –
                                    {{ $at->hora_fim ? \Carbon\Carbon::parse($at->hora_fim)->format('H:i') : '?' }}
                                </div>
                            @endif
                            <div class="fw-semibold">{{ $at->descricao ?? '—' }}</div>
                            @if(!$checkCompleto)
                                <span class="badge bg-warning text-dark fw-normal" style="font-size:.72rem;">
                                    ⚠️ Checklist {{ $marcados }}/{{ $totalCheck }}
                                </span>
                            @else
                                <span class="badge bg-success fw-normal" style="font-size:.72rem;">✅ Checklist completo</span>
                            @endif
                        </td>
                        <td>{{ $at->evento->nome ?? '—' }}</td>
                        <td>
                            {{ $at->municipios?->map(fn($m) => $m->nome_com_estado ?? $m->nome)->join(', ') ?: '—' }}
                        </td>
                        <td>
                            {{ $at->dia ? \Carbon\Carbon::parse($at->dia)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="text-muted small">
                            {{ $relatorio->updated_at ? $relatorio->updated_at->format('d/m/Y H:i') : '—' }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('avaliacao-atividade.edit', $at) }}" class="btn btn-sm btn-outline-primary">Ver / Editar</a>
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($relatorios->hasPages())
        <div class="card-footer">
            {{ $relatorios->links() }}
        </div>
        @endif
    </div>
    @endif

</div>
@endsection
