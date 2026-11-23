@extends('layouts.pdf-alfa-eja')

@php
    $templateNome = $avaliacao->templateAvaliacao->nome ?? 'Avaliação';
    $descricao = $avaliacao->descricao_universal ?? $avaliacao->atividade?->descricao ?? '';
    $titulo = $templateNome . ($descricao ? ' — ' . $descricao : '');

    $grupos = $avaliacao->avaliacaoQuestoes
        ->sortBy(function ($q) {
            $dim = mb_strtolower($q->indicador->dimensao->descricao ?? '');
            $ind = mb_strtolower($q->indicador->descricao ?? '');
            $ordem = $q->ordem ?? 999;
            return sprintf('%s|%s|%03d|%06d', $dim, $ind, $ordem, $q->id);
        })
        ->groupBy(fn($q) => $q->indicador->dimensao->descricao ?? 'Sem dimensão')
        ->map(fn($col) => $col->groupBy(fn($q) => $q->indicador->descricao ?? 'Sem indicador'));

    $num = 0;
@endphp

@section('title', $titulo)

@section('styles')
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 0; padding: 12px 0 24px; }

    .anonima-badge { display: inline-block; background: #f0e8f8; border: 1px solid #d0bfe0; border-radius: 3px; padding: 2px 7px; font-size: 9px; color: #421944; font-weight: bold; margin-bottom: 14px; }

    .id-box { border: 1px solid #d0bfe0; border-radius: 6px; padding: 10px 14px 6px; margin-bottom: 14px; background: #faf7fd; }
    .id-box-title { font-size: 10px; font-weight: bold; color: #421944; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 8px; }
    .id-row { margin-bottom: 10px; }
    .id-label { font-size: 10px; color: #444; font-weight: bold; margin-bottom: 2px; }
    .id-line { border-bottom: 1.5px solid #555; height: 20px; width: 100%; }
    .id-cpf-wrap { display: inline-block; }
    .cpf-group { display: inline-block; }
    .cpf-digit { display: inline-block; width: 13px; height: 20px; border-bottom: 1.5px solid #555; margin-right: 1px; }
    .cpf-sep { display: inline-block; font-size: 13px; font-weight: bold; margin: 0 2px; vertical-align: bottom; line-height: 20px; }

    .dim-title { font-size: 12px; font-weight: bold; color: #421944; margin: 16px 0 6px 0; padding-bottom: 3px; border-bottom: 1px solid #d0bfe0; page-break-after: avoid; }
    .ind-title { font-size: 10px; font-weight: bold; color: #008BBC; margin: 8px 0 4px 0; page-break-after: avoid; }

    .q-block { border: 1px solid #ddd; border-radius: 4px; padding: 8px 10px 6px; margin-bottom: 10px; page-break-inside: avoid; background: #fff; }
    .q-num { color: #888; font-size: 10px; }
    .q-breadcrumb { font-size: 9px; color: #888; margin-bottom: 3px; }
    .q-breadcrumb-sep { margin: 0 4px; color: #bbb; }
    .q-text { font-weight: bold; color: #1a1a1a; margin-bottom: 4px; line-height: 1.5; }
    .q-instruction { font-size: 9px; color: #777; font-style: italic; margin-bottom: 7px; }

    /* texto */
    .write-lines { margin-top: 4px; }
    .write-line { border-bottom: 1px solid #aaa; height: 22px; margin-bottom: 4px; }

    /* escala */
    .scale-wrap { margin-top: 6px; }
    .scale-choice-row { margin-bottom: 5px; }

    /* numero */
    .num-line { display: inline-block; border-bottom: 1.5px solid #555; width: 80px; height: 18px; margin-left: 4px; vertical-align: bottom; }

    /* boolean */
    .bool-wrap { margin-top: 6px; }
    .bool-opt { display: inline-block; margin-right: 24px; }
    .opt-circle { display: inline-block; width: 14px; height: 14px; border: 2px solid #333; border-radius: 50%; margin-right: 5px; vertical-align: middle; }
    .opt-square { display: inline-block; width: 14px; height: 14px; border: 2px solid #333; margin-right: 5px; vertical-align: middle; }
    .opt-label { font-size: 11px; vertical-align: middle; }

    /* unica / multipla */
    .choices-wrap { margin-top: 6px; }
    .choice-row { margin-bottom: 5px; }
@endsection

@section('content')
    <x-pdf.header :title="$titulo" subtitle="Ficha para preenchimento" />

    @if ($avaliacao->anonima)
        <div><span class="anonima-badge">Avaliação anônima — não é necessário se identificar</span></div>
    @endif

    {{-- Identificação (somente para avaliações não anônimas) --}}
    @if (!$avaliacao->anonima)
    <div class="id-box">
        <div class="id-box-title">Identificação do respondente</div>
        <div class="id-row">
            <div class="id-label">Nome completo</div>
            <div class="id-line"></div>
        </div>
        <div class="id-row">
            <div class="id-label">E-mail</div>
            <div class="id-line"></div>
        </div>
        <div class="id-row">
            <div class="id-label">CPF</div>
            <div class="id-cpf-wrap">
                {{-- 3 dígitos --}}
                @for ($i = 0; $i < 3; $i++)<span class="cpf-digit"></span>@endfor
                <span class="cpf-sep">.</span>
                {{-- 3 dígitos --}}
                @for ($i = 0; $i < 3; $i++)<span class="cpf-digit"></span>@endfor
                <span class="cpf-sep">.</span>
                {{-- 3 dígitos --}}
                @for ($i = 0; $i < 3; $i++)<span class="cpf-digit"></span>@endfor
                <span class="cpf-sep">-</span>
                {{-- 2 dígitos --}}
                @for ($i = 0; $i < 2; $i++)<span class="cpf-digit"></span>@endfor
            </div>
        </div>
    </div>
    @endif

    {{-- Questões agrupadas por dimensão → indicador --}}
    @foreach ($grupos as $dimNome => $indicadores)
        <div class="dim-title">Dimensão — {{ $dimNome }}</div>

        @foreach ($indicadores as $indNome => $qs)
            <div class="ind-title">Indicador — {{ $indNome }}</div>

            @foreach ($qs as $q)
                @php $num++ @endphp
                <div class="q-block">
                    <div class="q-text"><span class="q-num">{{ $num }}.</span> {{ $q->texto }}</div>

                    @if ($q->tipo === 'texto')
                        <div class="q-instruction">Questão dissertativa — escreva sua resposta nas linhas abaixo</div>
                        <div class="write-lines">
                            @for ($i = 0; $i < 5; $i++)
                                <div class="write-line"></div>
                            @endfor
                        </div>

                    @elseif ($q->tipo === 'escala')
                        @php $valores = array_reverse($q->escala?->valores ?? []); @endphp
                        <div class="q-instruction">Escala de avaliação — marque apenas uma opção</div>
                        <div class="scale-wrap">
                            @foreach ($valores as $val)
                            <div class="scale-choice-row">
                                <span class="opt-circle"></span><span class="opt-label">{{ $val }}</span>
                            </div>
                            @endforeach
                        </div>

                    @elseif ($q->tipo === 'numero')
                        <div class="q-instruction">Questão numérica — escreva um número na linha abaixo</div>
                        <div style="margin-top: 6px;">
                            <span style="font-size:10px; color:#555;">R:</span><span class="num-line"></span>
                        </div>

                    @elseif ($q->tipo === 'boolean')
                        <div class="q-instruction">Marque apenas uma opção</div>
                        <div class="bool-wrap">
                            <span class="bool-opt">
                                <span class="opt-circle"></span><span class="opt-label">Sim</span>
                            </span>
                            <span class="bool-opt">
                                <span class="opt-circle"></span><span class="opt-label">Não</span>
                            </span>
                        </div>

                    @elseif ($q->tipo === 'unica')
                        <div class="q-instruction">Marque apenas uma opção</div>
                        <div class="choices-wrap">
                            @foreach ($q->opcoes_resposta ?? [] as $opcao)
                            <div class="choice-row">
                                <span class="opt-circle"></span><span class="opt-label">{{ $opcao }}</span>
                            </div>
                            @endforeach
                        </div>

                    @elseif ($q->tipo === 'multipla')
                        <div class="q-instruction">Múltipla escolha — marque todas as opções que se aplicam</div>
                        <div class="choices-wrap">
                            @foreach ($q->opcoes_resposta ?? [] as $opcao)
                            <div class="choice-row">
                                <span class="opt-square"></span><span class="opt-label">{{ $opcao }}</span>
                            </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        @endforeach
    @endforeach
@endsection
