<?php

namespace Tests\Feature;

use App\Models\AtividadeAcao;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtividadeAcaoControllerTest extends TestCase
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

    public function test_index_renderiza_grid_com_dados(): void
    {
        $atividadeAcao = AtividadeAcao::factory()->create(['nome' => 'Roda de leitura']);

        $response = $this->actingAs($this->comPapel('administrador'))
            ->get(route('atividade-acoes.index'))
            ->assertOk()
            ->assertViewIs('atividade-acoes.index');

        $response->assertSee('data-ag-grid', false);
        $response->assertSee('id="grid-atividade-acoes"', false);
        $response->assertSee('Roda de leitura');
    }

    public function test_index_sem_registros_renderiza_grid_vazio(): void
    {
        $this->actingAs($this->comPapel('administrador'))
            ->get(route('atividade-acoes.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false);
    }
}
