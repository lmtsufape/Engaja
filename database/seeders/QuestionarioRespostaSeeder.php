<?php

namespace Database\Seeders;

use App\Models\Atividade;
use App\Models\Avaliacao;
use App\Models\AvaliacaoQuestao;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\Presenca;
use App\Models\RespostaAvaliacao;
use App\Models\SubmissaoAvaliacao;
use App\Models\TemplateAvaliacao;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class QuestionarioRespostaSeeder extends Seeder
{
    public function run(): void
    {
        $templates = TemplateAvaliacao::with([
            'questoes' => fn ($q) => $q->orderBy('ordem')->orderBy('id')->with('escala'),
        ])->get();

        if ($templates->isEmpty()) {
            return;
        }

        $atividades = Atividade::with('evento')
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->take(3)
            ->get();

        if ($atividades->isEmpty()) {
            $atividades = $this->criarAtividadesDemo();
        }

        $participantes = $this->criarParticipantesDemo();
        $inscricoes = $this->criarInscricoesEPresencas($atividades, $participantes);

        $templateList = $templates->values();
        $templateCount = max($templateList->count(), 1);

        foreach ($atividades as $index => $atividade) {
            $template = $templateList[$index % $templateCount];

            $avaliacao = Avaliacao::updateOrCreate(
                [
                    'atividade_id' => $atividade->id,
                    'inscricao_id' => null,
                ],
                [
                    'template_avaliacao_id' => $template->id,
                ]
            );

            $questoes = $this->sincronizarQuestoes($avaliacao, $template);

            $inscricoesDaAtividade = $inscricoes->where('atividade_id', $atividade->id);
            $this->criarSubmissoesERespostas($avaliacao, $questoes, $inscricoesDaAtividade);
        }
    }

    private function criarAtividadesDemo(): Collection
    {
        $municipios = Municipio::inRandomOrder()->take(3)->pluck('id');

        $evento = Evento::factory()->create([
            'nome'        => 'Percurso Formativo Engaja',
            'data_inicio' => now()->subDays(10)->format('Y-m-d'),
            'data_fim'    => now()->addDays(15)->format('Y-m-d'),
            'modalidade'  => 'Presencial',
        ]);

        $descricoes = [
            ['texto' => 'Oficina de planejamento participativo', 'offset' => -5],
            ['texto' => 'Roda de conversa com educadores', 'offset' => -2],
            ['texto' => 'Encontro de avaliacao e feedback', 'offset' => 1],
        ];

        return collect($descricoes)->map(function ($dados, int $index) use ($evento, $municipios) {
            return Atividade::factory()
                ->for($evento)
                ->create([
                    'municipio_id'     => $municipios[$index] ?? ($municipios->isNotEmpty() ? $municipios->random() : null),
                    'descricao'        => $dados['texto'],
                    'dia'              => now()->addDays($dados['offset'])->format('Y-m-d'),
                    'hora_inicio'      => '09:00',
                    'hora_fim'         => '12:00',
                    'publico_esperado' => 60 + ($index * 10),
                    'carga_horaria'    => 4,
                    'presenca_ativa'   => true,
                ]);
        });
    }

    private function criarParticipantesDemo(): Collection
    {
        $municipios = Municipio::inRandomOrder()->take(5)->pluck('id')->values();

        $pessoas = [
            [
                'nome'       => 'Ana Paula',
                'email'      => 'ana.paula@engaja.local',
                'cpf'        => '11122233344',
                'telefone'   => '(11) 98888-0001',
                'escola'     => 'Escola Horizonte',
                'tag'        => Participante::TAG_REDE_ENSINO,
                'organizacao'=> 'Rede municipal',
            ],
            [
                'nome'       => 'Bruno Silva',
                'email'      => 'bruno.silva@engaja.local',
                'cpf'        => '22233344455',
                'telefone'   => '(21) 97777-0002',
                'escola'     => 'Centro Cultural Jovem',
                'tag'        => Participante::TAG_MOVIMENTO_SOCIAL,
                'organizacao'=> 'Coletivo Jovem',
            ],
            [
                'nome'       => 'Carla Souza',
                'email'      => 'carla.souza@engaja.local',
                'cpf'        => '33344455566',
                'telefone'   => '(31) 96666-0003',
                'escola'     => 'Instituto Esperanca',
                'tag'        => Participante::TAG_REDE_ENSINO,
                'organizacao'=> 'Rede estadual',
            ],
            [
                'nome'       => 'Diego Martins',
                'email'      => 'diego.martins@engaja.local',
                'cpf'        => '44455566677',
                'telefone'   => '(41) 95555-0004',
                'escola'     => 'Associacao Caminhos',
                'tag'        => Participante::TAG_MOVIMENTO_SOCIAL,
                'organizacao'=> 'Associacao comunitaria',
            ],
            [
                'nome'       => 'Elisa Ramos',
                'email'      => 'elisa.ramos@engaja.local',
                'cpf'        => '55566677788',
                'telefone'   => '(51) 94444-0005',
                'escola'     => 'Centro de Referencia Popular',
                'tag'        => Participante::TAG_REDE_ENSINO,
                'organizacao'=> 'Rede municipal',
            ],
        ];

        return collect($pessoas)->map(function (array $dados, int $index) use ($municipios) {
            $user = User::firstOrCreate(
                ['email' => $dados['email']],
                [
                    'name'              => $dados['nome'],
                    'password'          => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            return Participante::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'municipio_id'    => $municipios[$index] ?? ($municipios->isNotEmpty() ? $municipios->random() : null),
                    'cpf'             => $dados['cpf'],
                    'telefone'        => $dados['telefone'],
                    'escola_unidade'  => $dados['escola'],
                    'tipo_organizacao'=> $dados['organizacao'],
                    'tag'             => $dados['tag'],
                    'data_entrada'    => now()->subDays(30 + ($index * 7))->format('Y-m-d'),
                ]
            );
        });
    }

    private function criarInscricoesEPresencas(Collection $atividades, Collection $participantes): Collection
    {
        $inscricoes = collect();

        foreach ($atividades as $index => $atividade) {
            $inscritos = $participantes->slice($index, 4);
            if ($inscritos->isEmpty()) {
                $inscritos = $participantes;
            }

            foreach ($inscritos as $participante) {
                $inscricao = Inscricao::firstOrCreate(
                    [
                        'atividade_id'   => $atividade->id,
                        'participante_id'=> $participante->id,
                    ],
                    [
                        'evento_id' => $atividade->evento_id,
                    ]
                );

                $inscricoes->push($inscricao);

                Presenca::firstOrCreate(
                    [
                        'inscricao_id' => $inscricao->id,
                        'atividade_id' => $atividade->id,
                    ],
                    [
                        'status' => 'presente',
                    ]
                );
            }
        }

        return $inscricoes;
    }

    private function sincronizarQuestoes(Avaliacao $avaliacao, TemplateAvaliacao $template): Collection
    {
        $questoesTemplate = $template->questoes()->orderBy('ordem')->orderBy('id')->get();
        $idsMantidos = [];

        foreach ($questoesTemplate as $questao) {
            $payload = [
                'questao_id'   => $questao->id,
                'indicador_id' => $questao->indicador_id,
                'escala_id'    => $questao->tipo === 'escala' ? $questao->escala_id : null,
                'evidencia_id' => $questao->evidencia_id,
                'texto'        => $questao->texto,
                'tipo'         => $questao->tipo,
                'ordem'        => $questao->ordem,
                'fixa'         => (bool) $questao->fixa,
            ];

            $avaliacaoQuestao = AvaliacaoQuestao::updateOrCreate(
                [
                    'avaliacao_id' => $avaliacao->id,
                    'questao_id'   => $questao->id,
                ],
                $payload
            );

            $idsMantidos[] = $avaliacaoQuestao->id;
        }

        if (! empty($idsMantidos)) {
            $avaliacao->avaliacaoQuestoes()
                ->whereNotIn('id', $idsMantidos)
                ->delete();
        }

        return $avaliacao->avaliacaoQuestoes()->with('escala')->whereIn('id', $idsMantidos)->get();
    }

    private function criarSubmissoesERespostas(
        Avaliacao $avaliacao,
        Collection $questoes,
        Collection $inscricoes
    ): void {
        if ($questoes->isEmpty() || $inscricoes->isEmpty()) {
            return;
        }

        foreach ($inscricoes as $index => $inscricao) {
            $codigo = 'SUBM-' . $avaliacao->id . '-' . $inscricao->id;

            $submissao = SubmissaoAvaliacao::firstOrCreate(
                ['codigo' => $codigo],
                [
                    'atividade_id' => $avaliacao->atividade_id,
                    'avaliacao_id' => $avaliacao->id,
                ]
            );

            foreach ($questoes as $questao) {
                RespostaAvaliacao::updateOrCreate(
                    [
                        'avaliacao_id'           => $avaliacao->id,
                        'avaliacao_questao_id'   => $questao->id,
                        'submissao_avaliacao_id' => $submissao->id,
                    ],
                    [
                        'resposta' => $this->gerarRespostaParaQuestao($questao, $index),
                    ]
                );
            }

            $presenca = Presenca::where('atividade_id', $avaliacao->atividade_id)
                ->where('inscricao_id', $inscricao->id)
                ->first();

            if ($presenca) {
                $presenca->avaliacao_respondida = true;
                $presenca->save();
            }
        }
    }

    private function gerarRespostaParaQuestao(AvaliacaoQuestao $questao, int $offset): string
    {
        if ($questao->tipo === 'escala') {
            $opcoes = $questao->escala?->valores ?? [];
            if (! empty($opcoes)) {
                return $opcoes[$offset % count($opcoes)];
            }

            return 'Neutro';
        }

        if ($questao->tipo === 'numero') {
            return (string) (6 + ($offset % 5));
        }

        if ($questao->tipo === 'boolean') {
            return $offset % 2 === 0 ? '1' : '0';
        }

        $comentarios = [
            'Achei a atividade bem organizada.',
            'O conteudo ajudou na pratica.',
            'Gostaria de mais momentos de troca.',
            'A equipe facilitou muito o processo.',
            'Foi util para planejar proximas acoes.',
        ];

        return $comentarios[$offset % count($comentarios)];
    }
}
