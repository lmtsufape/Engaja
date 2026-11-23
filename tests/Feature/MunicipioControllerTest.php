<?php

namespace Tests\Feature;

use App\Models\Municipio;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MunicipioControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $municipio = Municipio::factory()->create(['nome' => 'Feira de Santana']);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('municipios.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false)
            ->assertSee('Feira de Santana');
    }
}
