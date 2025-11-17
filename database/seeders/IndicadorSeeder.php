<?php

namespace Database\Seeders;

use App\Models\Dimensao;
use App\Models\Indicador;
use Illuminate\Database\Seeder;

class IndicadorSeeder extends Seeder
{
    public function run(): void
    {
        $dimensoes = Dimensao::pluck('id', 'descricao');

        $indicadores = [
            'Planejamento pedagógico' => [
                'Clareza dos objetivos da atividade',
                'Adequação do conteúdo ao público',
            ],
            'Metodologias de ensino' => [
                'Diversidade de estratégias pedagógicas',
                'Relação teoria-prática',
            ],
            'Participação e engajamento' => [
                'Interação entre participantes',
                'Motivação para continuar participando',
            ],
            'Gestão e logística' => [
                'Comunicação prévia do evento',
                'Infraestrutura e apoio técnico',
            ],
        ];

        foreach ($indicadores as $descricaoDimensao => $listaIndicadores) {
            $dimensaoId = $dimensoes[$descricaoDimensao] ?? null;

            if (! $dimensaoId) {
                continue;
            }

            foreach ($listaIndicadores as $descricaoIndicador) {
                Indicador::firstOrCreate(
                    [
                        'dimensao_id' => $dimensaoId,
                        'descricao'   => $descricaoIndicador,
                    ]
                );
            }
        }
    }
}
