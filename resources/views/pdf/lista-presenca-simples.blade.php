@extends('layouts.pdf-alfa-eja')

@section('title', 'Lista de Presença')

@section('styles')
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }

    .info-grid { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
    .info-grid td { padding: 5px 8px; border: 1px solid #e5e7eb; vertical-align: top; }
    .info-grid .label { background: #f3f4f6; font-weight: 700; color: #374151; width: 22%; }

    .section-title { font-size: 13px; font-weight: 700; color: #421944; border-left: 5px solid #421944; padding-left: 8px; margin: 0 0 10px 0; }

    .pdf-table .td-name { font-weight: 700; color: #111827; }
    .badge-ouvinte { background: #f5f3ff; color: #2c1230; border-radius: 4px; padding: 1px 5px; font-size: 9px; font-weight: 700; margin-left: 5px; }

    .total { text-align: right; font-size: 10px; color: #6b7280; margin-top: 10px; }
    .empty { color: #9ca3af; font-style: italic; padding: 12px 0; }
@endsection

@section('content')
@php
    $ini = \Carbon\Carbon::parse($atividade->hora_inicio)->format('H:i');
    $fim = \Carbon\Carbon::parse($atividade->hora_fim)->format('H:i');
    $data = \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y');
    $municipio = $atividade->municipio;
    $munLabel = $atividade->abrangencia_nacional
        ? 'Brasil'
        : ($municipio ? ($municipio->nome . ' / ' . ($municipio->estado->sigla ?? '')) : '—');
@endphp

<x-pdf.header title="Lista de Presença" />

<table class="info-grid">
    @if($atividade->evento)
    <tr>
        <td class="label">Ação pedagógica</td>
        <td colspan="3">{{ $atividade->evento->nome }}</td>
    </tr>
    @endif
    <tr>
        <td class="label">Momento</td>
        <td colspan="3">{{ $atividade->descricao }}</td>
    </tr>
    <tr>
        <td class="label">Data</td>
        <td>{{ $data }}</td>
        <td class="label">Horário</td>
        <td>{{ $ini }} às {{ $fim }}</td>
    </tr>
    <tr>
        <td class="label">Município</td>
        <td colspan="3">{{ $munLabel }}</td>
    </tr>
</table>

<h2 class="section-title">Participantes</h2>

@if($inscricoes->isEmpty())
    <p class="empty">Nenhum participante registrado para este momento.</p>
@else
    <table class="pdf-table">
        <thead>
            <tr>
                <th class="text-center">#</th>
                <th>Nome</th>
                <th>CPF</th>
                <th>Município</th>
                <th>Presença registrada em</th>
            </tr>
        </thead>
        <tbody>
            @foreach($inscricoes as $i => $inscricao)
                @php
                    $p = $inscricao->participante;
                    $u = $p?->user;
                    $m = $p?->municipio;
                    $munPart = $m ? ($m->nome . ' - ' . ($m->estado?->sigla ?? '')) : '—';

                    $cpfSujo = $p?->cpf ?? '';
                    $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfSujo);
                    $cpfFormatado = strlen($cpfLimpo) === 11
                        ? preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpfLimpo)
                        : ($cpfSujo ?: '—');

                    $presenca = $inscricao->presencas->first();
                    $registradoEm = $presenca?->created_at
                        ? \Carbon\Carbon::parse($presenca->created_at)->format('d/m/Y H:i')
                        : '—';
                @endphp
                <tr>
                    <td class="row-index">{{ $i + 1 }}</td>
                    <td class="td-name">
                        {{ $u->name ?? '—' }}
                        @if($inscricao->ouvinte)
                            <span class="badge-ouvinte">Ouvinte</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $cpfFormatado }}</td>
                    <td>{{ $munPart }}</td>
                    <td class="text-center">{{ $registradoEm }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="total">Total: {{ $inscricoes->count() }} participante(s)</div>
@endif
@endsection
