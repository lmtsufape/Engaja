<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Evento;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPresencasIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_linhas_de_detalhe(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        $evento = Evento::factory()->create(['nome' => 'Formação Alfa-EJA']);
        Atividade::factory()->create([
            'evento_id' => $evento->id,
            'descricao' => 'Encontro de abertura',
        ]);

        $this->actingAs($user)
            ->get(route('dashboards.presencas'))
            ->assertOk()
            ->assertSee('grid-dashboard-presencas', false)
            ->assertSee('data-detail-row-field="_isDetailRow"', false)
            ->assertSee('Encontro de abertura')
            ->assertSee('data-toggle-detail', false);
    }
}
