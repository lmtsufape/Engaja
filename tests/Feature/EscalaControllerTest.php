<?php

namespace Tests\Feature;

use App\Models\Escala;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscalaControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $escala = Escala::factory()->create(['descricao' => 'Escala de concordância']);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('escalas.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false)
            ->assertSee('Escala de concordância');
    }
}
