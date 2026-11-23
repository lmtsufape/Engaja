<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Avaliacao;
use App\Models\AvaliacaoQuestao;
use App\Models\Eixo;
use App\Models\Estado;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Regiao;
use App\Models\RespostaAvaliacao;
use App\Models\SubmissaoAvaliacao;
use App\Models\TemplateAvaliacao;
use App\Models\User;
use App\Services\AvaliacaoConsolidacaoService;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AvaliacaoConsolidadaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_painel_consolida_media_geral_por_modelo_da_acao_pedagogica(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $evento = $this->criarEventoComDuasAvaliacoes();

        $response = $this->actingAs($admin)->get(route('avaliacoes-consolidadas.index', [
            'evento_id' => $evento->id,
            'agrupamento' => 'geral',
        ]));

        $response->assertOk();
        $response->assertSee('Consolidação de avaliações');
        $response->assertSee('Modelo de satisfação');
        $response->assertSee('3,00');
        $response->assertSee('Todos os municípios');
    }

    public function test_rota_antiga_redireciona_para_o_painel_com_acao_selecionada(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $evento = $this->criarEventoComDuasAvaliacoes();

        $response = $this->actingAs($admin)->get(route('eventos.avaliacoes.consolidado', [
            'evento' => $evento,
            'agrupamento' => 'regiao',
        ]));

        $response->assertRedirect(route('avaliacoes-consolidadas.index', [
            'evento_id' => $evento->id,
            'agrupamento' => 'regiao',
        ]));
    }

    public function test_consolidado_unifica_a_mesma_pergunta_repetida_em_varios_momentos(): void
    {
        $evento = $this->criarEventoComTresMomentosEMunicipios();

        $grupos = app(AvaliacaoConsolidacaoService::class)->build($evento, 'geral');

        $this->assertCount(1, $grupos);
        $template = $grupos[0]['templates'][0];
        $perguntas = collect($template['perguntas']);

        $this->assertSame(6, $template['submissoes']);
        $this->assertSame(12, $template['respostas']);
        $this->assertSame(
            1,
            $perguntas->where('texto', 'A análise das videoaulas e os relatos compartilhados ajudaram a relacionar os conteúdos do curso com a prática na EJA.')->count()
        );

        $perguntaRepetida = $perguntas->firstWhere(
            'texto',
            'A análise das videoaulas e os relatos compartilhados ajudaram a relacionar os conteúdos do curso com a prática na EJA.'
        );

        $this->assertSame(6, $perguntaRepetida['total']);
        $this->assertSame(4.0, $perguntaRepetida['media']);
        $this->assertSame(4, $perguntas->count());
    }

    public function test_consolidado_pode_agrupar_por_regiao_e_por_municipio(): void
    {
        $evento = $this->criarEventoComTresMomentosEMunicipios();
        $service = app(AvaliacaoConsolidacaoService::class);

        $porRegiao = collect($service->build($evento, 'regiao'))->keyBy('nome');
        $porMunicipio = collect($service->build($evento, 'municipio'))->keyBy('nome');

        $this->assertSame(['Litoral', 'Sertão'], $porRegiao->keys()->all());
        $this->assertSame(4, $porRegiao['Litoral']['templates'][0]['submissoes']);
        $this->assertSame(2, $porRegiao['Sertão']['templates'][0]['submissoes']);

        $this->assertSame(['Caucaia', 'Fortaleza', 'Quixadá'], $porMunicipio->keys()->all());
        $this->assertSame(2, $porMunicipio['Fortaleza']['templates'][0]['submissoes']);
        $this->assertSame(2, $porMunicipio['Caucaia']['templates'][0]['submissoes']);
        $this->assertSame(2, $porMunicipio['Quixadá']['templates'][0]['submissoes']);
    }

    public function test_painel_aceita_agrupamento_por_municipio(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');
        $evento = $this->criarEventoComTresMomentosEMunicipios();

        $response = $this->actingAs($admin)->get(route('avaliacoes-consolidadas.index', [
            'evento_id' => $evento->id,
            'agrupamento' => 'municipio',
        ]));

        $response->assertOk();
        $response->assertSee('Agrupado por município');
        $response->assertSee('Fortaleza');
        $response->assertSee('Caucaia');
        $response->assertSee('Quixadá');
    }

    private function criarEventoComDuasAvaliacoes(): Evento
    {
        $eixo = Eixo::create(['nome' => 'Eixo teste']);
        $evento = Evento::factory()->create(['eixo_id' => $eixo->id]);
        $template = TemplateAvaliacao::create(['nome' => 'Modelo de satisfação']);

        $atividadeUm = Atividade::factory()->create(['evento_id' => $evento->id]);
        $atividadeDois = Atividade::factory()->create(['evento_id' => $evento->id]);

        $this->criarRespostaNumerica($atividadeUm, $template, '4');
        $this->criarRespostaNumerica($atividadeDois, $template, '2');

        return $evento;
    }

    private function criarRespostaNumerica(Atividade $atividade, TemplateAvaliacao $template, string $valor): void
    {
        $avaliacao = Avaliacao::create([
            'atividade_id' => $atividade->id,
            'template_avaliacao_id' => $template->id,
        ]);

        $questao = AvaliacaoQuestao::create([
            'avaliacao_id' => $avaliacao->id,
            'texto' => 'Como você avalia o momento?',
            'tipo' => 'numero',
            'ordem' => 1,
        ]);

        $submissao = SubmissaoAvaliacao::create([
            'codigo' => fake()->unique()->regexify('[A-Z0-9]{26}'),
            'atividade_id' => $atividade->id,
            'avaliacao_id' => $avaliacao->id,
        ]);

        RespostaAvaliacao::create([
            'avaliacao_id' => $avaliacao->id,
            'avaliacao_questao_id' => $questao->id,
            'submissao_avaliacao_id' => $submissao->id,
            'resposta' => $valor,
        ]);
    }

    private function criarEventoComTresMomentosEMunicipios(): Evento
    {
        $eixo = Eixo::create(['nome' => 'Eixo teste']);
        $evento = Evento::factory()->create([
            'eixo_id' => $eixo->id,
            'nome' => 'CACPF - Encerramento do Módulo 1 e Abertura do Módulo 2',
        ]);
        $template = TemplateAvaliacao::create(['nome' => 'Modelo CACPF']);

        $fortaleza = $this->criarMunicipio('Litoral', 'Ceará', 'CE', 'Fortaleza');
        $caucaia = $this->criarMunicipio('Litoral', 'Ceará', 'CE', 'Caucaia');
        $quixada = $this->criarMunicipio('Sertão', 'Ceará', 'CE', 'Quixadá');

        $this->criarAvaliacaoDoMomento(
            $evento,
            $template,
            $fortaleza,
            'Módulo 1',
            [5, 4],
            [4, 3],
        );
        $this->criarAvaliacaoDoMomento(
            $evento,
            $template,
            $caucaia,
            'Módulo 2',
            [4, 4],
            [5, 4],
        );
        $this->criarAvaliacaoDoMomento(
            $evento,
            $template,
            $quixada,
            'Módulo 3',
            [3, 4],
            [3, 2],
        );

        return $evento;
    }

    private function criarMunicipio(string $regiaoNome, string $estadoNome, string $sigla, string $municipioNome): Municipio
    {
        $regiao = Regiao::firstOrCreate(['nome' => $regiaoNome]);
        $estado = Estado::firstOrCreate(
            ['sigla' => $sigla, 'regiao_id' => $regiao->id],
            ['nome' => $estadoNome]
        );

        return Municipio::create([
            'estado_id' => $estado->id,
            'nome' => $municipioNome,
        ]);
    }

    /**
     * @param  list<int>  $notasPerguntaRepetida
     * @param  list<int>  $notasPerguntaDoModulo
     */
    private function criarAvaliacaoDoMomento(
        Evento $evento,
        TemplateAvaliacao $template,
        Municipio $municipio,
        string $modulo,
        array $notasPerguntaRepetida,
        array $notasPerguntaDoModulo
    ): void {
        $atividade = Atividade::factory()->create([
            'evento_id' => $evento->id,
            'municipio_id' => $municipio->id,
            'descricao' => 'Encontro '.$modulo,
        ]);
        $atividade->municipios()->attach($municipio->id);

        $avaliacao = Avaliacao::create([
            'atividade_id' => $atividade->id,
            'template_avaliacao_id' => $template->id,
        ]);

        $perguntaRepetida = AvaliacaoQuestao::create([
            'avaliacao_id' => $avaliacao->id,
            'texto' => 'A análise das videoaulas e os relatos compartilhados ajudaram a relacionar os conteúdos do curso com a prática na EJA.',
            'tipo' => 'escala',
            'ordem' => 1,
        ]);

        $perguntaDoModulo = AvaliacaoQuestao::create([
            'avaliacao_id' => $avaliacao->id,
            'texto' => 'O encontro contribuiu para ampliar minha compreensão sobre os temas trabalhados no '.$modulo.'.',
            'tipo' => 'escala',
            'ordem' => 2,
        ]);

        foreach ($notasPerguntaRepetida as $index => $notaRepetida) {
            $submissao = SubmissaoAvaliacao::create([
                'codigo' => fake()->unique()->regexify('[A-Z0-9]{26}'),
                'atividade_id' => $atividade->id,
                'avaliacao_id' => $avaliacao->id,
            ]);

            RespostaAvaliacao::create([
                'avaliacao_id' => $avaliacao->id,
                'avaliacao_questao_id' => $perguntaRepetida->id,
                'submissao_avaliacao_id' => $submissao->id,
                'resposta' => (string) $notaRepetida,
            ]);

            RespostaAvaliacao::create([
                'avaliacao_id' => $avaliacao->id,
                'avaliacao_questao_id' => $perguntaDoModulo->id,
                'submissao_avaliacao_id' => $submissao->id,
                'resposta' => (string) $notasPerguntaDoModulo[$index],
            ]);
        }
    }
}
