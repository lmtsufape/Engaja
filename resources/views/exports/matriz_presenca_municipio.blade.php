<table>
    <thead>
        <tr>
            <th colspan="{{ 5 + $atividades->count() + 3 }}" style="font-size: 14px; font-weight: bold; text-align: center;">
                Matriz de Frequência - Município: {{ $municipioNome }}
            </th>
        </tr>
        <tr>
            <th style="width: 35px;">Nome do Participante</th>
            <th style="width: 12px;">ID do usuário</th>
            <th style="width: 20px;">CPF</th>
            <th style="width: 35px;">E-mail</th>
            <th style="width: 15px;">Vínculo</th>

            @foreach($atividades as $atividade)
                @php
                    $data = $atividade->dia ? \Carbon\Carbon::parse($atividade->dia)->format('d/m/Y') : '';
                    $hora = $atividade->hora_inicio ? substr($atividade->hora_inicio, 0, 5) : '';
                    $header = $atividade->descricao;
                    if($data) $header .= "\n($data $hora)";
                @endphp
                <th style="width: 25px; text-align: center;">{!! nl2br(e($header)) !!}</th>
            @endforeach

            <th style="width: 15px; text-align: center;">Total Presente</th>
            <th style="width: 15px; text-align: center;">Total Ausente</th>
            <th style="width: 15px; text-align: center;">Frequência %</th>
        </tr>
    </thead>
    <tbody>
        @foreach($participantes as $participante)
            <tr>
                <td>{{ $participante['nome'] }}</td>
                <td>{{ $participante['usuario_id'] }}</td>
                <td>{{ $participante['cpf'] }}</td>
                <td>{{ $participante['email'] }}</td>
                <td>{{ $participante['vinculo'] }}</td>

                @foreach($atividades as $atividade)
                    @php
                        $status = $participante['momentos'][$atividade->id] ?? 'Não Inscrito';
                    @endphp
                    <td style="text-align: center;">{{ $status }}</td>
                @endforeach

                @php
                    $total = $participante['presente_count'] + $participante['ausente_count'];
                    $freq = $total > 0 ? round(($participante['presente_count'] / $total) * 100, 1) . '%' : '-';
                @endphp
                <td style="text-align: center;">{{ $participante['presente_count'] }}</td>
                <td style="text-align: center;">{{ $participante['ausente_count'] }}</td>
                <td style="text-align: center;">{{ $freq }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
