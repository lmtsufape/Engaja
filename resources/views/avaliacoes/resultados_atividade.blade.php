@extends('layouts.app')

@section('content')
<div class="container py-4">

    {{-- Cabeçalho --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="h4 fw-bold mb-1" style="color:#421944;">
                📊 Avaliações do Momento
            </h1>
            <p class="text-muted mb-0">
                <strong>{{ $atividade->descricao }}</strong> —
                {{ \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') }}
                @if($atividade->municipios->isNotEmpty())
                    · {{ $atividade->municipios->map(fn($m) => $m->nome_com_estado ?? $m->nome)->join(', ') }}
                @endif
            </p>
            <p class="text-muted small mt-1 mb-0">
                Ação pedagógica: <strong>{{ $atividade->evento->nome ?? '—' }}</strong>
            </p>
        </div>
        <a href="{{ route('eventos.show', $atividade->evento_id) }}"
           class="btn btn-outline-secondary">← Voltar à ação pedagógica</a>
    </div>

    {{-- Alerta de anonimato --}}
    <div class="alert alert-info d-flex align-items-center gap-2 mb-4" role="alert">
        <span style="font-size:1.2rem;">🔒</span>
        <div>
            As avaliações exibidas abaixo são <strong>estritamente anónimas</strong>.
            Nenhum dado identificador do participante (nome, e-mail, CPF) é armazenado
            ou apresentado nesta visualização.
        </div>
    </div>

    {{-- Resumo --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-md-3">
            <div class="card text-center border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="h2 fw-bold mb-0" style="color:#421944;">{{ $submissoes->count() }}</div>
                    <div class="text-muted small mt-1">Avaliações recebidas</div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-md-9">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex align-items-center">
                    <div>
                        <div class="fw-semibold mb-1">Formulário aplicado</div>
                        <div class="text-muted small">
                            {{ $avaliacao->templateAvaliacao->nome ?? '—' }}
                            &nbsp;·&nbsp;
                            {{ $avaliacao->avaliacaoQuestoes->count() }} questão(ões)
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($submissoes->isEmpty())
        <div class="alert alert-warning">
            Nenhuma avaliação recebida ainda para este momento.
        </div>
    @else

        {{-- Resumo agregado por questão --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold" style="background:#f3eaf5; color:#421944;">
                Resumo das respostas por questão
            </div>
            <div class="card-body p-0">
                @foreach($avaliacao->avaliacaoQuestoes->sortBy('ordem') as $questao)
                @php
                    $respostasDaQuestao = $submissoes
                        ->flatMap(fn($s) => $s->respostas)
                        ->filter(fn($r) => $r->avaliacao_questao_id === $questao->id)
                        ->pluck('resposta');

                    $totalRespostas = $respostasDaQuestao->count();
                @endphp
                <div class="border-bottom p-3">
                    <div class="fw-semibold mb-2 small text-uppercase text-muted">
                        Questão {{ $loop->iteration }}
                    </div>
                    <div class="mb-2">{{ $questao->texto }}</div>

                    @if($totalRespostas === 0)
                        <span class="text-muted small">Sem respostas registadas.</span>
                    @elseif($questao->tipo === 'escala' || $questao->tipo === 'boolean')
                        {{-- Contagem agrupada para tipos fechados --}}
                        @php
                            $contagem = $respostasDaQuestao->countBy()->sortKeys();
                        @endphp
                        <div class="d-flex flex-wrap gap-2 mt-1">
                            @foreach($contagem as $opcao => $qtd)
                            <span class="badge rounded-pill"
                                  style="background:#421944; font-size:.85rem; padding:.4em .8em;">
                                {{ $questao->tipo === 'boolean' ? ($opcao == '1' ? 'Sim' : 'Não') : $opcao }}:
                                <strong>{{ $qtd }}</strong>
                            </span>
                            @endforeach
                        </div>
                        <div class="text-muted small mt-1">{{ $totalRespostas }} resposta(s)</div>
                    @elseif($questao->tipo === 'numero')
                        @php
                            $valores = $respostasDaQuestao->map(fn($v) => (float)$v);
                            $media   = $valores->avg();
                            $min     = $valores->min();
                            $max     = $valores->max();
                        @endphp
                        <div class="text-muted small">
                            Média: <strong>{{ number_format($media, 2, ',', '.') }}</strong>
                            &nbsp;· Mín: {{ $min }} · Máx: {{ $max }}
                            &nbsp;· {{ $totalRespostas }} resposta(s)
                        </div>
                    @else
                        {{-- Tipo texto: listar todas as respostas abertas sem identificação --}}
                        <div class="text-muted small mb-1">{{ $totalRespostas }} resposta(s) abertas:</div>
                        <ul class="list-group list-group-flush">
                            @foreach($respostasDaQuestao as $resp)
                            <li class="list-group-item py-1 px-0 border-0 small">
                                "{{ $resp }}"
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Tabela de submissões (anónima — apenas número e data) --}}
        <div class="card shadow-sm">
            <div class="card-header fw-semibold" style="background:#f3eaf5; color:#421944;">
                Lista de submissões ({{ $submissoes->count() }} no total)
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Data / Hora de envio</th>
                                <th>Respostas fornecidas</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($submissoes as $idx => $sub)
                            <tr>
                                <td class="text-muted">{{ $idx + 1 }}</td>
                                <td>{{ $sub->created_at->format('d/m/Y H:i') }}</td>
                                <td>
                                    @foreach($sub->respostas as $resp)
                                    <span class="badge bg-light text-dark border me-1 mb-1"
                                          style="font-size:.75rem; max-width:220px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:inline-block;"
                                          title="{{ $resp->avaliacaoQuestao?->texto }}: {{ $resp->resposta }}">
                                        {{ Str::limit($resp->resposta, 40) }}
                                    </span>
                                    @endforeach
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @endif

</div>
@endsection

@push('scripts')
<script>
// Apenas garante que nenhum dado identificador seja exposto via JS
</script>
@endpush
