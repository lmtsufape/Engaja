<?php

namespace Tests\Feature;

use App\Models\ModeloCertificado;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModeloCertificadoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $modelo = ModeloCertificado::factory()->create(['nome' => 'Modelo padrão Alfa-EJA']);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('certificados.modelos.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false)
            ->assertSee('Modelo padrão Alfa-EJA');
    }
}
