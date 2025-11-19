<div {{ $attributes->merge(['class' => '']) }}>
    <h1 class="h4 fw-bold text-engaja mb-1">{{ $atividade->descricao ?? 'Momento' }}</h1>

    @php
        use Carbon\Carbon;

        $dia = Carbon::parse($atividade->dia)->locale('pt_BR')->translatedFormat('l, d \d\e F \d\e Y');

        $inicio = Carbon::parse($atividade->dia . ' ' . $atividade->hora_inicio);
        $fim = $atividade->hora_fim ? Carbon::parse($atividade->dia . ' ' . $atividade->hora_fim) : null;

        if ($fim && $fim->lessThanOrEqualTo($inicio)) {
            $fim->addDay();
        }

        $duracaoLabel = null;
        if ($fim) {
            $mins = $inicio->diffInMinutes($fim, false);
            if ($mins < 0) {
                $mins += 24 * 60;
            }
            $h = intdiv($mins, 60);
            $m = $mins % 60;
            $duracaoLabel = $h > 0 ? $h . 'h' . ($m ? ' ' . $m . 'min' : '') : $m . 'min';
        }
    @endphp

    @if ($fim)
        <p class="text-muted mb-1">
            {{ $dia }} • {{ $inicio->format('H:i') }} a {{ $fim->format('H:i') }}
            <br><span class="ms-1">Duração: {{ $duracaoLabel }}</span>
        </p>
    @else
        <p class="text-muted mb-1">
            {{ $dia }} • {{ $inicio->format('H:i') }}
        </p>
    @endif

    @if ($atividade->municipio)
        <p class="text-muted mb-1">Município: {{ $atividade->municipio->nome_com_estado }}</p>
    @endif

    @if ($atividade->local)
        <p class="text-muted mb-1">Local: {{ $atividade->local }}</p>
    @endif

    @if(!is_null($atividade->publico_esperado))
        <p class="text-muted mb-1">
            Público esperado: {{ number_format($atividade->publico_esperado, 0, ',', '.') }}
        </p>
    @endif

    @php
        $carga = $atividade->carga_horaria;
        $cargaFormatada = !is_null($carga) ? number_format($carga, 0, ',', '.') : null;
    @endphp
    @if($cargaFormatada)
        <p class="text-muted mb-1">Carga horária: {{ $cargaFormatada }}h</p>
    @endif
</div>
