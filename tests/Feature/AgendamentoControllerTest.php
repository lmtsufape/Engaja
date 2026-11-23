<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendamentoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_apenas_com_agendamentos_do_usuario(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        $agendamento = Agendamento::factory()->create([
            'user_id' => $user->id,
            'local_acao' => 'Escola Municipal Alfa-EJA',
        ]);

        Agendamento::factory()->create(['local_acao' => 'Outro local de outro usuário']);

        $response = $this->actingAs($user)
            ->get(route('agendamentos.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false)
            ->assertSee('Escola Municipal Alfa-EJA');

        $response->assertDontSee('Outro local de outro usuário');
    }
}
