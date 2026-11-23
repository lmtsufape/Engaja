<table>
    <thead>
        <tr>
            <th colspan="3" style="text-align: center; font-size: 14px; font-weight: bold;">
                Matriz de Frequência - {{ $evento->nome }}
            </th>
        </tr>
        <tr>
            <th colspan="3" style="text-align: center; font-style: italic;">
                Gerado em {{ now()->format('d/m/Y H:i') }}
            </th>
        </tr>
        <tr>
            <th>Município</th>
            <th style="text-align: center;">Total de Momentos</th>
            <th style="text-align: center;">Participantes Únicos</th>
        </tr>
    </thead>
    <tbody>
        @foreach($municipiosResumo as $resumo)
            <tr>
                <td>{{ $resumo['nome'] }}</td>
                <td style="text-align: center;">{{ $resumo['total_momentos'] }}</td>
                <td style="text-align: center;">{{ $resumo['total_participantes_unicos'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
