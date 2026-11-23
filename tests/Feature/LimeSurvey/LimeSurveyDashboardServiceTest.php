<?php

namespace Tests\Feature\LimeSurvey;

use App\Services\LimeSurvey\LimeSurveyClient;
use App\Services\LimeSurvey\LimeSurveyDashboardService;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class LimeSurveyDashboardServiceTest extends TestCase
{
    private function service(): LimeSurveyDashboardService
    {
        return new LimeSurveyDashboardService(new LimeSurveyClient);
    }

    /**
     * @param  list<array{nivel: int, label: string}>  $opcoes
     * @return array<string, mixed>|null
     */
    private function buildGroups(
        Collection $responses,
        array $opcoes,
        \Closure $toLevel,
        ?string $municipioField = 'municipio',
    ): ?array {
        $method = new ReflectionMethod(LimeSurveyDashboardService::class, 'buildMunicipioClassificationBlock');

        return $method->invoke(
            $this->service(),
            'Q1',
            'Q1',
            'Pergunta de teste',
            $responses,
            $municipioField,
            [],
            $opcoes,
            $toLevel,
        );
    }

    private function likertLevel(): \Closure
    {
        $method = new ReflectionMethod(LimeSurveyDashboardService::class, 'toLikertLevel');

        return fn (mixed $value) => $method->invoke($this->service(), $value);
    }

    public function test_agrupa_municipios_por_nivel_exato(): void
    {
        $opcoes = [
            ['nivel' => 1, 'label' => 'Semanal'],
            ['nivel' => 2, 'label' => 'Quinzenal'],
            ['nivel' => 3, 'label' => 'Mensal'],
        ];

        $responses = collect([
            ['Q1' => '1', 'municipio' => 'BELEM'],
            ['Q1' => '3', 'municipio' => 'CAUCAIA'],
        ]);

        $resultado = $this->buildGroups($responses, $opcoes, $this->likertLevel());

        $grupos = collect($resultado['grupos_nivel'])->keyBy('nivel');
        $this->assertSame(['BELEM'], $grupos[1]['municipios']);
        $this->assertSame([], $grupos[2]['municipios']);
        $this->assertSame(['CAUCAIA'], $grupos[3]['municipios']);
    }

    public function test_media_fracionaria_cai_no_nivel_mais_proximo(): void
    {
        $opcoes = [
            ['nivel' => 1, 'label' => 'Semanal'],
            ['nivel' => 2, 'label' => 'Quinzenal'],
            ['nivel' => 3, 'label' => 'Mensal'],
        ];

        // Média (1 + 1 + 3) / 3 = 1.67 -> mais próximo de 2 (Quinzenal) do que de 1 ou 3.
        $responses = collect([
            ['Q1' => '1', 'municipio' => 'BELEM'],
            ['Q1' => '1', 'municipio' => 'BELEM'],
            ['Q1' => '3', 'municipio' => 'BELEM'],
        ]);

        $resultado = $this->buildGroups($responses, $opcoes, $this->likertLevel());

        $grupos = collect($resultado['grupos_nivel'])->keyBy('nivel');
        $this->assertSame(['BELEM'], $grupos[2]['municipios']);
        $this->assertSame([], $grupos[1]['municipios']);
        $this->assertSame([], $grupos[3]['municipios']);
    }

    public function test_boolean_fica_no_grupo_majoritario(): void
    {
        $opcoes = [
            ['nivel' => 0, 'label' => 'Não'],
            ['nivel' => 1, 'label' => 'Sim'],
        ];

        // sim, sim, nao -> média 0.67 -> mais próximo de "Sim".
        $responses = collect([
            ['Q1' => 'sim', 'municipio' => 'BELEM'],
            ['Q1' => 'sim', 'municipio' => 'BELEM'],
            ['Q1' => 'nao', 'municipio' => 'BELEM'],
        ]);

        $toLevel = function (mixed $value) {
            $method = new ReflectionMethod(LimeSurveyDashboardService::class, 'toBoolSelection');
            if ($value === null || trim((string) $value) === '') {
                return null;
            }

            return $method->invoke($this->service(), $value) ? 1.0 : 0.0;
        };

        $resultado = $this->buildGroups($responses, $opcoes, $toLevel);

        $grupos = collect($resultado['grupos_nivel'])->keyBy('nivel');
        $this->assertSame(['BELEM'], $grupos[1]['municipios']);
        $this->assertSame([], $grupos[0]['municipios']);
    }

    public function test_deriva_opcoes_a_partir_dos_codigos_vistos_quando_nao_ha_legenda(): void
    {
        $responses = collect([
            ['Q1' => '2', 'municipio' => 'BELEM'],
            ['Q1' => '5', 'municipio' => 'CAUCAIA'],
        ]);

        $resultado = $this->buildGroups($responses, [], $this->likertLevel());

        $niveis = collect($resultado['grupos_nivel'])->pluck('label', 'nivel')->all();
        $this->assertSame(['2' => '2', '5' => '5'], $niveis);

        $grupos = collect($resultado['grupos_nivel'])->keyBy('nivel');
        $this->assertSame(['BELEM'], $grupos[2]['municipios']);
        $this->assertSame(['CAUCAIA'], $grupos[5]['municipios']);
    }

    public function test_retorna_null_quando_nao_ha_dados(): void
    {
        $opcoes = [['nivel' => 1, 'label' => 'Semanal']];

        $resultado = $this->buildGroups(collect(), $opcoes, $this->likertLevel());

        $this->assertNull($resultado);
    }

    /**
     * @param  array<string, mixed>  $grouped
     * @param  list<array{nivel: int, label: string}>  $opcoes
     * @return array<string, mixed>|null
     */
    private function buildSingleSeries(array $grouped, Collection $responses, array $opcoes): ?array
    {
        $method = new ReflectionMethod(LimeSurveyDashboardService::class, 'buildMunicipioClassificationForSingleSeries');

        return $method->invoke($this->service(), $grouped, 'Q17', $responses, 'municipio', [], $opcoes);
    }

    public function test_questao_array_de_subquestao_unica_vira_classificacao(): void
    {
        $grouped = ['texto' => 'Em que ano aconteceu?', 'source_ids' => ['Q17[SQ001]']];
        $opcoes = [
            ['nivel' => 1, 'label' => 'Até 2020'],
            ['nivel' => 2, 'label' => 'Após 2020'],
        ];

        $responses = collect([
            ['Q17[SQ001]' => '1', 'municipio' => 'BELEM'],
            ['Q17[SQ001]' => '2', 'municipio' => 'CAUCAIA'],
        ]);

        $resultado = $this->buildSingleSeries($grouped, $responses, $opcoes);

        $this->assertSame('municipio_level', $resultado['tipo']);
        $this->assertSame('Q17', $resultado['id']);

        $grupos = collect($resultado['grupos_nivel'])->keyBy('nivel');
        $this->assertSame(['BELEM'], $grupos[1]['municipios']);
        $this->assertSame(['CAUCAIA'], $grupos[2]['municipios']);
    }

    /**
     * @return array{label: string, descricao: string}
     */
    private function splitLabel(string $raw): array
    {
        $method = new ReflectionMethod(LimeSurveyDashboardService::class, 'splitOptionLabel');

        return $method->invoke($this->service(), $raw);
    }

    public function test_extrai_rotulo_curto_do_strong_e_manda_o_resto_para_descricao(): void
    {
        $raw = '<p><strong>Nível 0 – Ausência de acompanhamento</strong></p>'
            .'<p>Não há acompanhamento sistemático. A equipe da SME não visita as escolas.</p>';

        $resultado = $this->splitLabel($raw);

        $this->assertSame('Nível 0 – Ausência de acompanhamento', $resultado['label']);
        $this->assertStringContainsString('Não há acompanhamento sistemático', $resultado['descricao']);
        $this->assertStringStartsWith('Nível 0', $resultado['descricao']);
    }

    public function test_rotulo_curto_sem_html_fica_intacto_e_sem_descricao(): void
    {
        $resultado = $this->splitLabel('Sim');

        $this->assertSame('Sim', $resultado['label']);
        $this->assertSame('', $resultado['descricao']);
    }

    public function test_rotulo_longo_sem_strong_e_truncado(): void
    {
        $longo = str_repeat('palavra ', 40);

        $resultado = $this->splitLabel($longo);

        $this->assertSame(70, mb_strlen($resultado['label']));
        $this->assertStringEndsWith('…', $resultado['label']);
        $this->assertNotSame('', $resultado['descricao']);
    }

    public function test_questao_array_com_varias_subquestoes_nao_vira_classificacao(): void
    {
        $grouped = ['texto' => 'Matriz', 'source_ids' => ['Q18[SQ001]', 'Q18[SQ002]']];

        $responses = collect([
            ['Q18[SQ001]' => '1', 'Q18[SQ002]' => '2', 'municipio' => 'BELEM'],
        ]);

        $this->assertNull($this->buildSingleSeries($grouped, $responses, []));
    }
}
