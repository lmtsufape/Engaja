{{-- resources/views/dashboard_pdf.blade.php --}}
@php use Carbon\Carbon; @endphp
@extends('layouts.pdf-alfa-eja')

@section('title', 'Dashboard - Lista de Presenças')

@section('styles')
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#222; }
    .atividade-card { border:1px solid #edd7fc; border-radius:6px; padding:12px 16px; margin-bottom:18px; background:#fff; }
    .atividade-header { display:flex; flex-wrap:wrap; gap:12px 24px; margin-bottom:12px; }
    .atividade-header .item { min-width:120px; max-width:240px; }
    .atividade-header .label { font-size:10px; text-transform:uppercase; letter-spacing:0.4px; color:#6b7a99; margin-bottom:2px; }
    .atividade-header .value { font-size:13px; font-weight:600; color:#14213d; word-break:break-word; }
    .metrics { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
    .metric { border:1px solid #edd7fc; background:#f9f4ff; border-radius:4px; padding:6px 10px; min-width:110px; }
    .metric-label { font-size:10px; text-transform:uppercase; letter-spacing:0.4px; color:#6b7a99; display:block; }
    .metric-value { font-size:16px; font-weight:700; color:#421944; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 12px; page-break-inside:auto; }
    thead { display: table-header-group; }
    th { background: #421944; color: #fff; padding: 6px 8px; text-align: left; vertical-align: top; }
    td { border-bottom: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
    tbody tr:nth-child(even) td { background: #f9fafb; }
    .muted { color:#777; }
    .mb-6 { margin-bottom: 6px; }
    .mb-10 { margin-bottom: 10px; }
    .fw-bold { font-weight: bold; }
    .text-right { text-align: right; }
    .small { font-size: 11px; }
    .section-title { background:#f7f7f7; padding:6px 8px; border:1px solid #ccc; margin:10px 0 6px; }
    .empty-state { border:1px dashed #d0d7e6; padding:16px; border-radius:6px; text-align:center; color:#6b7a99; margin-top:20px; }
    .filters-applied { border:1px dashed #d8c3f7; background:#fcfaff; padding:10px 12px; border-radius:6px; margin-bottom:18px; font-size:11px; }
    .filters-applied .title { display:block; font-weight:700; color:#421944; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.4px; font-size:10px; }
    .filters-applied .chip { display:inline-block; margin:0 6px 6px 0; padding:4px 8px; border-radius:4px; border:1px solid #edd7fc; background:#fff; color:#4a1768; font-size:11px; }
    .filters-applied .chip strong { margin-right:4px; }
    .badge { display:inline-block; padding:4px 6px; border-radius:4px; font-size:10px; font-weight:bold; }
    .bg-success { background-color: #198754; color: #fff; }
    .bg-info { background-color: #0dcaf0; color: #000; }
    .bg-warning { background-color: #ffc107; color: #000; }
@endsection

@section('content')
    @php
        $totalMomentos = is_countable($atividades) ? count($atividades) : $atividades->count();
    @endphp
    <x-pdf.header title="Lista de Presenças" subtitle="Dashboard de presenças">
        Exibindo <strong>{{ $totalMomentos }}</strong> {{ $totalMomentos === 1 ? 'momento' : 'momentos' }}.
    </x-pdf.header>

    @if(!empty($truncado))
        <div class="filters-applied" style="border-color:#f0c36d; background:#fff8e8;">
            <span class="title" style="color:#8a6d1a;">Resultado parcial</span>
            <div>
                Exibindo as primeiras <strong>{{ number_format($maxAtividades, 0, ',', '.') }}</strong>
                de <strong>{{ number_format($totalAtividades, 0, ',', '.') }}</strong> atividades.
                Refine os filtros (ação pedagógica, município, momento ou período) para gerar um relatório completo.
            </div>
        </div>
    @endif

    @if(!empty($filtroResumo ?? []))
        <div class="filters-applied">
            <span class="title">Filtros aplicados</span>
            <div>
                @foreach($filtroResumo as $label => $value)
                    <span class="chip"><strong>{{ $label }}:</strong> {{ $value }}</span>
                @endforeach
            </div>
        </div>
    @endif

    @forelse($atividades as $index => $a)
        @php
            $data = $a->dia ? Carbon::parse($a->dia)->format('d/m/Y') : '-';
            $hora = $a->hora_inicio ? substr($a->hora_inicio, 0, 5) : '-';
            $presentes = collect($a->presencas ?? []);
            $presentesIds = $presentes->pluck('inscricao_id')->filter()->unique();
            $inscricoes = collect($a->inscricoes ?? [])->sortBy(fn($i) => strtolower($i->participante?->user?->name ?? ''))->values();
            $inscritosCount = $inscricoes->count();
            $presentesCount = $presentesIds->count();
            $ausentesCount = max($inscritosCount - $presentesCount, 0);
        @endphp

        <section class="atividade-card">
            <div class="atividade-header">
                <div class="item">
                    <div class="label">Data</div>
                    <div class="value">{{ $data }}</div>
                </div>
                <div class="item">
                    <div class="label">Hora</div>
                    <div class="value">{{ $hora }}</div>
                </div>
                <div class="item">
                    <div class="label">Momento</div>
                    <div class="value">{{ $a->descricao ?? 'Momento' }}</div>
                </div>
                <div class="item">
                    <div class="label">Municipio</div>
                    <div class="value">{{ $a->municipio?->nome_com_estado ?? '-' }}</div>
                </div>
                <div class="item" style="flex:1; min-width:180px;">
                    <div class="label">Acao pedagogica</div>
                    <div class="value">{{ $a->evento_nome ?? optional($a->evento)->nome ?? '-' }}</div>
                </div>
            </div>

            <div class="metrics">
                <div class="metric">
                    <span class="metric-label">Inscritos</span>
                    <span class="metric-value">{{ $inscritosCount }}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Presentes</span>
                    <span class="metric-value">{{ $presentesCount }}</span>
                </div>
                <div class="metric">
                    <span class="metric-label">Ausentes</span>
                    <span class="metric-value">{{ $ausentesCount }}</span>
                </div>
            </div>

            <div class="section-title fw-bold">Participantes</div>
            @if($inscricoes->isEmpty())
                <div class="small muted mb-10">Nenhum participante listado.</div>
            @else
                <table class="subtable">
                    <thead>
                        <tr>
                            <th style="width: 29%;">Nome</th>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 23%;">E-mail</th>
                            <th style="width: 15%;">CPF</th>
                            <th style="width: 13%;">Tag</th>
                            <th style="width: 12%;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($inscricoes as $insc)
                        @php
                            $part = optional($insc->participante);
                            $user = optional($part->user);
                            $isPresente = $presentesIds->contains($insc->id);
                            
                            if ($isPresente) {
                                if ($insc->ouvinte ?? false) {
                                    $statusLabel = 'Ouvinte';
                                    $statusClass = 'bg-info';
                                } else {
                                    $statusLabel = 'Presente';
                                    $statusClass = 'bg-success';
                                }
                            } else {
                                $statusLabel = 'Ausente';
                                $statusClass = 'bg-warning';
                            }
                        @endphp
                        <tr>
                            <td>{{ $user->name ?? ('Participante #'.$part->id) }}</td>
                            <td>{{ $user->id ?? '-' }}</td>
                            <td>{{ $user->email ?? '-' }}</td>
                            <td>{{ $part->cpf ?: '-' }}</td>
                            <td>{{ $part->tag ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @empty
        <div class="empty-state">Nenhuma atividade encontrada.</div>
    @endforelse
@endsection
