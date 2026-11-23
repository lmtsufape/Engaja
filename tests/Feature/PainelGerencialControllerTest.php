<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\TestCase;

class PainelGerencialControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function comPapel(string $papel): User
    {
        $user = User::factory()->create();
        $user->assignRole($papel);

        return $user;
    }

    public function test_index_acessivel_para_gerente(): void
    {
        $this->actingAs($this->comPapel('gerente'))
            ->get(route('painel-gerencial.index'))
            ->assertOk()
            ->assertViewIs('painel-gerencial.index')
            ->assertSee('grid-painel-metas', false)
            ->assertSee('grid-painel-baixo-engajamento', false)
            ->assertSee('grid-painel-sem-avaliacao', false)
            ->assertSee('grid-painel-recorrencia-ausencia', false);
    }

    public function test_index_negado_para_participante(): void
    {
        $this->actingAs($this->comPapel('participante'))
            ->get(route('painel-gerencial.index'))
            ->assertForbidden();
    }

    public function test_dados_retorna_json_com_blocos(): void
    {
        $this->actingAs($this->comPapel('administrador'))
            ->getJson(route('painel-gerencial.dados'))
            ->assertOk()
            ->assertJsonStructure([
                'kpis' => ['municipios_ativos', 'participantes_unicos', 'certificados_emitidos'],
                'metas_por_acao',
                'participacao_por_regiao',
                'segmentos',
                'evolucao_semestral',
                'municipios_baixo_engajamento',
                'eventos_sem_avaliacao',
                'recorrencia_ausencia',
            ]);
    }

    public function test_exportar_xlsx(): void
    {
        $response = $this->actingAs($this->comPapel('administrador'))
            ->get(route('painel-gerencial.exportar', ['formato' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('content-disposition'));
        $this->assertStringContainsString(
            'spreadsheetml',
            $response->headers->get('content-type')
        );
    }

    public function test_exportar_pdf_usa_view_e_branding(): void
    {
        $builder = Mockery::mock(PdfBuilder::class);
        $builder->shouldReceive('format')->andReturnSelf();
        $builder->shouldReceive('landscape')->andReturnSelf();
        $builder->shouldReceive('withAlfaEjaBrand')->andReturnSelf();
        $builder->shouldReceive('download')->andReturnSelf();
        $builder->shouldReceive('toResponse')->andReturn(response('ok'));

        Pdf::shouldReceive('view')
            ->once()
            ->withArgs(fn ($view, $data) => $view === 'painel-gerencial.pdf' && isset($data['kpis']))
            ->andReturn($builder);

        $this->actingAs($this->comPapel('administrador'))
            ->get(route('painel-gerencial.exportar', ['formato' => 'pdf']))
            ->assertOk();
    }
}
