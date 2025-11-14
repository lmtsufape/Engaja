<?php

namespace Database\Seeders;

use App\Models\Questao;
use App\Models\TemplateAvaliacao;
use App\Models\Evidencia;
use Illuminate\Database\Seeder;

class TemplateAvaliacaoSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            'Avaliação padrão de atividades' => [
                'descricao' => 'Coleta percepções gerais sobre a realização das atividades.',
                'questoes'  => [
                    'Os objetivos da atividade foram apresentados com clareza?',
                    'O conteúdo abordado dialogou com a sua realidade?',
                    'As propostas possibilitaram interação entre participantes?',
                    'Em uma escala de 0 a 10, qual a chance de participar de novas ações?',
                    'Quais melhorias você sugere para a infraestrutura do evento?',
                ],
            ],
            'Avaliação de logística e comunicação' => [
                'descricao' => 'Foco em aspectos de apoio, comunicação e estrutura das ações.',
                'questoes'  => [
                    'A comunicação antes do evento foi suficiente?',
                    'Quais melhorias você sugere para a infraestrutura do evento?',
                    'Houve atividades práticas relacionadas aos conceitos apresentados?',
                ],
            ],
        ];

        $questoesBase = Questao::whereNull('template_avaliacao_id')
            ->get()
            ->keyBy('texto');

        foreach ($templates as $nome => $dados) {
            $template = TemplateAvaliacao::updateOrCreate(
                ['nome' => $nome],
                ['descricao' => $dados['descricao']]
            );

            $ordem = 1;
            $idsMantidos = [];

            foreach ($dados['questoes'] as $textoQuestao) {
                $questaoBase = $questoesBase->get($textoQuestao)
                    ?? Questao::whereNull('template_avaliacao_id')
                        ->where('texto', $textoQuestao)
                        ->first();

                if (! $questaoBase) {
                    continue;
                }

                $evidenciaId = null;
                if ($questaoBase->indicador_id) {
                    $evidenciaId = Evidencia::firstOrCreate([
                        'indicador_id' => $questaoBase->indicador_id,
                        'descricao'    => 'Evidência padrão',
                    ])->id;
                }

                $questao = Questao::updateOrCreate(
                    [
                        'template_avaliacao_id' => $template->id,
                        'indicador_id'          => $questaoBase->indicador_id,
                        'evidencia_id'          => $evidenciaId,
                        'texto'                 => $questaoBase->texto,
                    ],
                    [
                        'escala_id' => $questaoBase->tipo === 'escala' ? $questaoBase->escala_id : null,
                        'tipo'      => $questaoBase->tipo,
                        'fixa'      => $questaoBase->fixa,
                    ]
                );

                $questao->ordem = $ordem++;
                $questao->save();

                $idsMantidos[] = $questao->id;
            }

            if (! empty($idsMantidos)) {
                Questao::where('template_avaliacao_id', $template->id)
                    ->whereNotIn('id', $idsMantidos)
                    ->delete();
            }
        }
    }
}
