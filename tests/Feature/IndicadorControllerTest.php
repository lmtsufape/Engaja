<?php

namespace Tests\Feature;

use App\Models\Indicador;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndicadorControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $indicador = Indicador::factory()->create(['descricao' => 'Clareza dos objetivos da atividade']);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('indicadors.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false)
            ->assertSee('Clareza dos objetivos da atividade');
    }
}
