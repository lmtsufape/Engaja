<?php

namespace Tests\Feature;

use App\Models\Evidencia;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvidenciaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $evidencia = Evidencia::factory()->create(['descricao' => 'Evidência de engajamento']);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('evidencias.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false)
            ->assertSee('Evidência de engajamento');
    }
}
