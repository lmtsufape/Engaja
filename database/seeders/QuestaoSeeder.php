<?php

namespace Database\Seeders;

use App\Models\Escala;
use App\Models\Indicador;
use App\Models\Questao;
use App\Models\Evidencia;
use Illuminate\Database\Seeder;

class QuestaoSeeder extends Seeder
{
    public function run(): void
    {
        $indicadores = Indicador::pluck('id', 'descricao');
        $escalas = Escala::pluck('id', 'descricao');

        $questoes = [
            [
                'indicador' => 'Clareza dos objetivos da atividade',
                'tipo'      => 'escala',
                'escala'    => 'Nível de concordância',
                'texto'     => 'Os objetivos da atividade foram apresentados com clareza?',
                'fixa'      => true,
            ],
            [
                'indicador' => 'Adequação do conteúdo ao público',
                'tipo'      => 'escala',
                'escala'    => 'Avaliação de satisfação',
                'texto'     => 'O conteúdo abordado dialogou com a sua realidade?',
                'fixa'      => false,
            ],
            [
                'indicador' => 'Diversidade de estratégias pedagógicas',
                'tipo'      => 'texto',
                'escala'    => null,
                'texto'     => 'Quais metodologias mais contribuiram para a aprendizagem?',
                'fixa'      => false,
            ],
            [
                'indicador' => 'Relação teoria-prática',
                'tipo'      => 'boolean',
                'escala'    => null,
                'texto'     => 'Houve atividades práticas relacionadas aos conceitos apresentados?',
                'fixa'      => false,
            ],
            [
                'indicador' => 'Interação entre participantes',
                'tipo'      => 'escala',
                'escala'    => 'Nível de concordância',
                'texto'     => 'As propostas possibilitaram interação entre participantes?',
                'fixa'      => false,
            ],
            [
                'indicador' => 'Motivação para continuar participando',
                'tipo'      => 'numero',
                'escala'    => null,
                'texto'     => 'Em uma escala de 0 a 10, qual a chance de participar de novas ações?',
                'fixa'      => false,
            ],
            [
                'indicador' => 'Comunicação prévia do evento',
                'tipo'      => 'escala',
                'escala'    => 'Avaliação de satisfação',
                'texto'     => 'A comunicação antes do evento foi suficiente?',
                'fixa'      => false,
            ],
            [
                'indicador' => 'Infraestrutura e apoio técnico',
                'tipo'      => 'texto',
                'escala'    => null,
                'texto'     => 'Quais melhorias você sugere para a infraestrutura do evento?',
                'fixa'      => false,
            ],
        ];

        foreach ($questoes as $questao) {
            $indicadorId = $indicadores[$questao['indicador']] ?? null;

            if (! $indicadorId) {
                continue;
            }

            $escalaId = null;
            if ($questao['tipo'] === 'escala' && $questao['escala']) {
                $escalaId = $escalas[$questao['escala']] ?? null;
            }

            $evidenciaId = null;
            if ($indicadorId) {
                $evidenciaId = Evidencia::firstOrCreate([
                    'indicador_id' => $indicadorId,
                    'descricao'    => 'Evidência padrão',
                ])->id;
            }

            Questao::updateOrCreate(
                [
                    'template_avaliacao_id' => null,
                    'indicador_id'          => $indicadorId,
                    'evidencia_id'          => $evidenciaId,
                    'texto'                 => $questao['texto'],
                ],
                [
                    'tipo'      => $questao['tipo'],
                    'escala_id' => $escalaId,
                    'fixa'      => $questao['fixa'],
                    'ordem'     => null,
                ]
            );
        }
    }
}
