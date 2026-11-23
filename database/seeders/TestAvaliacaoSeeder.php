<?php

namespace Database\Seeders;

use App\Models\Atividade;
use App\Models\Avaliacao;
use App\Models\AvaliacaoQuestao;
use App\Models\Dimensao;
use App\Models\Escala;
use App\Models\Evento;
use App\Models\Evidencia;
use App\Models\Indicador;
use App\Models\Inscricao;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\Presenca;
use App\Models\Questao;
use App\Models\RespostaAvaliacao;
use App\Models\SubmissaoAvaliacao;
use App\Models\TemplateAvaliacao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TestAvaliacaoSeeder extends Seeder
{
    public function run(): void
    {
        // ─────────────────────────────────────────────
        // 1. Estrutura de avaliação
        // ─────────────────────────────────────────────
        $dimensao = Dimensao::create([
            'descricao' => 'Dimensão Teste - Qualidade Pedagógica',
        ]);

        $indicador1 = Indicador::create([
            'dimensao_id' => $dimensao->id,
            'descricao'   => 'Indicador Teste 1 - Engajamento',
        ]);

        $indicador2 = Indicador::create([
            'dimensao_id' => $dimensao->id,
            'descricao'   => 'Indicador Teste 2 - Compreensão',
        ]);

        $evidencia1 = Evidencia::create(['descricao' => 'Evidência Teste 1', 'indicador_id' => $indicador1->id]);
        $evidencia2 = Evidencia::create(['descricao' => 'Evidência Teste 2', 'indicador_id' => $indicador2->id]);

        $escala = Escala::create([
            'descricao' => 'Escala Teste (1 a 5)',
            'opcao1'    => 'Péssimo',
            'opcao2'    => 'Ruim',
            'opcao3'    => 'Razoável',
            'opcao4'    => 'Bom',
            'opcao5'    => 'Excelente',
        ]);

        // ─────────────────────────────────────────────
        // 2. Template de Avaliação + Questões
        // ─────────────────────────────────────────────
        $template = TemplateAvaliacao::create([
            'nome'      => 'Template de Avaliação Teste ' . date('d/m/Y H:i'),
            'descricao' => 'Template criado via Seeder para testes de consolidação.',
        ]);

        $questoesData = [
            [
                'texto'        => 'Como você avalia o material do curso?',
                'tipo'         => 'escala',
                'indicador_id' => $indicador1->id,
                'escala_id'    => $escala->id,
                'evidencia_id' => $evidencia1->id,
                'ordem'        => 1,
            ],
            [
                'texto'        => 'O encontro me motivou a estudar.',
                'tipo'         => 'boolean',
                'indicador_id' => $indicador1->id,
                'escala_id'    => null,
                'evidencia_id' => null,
                'ordem'        => 2,
            ],
            [
                'texto'           => 'Qual o seu grau de compreensão dos temas?',
                'tipo'            => 'unica',
                'opcoes_resposta' => ['Alto', 'Médio', 'Baixo'],
                'indicador_id'    => $indicador2->id,
                'escala_id'       => null,
                'evidencia_id'    => $evidencia2->id,
                'ordem'           => 3,
            ],
            [
                'texto'           => 'Quais temas você mais gostou?',
                'tipo'            => 'multipla',
                'opcoes_resposta' => ['Metodologia', 'Didática', 'Prática', 'Teoria'],
                'indicador_id'    => $indicador2->id,
                'escala_id'       => null,
                'evidencia_id'    => null,
                'ordem'           => 4,
            ],
            [
                'texto'        => 'Deixe um comentário aberto:',
                'tipo'         => 'texto',
                'indicador_id' => null,
                'escala_id'    => null,
                'evidencia_id' => null,
                'ordem'        => 5,
            ],
        ];

        $templateQuestoes = collect();
        foreach ($questoesData as $qData) {
            $qData['template_avaliacao_id'] = $template->id;
            $templateQuestoes->push(Questao::create($qData));
        }

        // ─────────────────────────────────────────────
        // 3. Municípios existentes agrupados por região
        // IDs reais do banco: Norte (1-4), NE-I (5-9), NE-II (10-16)
        // ─────────────────────────────────────────────
        $municipiosPorMomento = [
            1 => [1, 2, 3, 4],          // Norte: Oiapoque, Coari, Carauari, Belém
            2 => [5, 6, 7, 8, 9],       // Nordeste I: Caucaia, Fortaleza, Icapuí, Alto do Rodrigues, Porto do Mangue
            3 => [10, 11, 12, 13, 14],  // Nordeste II: Araçás, São Francisco do Conde, Conde, Ipojuca, Cabo de Santo Agostinho
        ];

        // ─────────────────────────────────────────────
        // 4. Evento (Ação)
        // ─────────────────────────────────────────────
        $user   = User::first() ?? User::factory()->create();
        $evento = Evento::create([
            'nome'       => 'Ação de Teste Completa Consolidação ' . time(),
            'tipo'       => 'Formação',
            'acao_geral' => '1',
            'subacao'    => '1.1',
            'data_inicio' => now()->subDays(10),
            'data_fim'    => now()->addDays(10),
            'modalidade'  => 'Presencial',
            'user_id'     => $user->id,
        ]);

        // ─────────────────────────────────────────────
        // 5. Participantes — um por município presente nos 3 momentos
        //    (usamos os 14 municípios = 14 participantes)
        // ─────────────────────────────────────────────
        $todosMunicipios = collect(array_merge(
            $municipiosPorMomento[1],
            $municipiosPorMomento[2],
            $municipiosPorMomento[3]
        ))->unique()->values();

        $participantes = collect();
        foreach ($todosMunicipios as $munId) {
            $mun = Municipio::find($munId);
            for ($p = 0; $p < 5; $p++) {
                $participantes->push([
                    'municipio_id' => $munId,
                    'participante' => Participante::create([
                        'user_id'           => $user->id,
                        'municipio_id'      => $munId,
                        'cpf'               => str_pad(rand(10000000000, 99999999999), 11, '0', STR_PAD_LEFT),
                        'telefone'          => '8599' . rand(10000000, 99999999),
                        'escola_unidade'    => 'EMEF ' . ($mun->nome ?? 'Teste'),
                        'tipo_organizacao'  => 'Pública',
                        'tag'               => Participante::TAG_REDE_ENSINO,
                    ]),
                ]);
            }
        }

        // ─────────────────────────────────────────────
        // 6. Atividades (Momentos) + Avaliações + Respostas
        // ─────────────────────────────────────────────
        for ($i = 1; $i <= 3; $i++) {
            $munIds    = $municipiosPorMomento[$i];
            $municipios = Municipio::whereIn('id', $munIds)->get();

            $atividade = Atividade::create([
                'evento_id'   => $evento->id,
                'descricao'   => "Momento Teste {$i}",
                'dia'         => now()->subDays(10 - $i * 3)->format('Y-m-d'),
                'hora_inicio' => '14:00',
                'hora_fim'    => '18:00',
            ]);

            // Vincular os municípios deste momento à atividade
            $atividade->municipios()->sync($munIds);

            // Criar avaliação
            $avaliacao = Avaliacao::create([
                'atividade_id'         => $atividade->id,
                'template_avaliacao_id' => $template->id,
                'formulario_aberto'    => true,
            ]);

            // Clonar questões do template → AvaliacaoQuestao
            $avaliacaoQuestoes = collect();
            foreach ($templateQuestoes as $q) {
                $texto = $q->texto;
                // Simular variação de texto por módulo (cenário de duplicação)
                if ($i === 2 && $q->tipo === 'escala') {
                    $texto = str_replace('do curso', 'do Módulo 2', $texto);
                } elseif ($i === 3 && $q->tipo === 'escala') {
                    $texto = str_replace('do curso', 'do Módulo 3', $texto);
                }

                $avaliacaoQuestoes->push(AvaliacaoQuestao::create([
                    'avaliacao_id'  => $avaliacao->id,
                    'questao_id'    => $q->id,
                    'indicador_id'  => $q->indicador_id,
                    'escala_id'     => $q->escala_id,
                    'evidencia_id'  => $q->evidencia_id,
                    'texto'         => $texto,
                    'tipo'          => $q->tipo,
                    'opcoes_resposta' => $q->opcoes_resposta,
                    'ordem'         => $q->ordem,
                ]));
            }

            // Participantes deste momento = os que pertencem aos municípios deste momento
            $participantesDoMomento = $participantes->filter(
                fn ($p) => in_array($p['municipio_id'], $munIds)
            );

            foreach ($participantesDoMomento as $entry) {
                $participante = $entry['participante'];

                $inscricao = Inscricao::create([
                    'evento_id'      => $evento->id,
                    'atividade_id'   => $atividade->id,
                    'participante_id' => $participante->id,
                ]);

                $presenca = Presenca::create([
                    'inscricao_id'        => $inscricao->id,
                    'atividade_id'        => $atividade->id,
                    'status'              => 'Presente',
                    'avaliacao_respondida' => true,
                ]);

                $submissao = SubmissaoAvaliacao::create([
                    'avaliacao_id'  => $avaliacao->id,
                    'atividade_id'  => $atividade->id,
                    'presenca_id'   => $presenca->id,
                    'codigo'        => Str::random(10),
                ]);

                foreach ($avaliacaoQuestoes as $aq) {
                    $respostaStr = match ($aq->tipo) {
                        'escala'   => ['Razoável', 'Bom', 'Excelente'][rand(0, 2)],
                        'boolean'  => rand(0, 1) ? 'Sim' : 'Não',
                        'unica'    => ['Alto', 'Médio', 'Baixo'][rand(0, 2)],
                        'multipla' => (function () {
                            $opcoes   = ['Metodologia', 'Didática', 'Prática', 'Teoria'];
                            $selected = array_rand(array_flip($opcoes), rand(1, 3));
                            return json_encode(is_array($selected) ? $selected : [$selected]);
                        })(),
                        'texto'  => "Comentário do participante {$participante->id} ({$entry['municipio_id']})",
                        default  => '',
                    };

                    RespostaAvaliacao::create([
                        'avaliacao_id'          => $avaliacao->id,
                        'avaliacao_questao_id'  => $aq->id,
                        'submissao_avaliacao_id' => $submissao->id,
                        'resposta'              => $respostaStr,
                    ]);
                }
            }
        }

        // Limpar arquivo temporário de verificação, se existir
        @unlink(base_path('check_geo.php'));
    }
}
