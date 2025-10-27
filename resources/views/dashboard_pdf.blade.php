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
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { font-size: 11px; color:#555; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; vertical-align: top; }
        th { background: #f0f0f0; }
        .subtable th { background: #fafafa; }
        .muted { color:#777; }
        .mb-6 { margin-bottom: 6px; }
        .mb-10 { margin-bottom: 10px; }
        .fw-bold { font-weight: bold; }
        .text-right { text-align: right; }
        .small { font-size: 11px; }
        .section-title { background:#f7f7f7; padding:6px 8px; border:1px solid #ccc; margin-top:10px; }
    </style>
</head>
<body>
    <h1>Dashboard - Lista de Presencas</h1>
    <div class="meta">
        Gerado em {{ now()->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Data</th>
                <th style="width: 10%;">Hora</th>
                <th>Momento</th>
                <th style="width: 20%;">Municipio</th>
                <th style="width: 24%;">Acao pedagogica</th>
                <th style="width: 8%;" class="text-right">Inscritos</th>
                <th style="width: 8%;" class="text-right">Presentes</th>
                <th style="width: 8%;" class="text-right">Ausentes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($atividades as $a)
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
                <tr>
                    <td>{{ $data }}</td>
                    <td>{{ $hora }}</td>
                    <td>{{ $a->descricao ?? 'Momento' }}</td>
                    <td>{{ $a->municipio?->nome_com_estado ?? '-' }}</td>
                    <td>{{ $a->evento_nome ?? optional($a->evento)->nome ?? '-' }}</td>
                    <td class="text-right fw-bold">{{ $inscritosCount }}</td>
                    <td class="text-right fw-bold">{{ $presentesCount }}</td>
                    <td class="text-right fw-bold">{{ $ausentesCount }}</td>
                </tr>
                <tr>
                    <td colspan="8">
                        <div class="section-title fw-bold">Resumo: inscritos {{ $inscritosCount }}, presentes {{ $presentesCount }}, ausentes {{ $ausentesCount }}</div>
                        <div class="section-title fw-bold">Presentes</div>
                        @if($presentes->isEmpty())
                            <div class="small muted mb-10">Nenhum presente listado.</div>
                        @else
                            <table class="subtable" style="margin-top:6px; width:100%;">
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
                        <div class="section-title fw-bold" style="margin-top:10px;">Ausentes</div>
                        @if($ausentes->isEmpty())
                            <div class="small muted mb-10">Nenhum ausente listado.</div>
                        @else
                            <table class="subtable" style="margin-top:6px; width:100%;">
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
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="muted">Nenhuma atividade encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
