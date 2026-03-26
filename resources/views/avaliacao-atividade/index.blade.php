@extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-engaja mb-0">Relatórios da Ação</h1>
            <small class="text-muted">
                {{ auth()->user()?->hasAnyRole(['administrador', 'gerente'])
                    ? 'Relatórios pós-ação preenchidos por utilizadores do sistema'
                    : 'Os seus relatórios individuais pós-ação' }}
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

    @if($acoesAgrupadas->isEmpty())
        <div class="alert alert-info">Nenhum relatório encontrado.</div>
    @else
    <div class="vstack gap-3">
        @foreach($acoesAgrupadas as $nomeAcao => $momentos)
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0 text-engaja">{{ $nomeAcao }}</h2>
            </div>
            <div class="card-body">
                @foreach($momentos as $momentoIndex => $momento)
                @php
                    $atividade = $momento['atividade'];
                    $relatoriosMomento = $momento['relatorios'];
                    $collapseId = 'relatorio-momento-' . ($atividade?->id ?? 'x') . '-' . $momentoIndex;
                @endphp
                <div class="border rounded-3 p-3 mb-3">
                    <button class="btn w-100 text-start d-flex flex-wrap justify-content-between align-items-start gap-2 p-0 border-0 bg-transparent"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#{{ $collapseId }}"
                            aria-expanded="false"
                            aria-controls="{{ $collapseId }}">
                        <span>
                            <span class="fw-semibold d-block">{{ $atividade?->descricao ?? 'Momento não informado' }}</span>
                            <span class="small text-muted d-block">
                                {{ $atividade?->dia ? \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') : '—' }}
                                @if($atividade?->hora_inicio || $atividade?->hora_fim)
                                    • {{ $atividade?->hora_inicio ? \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i') : '?' }} - {{ $atividade?->hora_fim ? \Carbon\Carbon::parse($atividade->hora_fim)->format('H:i') : '?' }}
                                @endif
                                • {{ $atividade?->municipios?->map(fn($m) => $m->nome_com_estado ?? $m->nome)->join(', ') ?: 'Município não informado' }}
                            </span>
                        </span>
                        <span class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                            <span class="badge bg-secondary">{{ $relatoriosMomento->count() }} relatório(s)</span>
                            <span class="badge rounded-pill" style="background-color:#421944; color:#fff; font-size:.85rem; padding:.45rem .7rem;">
                                Clique aqui para ver as respostas
                            </span>
                        </span>
                    </button>

                    <div id="{{ $collapseId }}" class="collapse mt-3">

                    @foreach($camposPerguntas as $campo => $pergunta)
                        @php
                            $respostas = $relatoriosMomento->filter(function ($relatorio) use ($campo) {
                                return filled(trim((string)($relatorio->$campo ?? '')));
                            });
                        @endphp
                        <div class="mb-3">
                            <div class="fw-semibold mb-2">{{ $pergunta }}</div>

                            @if($respostas->isEmpty())
                                <div class="text-muted small border rounded p-2">Sem respostas para esta pergunta.</div>
                            @else
                                @foreach($respostas as $relatorio)
                                    <div class="border rounded p-2 mb-2 bg-light">
                                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                            <div class="small text-muted">
                                                <strong>{{ $relatorio->user->name ?? $relatorio->nome_educador ?? 'Usuário não informado' }}</strong>
                                                • {{ $relatorio->updated_at ? $relatorio->updated_at->format('d/m/Y H:i') : '—' }}
                                            </div>
                                            <div class="d-flex gap-1">
                                                <a href="{{ route('avaliacao-atividade.show', $relatorio) }}" class="btn btn-sm btn-outline-primary">Ver</a>
                                                <a href="{{ route('avaliacao-atividade.download', $relatorio) }}" class="btn btn-sm btn-outline-secondary">PDF</a>
                                            </div>
                                        </div>
                                        <div>{{ $relatorio->$campo }}</div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
@endsection
