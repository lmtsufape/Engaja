<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstadoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $estado = Estado::factory()->create(['nome' => 'Bahia']);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('estados.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false)
            ->assertSee('Bahia');
    }
}
