<?php

namespace Tests\Feature;

use App\Models\Dimensao;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DimensaoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $dimensao = Dimensao::factory()->create(['descricao' => 'Planejamento pedagógico']);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('dimensaos.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false)
            ->assertSee('Planejamento pedagógico');
    }
}
