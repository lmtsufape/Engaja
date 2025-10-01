{{-- resources/views/dashboard_pdf.blade.php --}}
@php
    use Carbon\Carbon;
@endphp
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Dashboard - Lista de Presenças</title>
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
    <h1>Dashboard — Lista de Presenças</h1>
    <div class="meta">
        Gerado em {{ now()->format('d/m/Y H:i') }}
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Data</th>
                <th style="width: 10%;">Hora</th>
                <th>Momento</th>
                <th style="width: 30%;">Ação pedagógica</th>
                <th style="width: 12%;">Presentes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($atividades as $a)
                @php
                    $data = $a->dia ? Carbon::parse($a->dia)->format('d/m/Y') : '—';
                    $hora = $a->hora_inicio ? substr($a->hora_inicio, 0, 5) : '—';
                    $presentes = collect($a->presencas ?? []);
                @endphp
                <tr>
                    <td>{{ $data }}</td>
                    <td>{{ $hora }}</td>
                    <td>{{ $a->descricao ?? 'Momento' }}</td>
                    <td>{{ $a->evento_nome ?? optional($a->evento)->nome ?? '—' }}</td>
                    <td class="text-right fw-bold">{{ $a->presentes_count }}</td>
                </tr>
                <tr>
                    <td colspan="5">
                        <div class="section-title fw-bold">Presentes</div>
                        @if($presentes->isEmpty())
                            <div class="small muted mb-10">Nenhum presente listado.</div>
                        @else
                            <table class="subtable" style="margin-top:6px; width:100%;">
                                <thead>
                                    <tr>
                                        <th style="width: 55%;">Nome</th>
                                        <th style="width: 45%;">E-mail</th>
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
                                        <td>{{ $user->email ?? '—' }}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted">Nenhuma atividade encontrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
