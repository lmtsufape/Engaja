{{-- resources/views/dashboard_pdf.blade.php --}}
@php
    use Carbon\Carbon;
@endphp
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Dashboard - Lista de Presencas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color:#222; }
        .pdf-header { display:flex; align-items:center; gap:16px; padding:12px 16px; border:1px solid #edd7fc; border-radius:6px; background:#f9f4ff; margin-bottom:18px; }
        .pdf-header img { height:48px; }
        .header-text h1 { font-size:18px; margin:0; color:#681170; }
        .header-text .subtitle { font-size:13px; font-weight:600; letter-spacing:0.3px; text-transform:uppercase; margin-top:4px; color:#681170; }
        .meta { font-size: 11px; color:#555; margin-bottom: 12px; }
        .header-text .meta { margin:6px 0 0; }
        .atividade-card { border:1px solid #edd7fc; border-radius:6px; padding:12px 16px; margin-bottom:18px; background:#fff; }
        .atividade-header { display:flex; flex-wrap:wrap; gap:12px 24px; margin-bottom:12px; }
        .atividade-header .item { min-width:120px; max-width:240px; }
        .atividade-header .label { font-size:10px; text-transform:uppercase; letter-spacing:0.4px; color:#6b7a99; margin-bottom:2px; }
        .atividade-header .value { font-size:13px; font-weight:600; color:#14213d; word-break:break-word; }
        .metrics { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:14px; }
        .metric { border:1px solid #edd7fc; background:#f9f4ff; border-radius:4px; padding:6px 10px; min-width:110px; }
        .metric-label { font-size:10px; text-transform:uppercase; letter-spacing:0.4px; color:#6b7a99; display:block; }
        .metric-value { font-size:16px; font-weight:700; color:#681170; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; page-break-inside:auto; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; vertical-align: top; }
        th { background: #f0f0f0; }
        .subtable th { background: #fafafa; }
        .muted { color:#777; }
        .mb-6 { margin-bottom: 6px; }
        .mb-10 { margin-bottom: 10px; }
        .fw-bold { font-weight: bold; }
        .text-right { text-align: right; }
        .small { font-size: 11px; }
        .section-title { background:#f7f7f7; padding:6px 8px; border:1px solid #ccc; margin:10px 0 6px; }
        .empty-state { border:1px dashed #d0d7e6; padding:16px; border-radius:6px; text-align:center; color:#6b7a99; margin-top:20px; }
        .filters-applied { border:1px dashed #d8c3f7; background:#fcfaff; padding:10px 12px; border-radius:6px; margin-bottom:18px; font-size:11px; }
        .filters-applied .title { display:block; font-weight:700; color:#681170; margin-bottom:6px; text-transform:uppercase; letter-spacing:0.4px; font-size:10px; }
        .filters-applied .chip { display:inline-block; margin:0 6px 6px 0; padding:4px 8px; border-radius:4px; border:1px solid #edd7fc; background:#fff; color:#4a1768; font-size:11px; }
        .filters-applied .chip strong { margin-right:4px; }
    </style>
</head>
<body>
    <header class="pdf-header">
        <div class="header-logo">
            <img src="{{ public_path('images/engaja-bg.png') }}" alt="Logo Engaja">
        </div>
        <div class="header-text">
            <div class="subtitle">Dashboard &middot; Lista de Presen&ccedil;as</div>
            <div class="meta">
                Gerado em {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </header>

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
            $inscricoes = collect($a->inscricoes ?? []);
            $presentesIds = $presentes->pluck('inscricao_id')->filter()->unique();
            $ausentes = $inscricoes->filter(fn($insc) => !$presentesIds->contains($insc->id))->values();
            $inscritosCount = $inscricoes->count();
            $presentesCount = $presentesIds->count();
            $ausentesCount = $ausentes->count();
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

            <div class="section-title fw-bold">Presentes</div>
            @if($presentes->isEmpty())
                <div class="small muted mb-10">Nenhum presente listado.</div>
            @else
                <table class="subtable">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Nome</th>
                            <th style="width: 30%;">E-mail</th>
                            <th style="width: 18%;">CPF</th>
                            <th style="width: 17%;">Tag</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($presentes as $p)
                        @php
                            $insc = optional($p->inscricao);
                            $part = optional($insc->participante);
                            $user = optional($part->user);
                        @endphp
                        <tr>
                            <td>{{ $user->name ?? ('Participante #'.$part->id) }}</td>
                            <td>{{ $user->email ?? '-' }}</td>
                            <td>{{ $part->cpf ?: '-' }}</td>
                            <td>{{ $part->tag ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif

            <div class="section-title fw-bold">Ausentes</div>
            @if($ausentes->isEmpty())
                <div class="small muted mb-10">Nenhum ausente listado.</div>
            @else
                <table class="subtable">
                    <thead>
                        <tr>
                            <th style="width: 35%;">Nome</th>
                            <th style="width: 30%;">E-mail</th>
                            <th style="width: 18%;">CPF</th>
                            <th style="width: 17%;">Tag</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($ausentes as $insc)
                        @php
                            $part = optional($insc->participante);
                            $user = optional($part->user);
                        @endphp
                        <tr>
                            <td>{{ $user->name ?? ('Participante #'.$part->id) }}</td>
                            <td>{{ $user->email ?? '-' }}</td>
                            <td>{{ $part->cpf ?: '-' }}</td>
                            <td>{{ $part->tag ?: '-' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    @empty
        <div class="empty-state">Nenhuma atividade encontrada.</div>
    @endforelse
</body>
</html>
