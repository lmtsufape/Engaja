<?php

namespace Tests\Feature;

use App\Models\Avaliacao;
use App\Models\TemplateAvaliacao;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAvaliacoesFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionsSeeder::class);
    }

    private function usuarioAutenticado(): User
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        return $user;
    }

    public function test_retorna_erro_quando_filtro_de_momento_nao_tem_acao_pedagogica(): void
    {
        $response = $this->actingAs($this->usuarioAutenticado())
            ->getJson(route('dashboards.avaliacoes.data', [
                'tipo' => 'momento',
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['evento_id']);
    }

    public function test_retorna_erro_quando_avaliacao_universal_nao_e_informada(): void
    {
        $response = $this->actingAs($this->usuarioAutenticado())
            ->getJson(route('dashboards.avaliacoes.data', [
                'tipo' => 'universal',
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['avaliacao_id']);
    }

    public function test_retorna_erro_quando_periodo_esta_invertido(): void
    {
        $template = TemplateAvaliacao::factory()->create();
        $avaliacao = Avaliacao::factory()->create([
            'template_avaliacao_id' => $template->id,
            'atividade_id' => null,
            'descricao_universal' => 'Avaliação universal de teste',
        ]);

        $response = $this->actingAs($this->usuarioAutenticado())
            ->getJson(route('dashboards.avaliacoes.data', [
                'tipo' => 'universal',
                'avaliacao_id' => $avaliacao->id,
                'de' => '2026-07-02',
                'ate' => '2026-07-01',
            ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['ate']);
    }
}
