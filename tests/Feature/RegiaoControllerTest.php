<?php

namespace Tests\Feature;

use App\Models\Regiao;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegiaoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        Regiao::factory()->create(['nome' => 'Região Sul']);

        $this->actingAs($user)
            ->get(route('regioes.index'))
            ->assertOk()
            ->assertSee('grid-regioes', false)
            ->assertSee('Região Sul');
    }
}
