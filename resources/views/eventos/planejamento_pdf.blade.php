@extends('layouts.pdf-alfa-eja')

@section('title')
    Planejamento – {{ $evento->nome ?? 'Evento' }}
@endsection

@section('styles')
    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 10.5px;
        color: #111;
        line-height: 1.5;
    }

    .content { padding: 24px 16px 32px; }

    .section { margin-bottom: 18px; }
    .section-title {
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #421944;
        border-bottom: 1px solid #c9a0d0;
        padding-bottom: 3px;
        margin-bottom: 9px;
    }

    .field { margin-bottom: 7px; }
    .field-label {
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
        color: #6b7280;
        margin-bottom: 1px;
    }
    .cols { width: 100%; }
    .cols td { vertical-align: top; padding-right: 16px; width: 50%; }

    .list-item {
        padding: 3px 0;
        border-bottom: 1px dotted #e5e7eb;
    }
    .ods-item { padding: 2px 0 2px 10px; }

    .seq-wrap { border: 1px solid #d1d5db; margin-bottom: 8px; width: 100%; }
    .seq-inner { width: 100%; border-collapse: collapse; }
    .seq-inner td { vertical-align: top; padding: 6px 10px; }
    .seq-period {
        background-color: #f3eaf5;
        width: 90px;
        font-weight: bold;
        color: #421944;
        border-right: 1px solid #d1d5db;
        white-space: nowrap;
    }

    .check-table { width: 100%; border-collapse: collapse; }
    .check-table td { vertical-align: top; padding: 4px 3px; border-bottom: 1px dotted #e5e7eb; }
    .check-icon { width: 16px; text-align: center; }
    .icon-ok  { color: #16a34a; font-size: 13px; }
    .icon-no  { color: #9ca3af; font-size: 13px; }
@endsection

@section('content')
<div class="content">

    <x-pdf.header
        title="Planejamento de Ação Pedagógica"
        subtitle="Projeto Engaja"
    />

    <div class="section">
        <div class="section-title">Dados da Ação</div>

        <div class="field">
            <div class="field-label">Nome da ação pedagógica</div>
            <strong>{{ $evento->nome }}</strong>
        </div>

        @if ($evento->acao_geral)
        <table class="cols"><tr>
            <td>
                <div class="field">
                    <div class="field-label">Ação Geral</div>
                    {{ $acoesGerais[$evento->acao_geral] ?? $evento->acao_geral }}
                </div>
            </td>
            <td>
                <div class="field">
                    <div class="field-label">Sub-Ação</div>
                    {{ $evento->subacao ?? '—' }}
                </div>
            </td>
        </tr></table>
        @endif

        <table class="cols"><tr>
            @if ($evento->tipo)
            <td>
                <div class="field">
                    <div class="field-label">Tipo</div>
                    {{ $evento->tipo }}
                </div>
            </td>
            @endif
            @if ($evento->modalidade)
            <td>
                <div class="field">
                    <div class="field-label">Modalidade</div>
                    {{ $evento->modalidade }}
                </div>
            </td>
            @endif
        </tr></table>

        <table class="cols"><tr>
            @if ($evento->data_inicio)
            <td>
                <div class="field">
                    <div class="field-label">Data de início</div>
                    {{ \Carbon\Carbon::parse($evento->data_inicio)->format('d/m/Y') }}
                </div>
            </td>
            @endif
            @if ($evento->data_fim)
            <td>
                <div class="field">
                    <div class="field-label">Data de término</div>
                    {{ \Carbon\Carbon::parse($evento->data_fim)->format('d/m/Y') }}
                </div>
            </td>
            @endif
        </tr></table>

        @if ($evento->local)
        <div class="field">
            <div class="field-label">Local</div>
            {{ $evento->local }}
        </div>
        @endif
    </div>

    @if ($evento->objetivos_gerais || $evento->objetivos_especificos)
    <div class="section">
        <div class="section-title">Objetivos</div>
        @if ($evento->objetivos_gerais)
        <div class="field">
            <div class="field-label">Objetivos Gerais</div>
            {{ $evento->objetivos_gerais }}
        </div>
        @endif
        @if ($evento->objetivos_especificos)
        <div class="field">
            <div class="field-label">Objetivos Específicos</div>
            {{ $evento->objetivos_especificos }}
        </div>
        @endif
    </div>
    @endif

    @if ($evento->situacoesDesafiadoras->isNotEmpty())
    <div class="section">
        <div class="section-title">Situações Desafiadoras da EJA a serem enfrentadas</div>
        @foreach ($evento->situacoesDesafiadoras as $sit)
        <div class="list-item">{{ $sit->nome }}</div>
        @endforeach
    </div>
    @endif

    @php $matrizesPlanejamento = $matrizesOrdenadas ?? $evento->matrizes; @endphp
    @if ($matrizesPlanejamento->isNotEmpty())
    <div class="section">
        <div class="section-title">Matriz de Aprendizagens</div>
        @foreach ($matrizesPlanejamento as $matriz)
        <div class="list-item">{{ $matriz->nome }}</div>
        @endforeach
    </div>
    @endif

    @if (!empty($evento->ods_selecionados))
    <div class="section">
        <div class="section-title">Interfaces com os Objetivos de Desenvolvimento Sustentável (ODS)</div>
        @foreach ($evento->ods_selecionados as $aspecto)
        <div class="ods-item">&bull; {{ $aspecto }}</div>
        @endforeach
    </div>
    @endif

    @if ($evento->sequenciasDidaticas->isNotEmpty())
    <div class="section">
        <div class="section-title">Sequência Didática das Atividades</div>
        @foreach ($evento->sequenciasDidaticas as $i => $seq)
        <table class="seq-wrap">
            <tr>
                <td class="seq-period">
                    @if ($seq->periodo)
                        {{ $seq->periodo }}
                    @else
                        Momento {{ $i + 1 }}
                    @endif
                </td>
                <td>{{ $seq->descricao }}</td>
            </tr>
        </table>
        @endforeach
    </div>
    @endif

    @if ($evento->recursos_materiais_necessarios || $evento->providencias_sme_parceria || $evento->observacoes_complementares)
    <div class="section">
        <div class="section-title">Informações Complementares</div>
        @if ($evento->recursos_materiais_necessarios)
        <div class="field">
            <div class="field-label">Recursos Materiais Necessários</div>
            {{ $evento->recursos_materiais_necessarios }}
        </div>
        @endif
        @if ($evento->providencias_sme_parceria)
        <div class="field">
            <div class="field-label">Providências junto à SME / Parceria</div>
            {{ $evento->providencias_sme_parceria }}
        </div>
        @endif
        @if ($evento->observacoes_complementares)
        <div class="field">
            <div class="field-label">Observações Complementares</div>
            {{ $evento->observacoes_complementares }}
        </div>
        @endif
    </div>
    @endif

    <div class="section">
        <div class="section-title">Checklist do Planejamento</div>
        @php
            $checkedInts = array_map('intval', $evento->checklist_planejamento ?? []);
        @endphp
        <table class="check-table">
            @foreach ($checklistItems as $idx => $label)
            <tr>
                <td class="check-icon">
                    @if (in_array($idx, $checkedInts))
                        <span class="icon-ok">&#9745;</span>
                    @else
                        <span class="icon-no">&#9744;</span>
                    @endif
                </td>
                <td>{{ $label }}</td>
            </tr>
            @endforeach
        </table>
    </div>


</div>
@endsection
