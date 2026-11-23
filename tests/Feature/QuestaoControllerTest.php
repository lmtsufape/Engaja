<?php

namespace Tests\Feature;

use App\Models\Questao;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestaoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $questao = Questao::factory()->create(['texto' => 'A atividade atendeu suas expectativas?']);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('questaos.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false)
            ->assertSee('A atividade atendeu suas expectativas?');
    }
}
