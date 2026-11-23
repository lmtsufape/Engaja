<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Limiares do Painel Gerencial
    |--------------------------------------------------------------------------
    |
    | Parâmetros que definem os recortes de acompanhamento exibidos no painel
    | gerencial de quantitativos. Mantidos em config (lendo env apenas aqui)
    | para que os call sites usem config(...) sem 2º argumento.
    |
    */

    // Percentual mínimo de realizado (presentes/previstos) abaixo do qual um
    // município é considerado de "baixo engajamento".
    'engajamento_minimo_pct' => (float) env('PAINEL_ENGAJAMENTO_MINIMO_PCT', 50),

    // Número mínimo de ausências para um participante entrar na lista de
    // "recorrência de ausência".
    'recorrencia_ausencia_minima' => (int) env('PAINEL_RECORRENCIA_AUSENCIA_MINIMA', 2),

    // Mapeamento dos valores da coluna eventos.modalidade para os baldes de horas.
    'modalidade_buckets' => [
        'Presencial' => 'presencial',
        'Online' => 'ead',
        'Híbrido' => 'hibrido',
    ],
];
