<?php

namespace Tests\Feature;

use App\Exports\RelatorioTotalGeralExport;
use App\Http\Controllers\RelatorioQuantitativoController;
use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RelatorioQuantitativoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function criarAtividade(): Atividade
    {
        $evento = Evento::factory()->create(['nome' => 'Formação Alfa-EJA']);
        $municipio = Municipio::factory()->create();

        return Atividade::factory()->create([
            'evento_id' => $evento->id,
            'municipio_id' => $municipio->id,
            'publico_esperado' => 50,
        ]);
    }

    public function test_aba_momento_renderiza_grid_com_subtotal(): void
    {
        $this->criarAtividade();

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('relatorio-quantitativo.index', ['tab' => 'momento']))
            ->assertOk()
            ->assertSee('grid-relatorio-momento', false)
            ->assertSee('Formação Alfa-EJA')
            ->assertSee('Subtotal');
    }

    public function test_aba_total_geral_renderiza_grid(): void
    {
        $this->criarAtividade();

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('relatorio-quantitativo.index', ['tab' => 'total-geral']))
            ->assertOk()
            ->assertSee('grid-relatorio-total-geral', false);
    }

    public function test_total_geral_ignora_filtro_de_municipio(): void
    {
        $evento = Evento::factory()->create(['nome' => 'Formação Alfa-EJA']);
        $municipioA = Municipio::factory()->create(['nome' => 'Alpha City']);
        $municipioB = Municipio::factory()->create(['nome' => 'Beta City']);

        Atividade::factory()->create(['evento_id' => $evento->id, 'municipio_id' => $municipioA->id, 'publico_esperado' => 10]);
        Atividade::factory()->create(['evento_id' => $evento->id, 'municipio_id' => $municipioB->id, 'publico_esperado' => 20]);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        // Mesmo passando municipio_id, o relatório continua listando todos os municípios.
        $this->actingAs($user)
            ->get(route('relatorio-quantitativo.index', ['tab' => 'total-geral', 'municipio_id' => $municipioA->id]))
            ->assertOk()
            ->assertSee('Alpha City')
            ->assertSee('Beta City');
    }

    public function test_total_geral_separa_abrangencia_nacional_de_municipios_nao_identificados(): void
    {
        $evento = Evento::factory()->create(['nome' => 'Formação Alfa-EJA']);

        // Atividade de abrangência nacional: município nulo é intencional.
        Atividade::factory()->create([
            'evento_id' => $evento->id,
            'municipio_id' => null,
            'abrangencia_nacional' => true,
            'publico_esperado' => 30,
        ]);

        // Atividade sem município e sem abrangência nacional: dado ausente.
        Atividade::factory()->create([
            'evento_id' => $evento->id,
            'municipio_id' => null,
            'abrangencia_nacional' => false,
            'publico_esperado' => 15,
        ]);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        // As duas atividades caem em linhas distintas, não somadas em "não identificados".
        $this->actingAs($user)
            ->get(route('relatorio-quantitativo.index', ['tab' => 'total-geral']))
            ->assertOk()
            ->assertSee('Brasil (abrang', false)
            ->assertSee('identificados', false);
    }

    public function test_total_geral_omite_municipios_sem_dados(): void
    {
        $evento = Evento::factory()->create(['nome' => 'Formação Alfa-EJA']);
        $comDados = Municipio::factory()->create(['nome' => 'Com Dados']);
        Municipio::factory()->create(['nome' => 'Sem Dados Nenhum']);

        Atividade::factory()->create([
            'evento_id' => $evento->id,
            'municipio_id' => $comDados->id,
            'publico_esperado' => 5,
        ]);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        // Só municípios com previstos ou presentes aparecem; a tabela cobre todo o Brasil.
        $this->actingAs($user)
            ->get(route('relatorio-quantitativo.index', ['tab' => 'total-geral']))
            ->assertOk()
            ->assertSee('Com Dados')
            ->assertDontSee('Sem Dados Nenhum');
    }

    public function test_presenca_conta_no_municipio_do_participante_nao_do_momento(): void
    {
        $evento = Evento::factory()->create(['nome' => 'Formação Alfa-EJA']);
        $municipioParticipante = Municipio::factory()->create(['nome' => 'Cidade Do Participante']);
        $municipioMomento = Municipio::factory()->create(['nome' => 'Cidade Do Momento']);

        // Momento acontece em "Cidade Do Momento".
        $atividade = Atividade::factory()->create([
            'evento_id' => $evento->id,
            'municipio_id' => $municipioMomento->id,
            'publico_esperado' => 10,
        ]);

        // Participante é de "Cidade Do Participante".
        $participante = Participante::factory()->create(['municipio_id' => $municipioParticipante->id]);

        $inscricaoId = \DB::table('inscricaos')->insertGetId([
            'evento_id' => $evento->id,
            'participante_id' => $participante->id,
            'atividade_id' => $atividade->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('presencas')->insert([
            'inscricao_id' => $inscricaoId,
            'atividade_id' => $atividade->id,
            'status' => 'presente',
            'avaliacao_respondida' => false,
            'certificado_emitido' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ref = new \ReflectionMethod(RelatorioQuantitativoController::class, 'buildTotalGeralData');
        $ref->setAccessible(true);
        $rows = collect($ref->invoke(new RelatorioQuantitativoController, Request::create('/', 'GET', [])))
            ->keyBy('municipio_id');

        // Presença conta na cidade do participante...
        $this->assertSame(1, $rows[$municipioParticipante->id]['metricas']['total_presentes']);
        // ...e não na cidade onde o momento aconteceu.
        $this->assertSame(0, $rows[$municipioMomento->id]['metricas']['total_presentes']);
        // Já "Previstos" fica na cidade do momento (é atributo do momento).
        $this->assertSame(10, $rows[$municipioMomento->id]['previstos']);
    }

    public function test_resumo_do_export_total_geral_nao_anuncia_filtro_de_municipio(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Alpha City']);

        // Total Geral ignora município; o resumo do arquivo não deve afirmar que aplicou.
        $request = Request::create('/', 'GET', ['municipio_id' => $municipio->id]);
        $export = new RelatorioTotalGeralExport($request);

        $anunciaMunicipio = collect($export->getFiltersSummary())
            ->contains(fn ($filtro) => str_contains($filtro, 'Município'));

        $this->assertFalse($anunciaMunicipio, 'O resumo do export não deve anunciar filtro de município.');
    }
}
