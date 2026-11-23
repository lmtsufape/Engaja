<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\AtividadeAcao;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendamentoEfetivacaoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_agendamentos_pendentes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        $atividadeAcao = AtividadeAcao::factory()->create(['nome' => 'Formação Inicial']);
        Agendamento::factory()->create([
            'atividade_acao_id' => $atividadeAcao->id,
            'efetivado' => false,
        ]);
        Agendamento::factory()->create(['efetivado' => true]);

        $this->actingAs($user)
            ->get(route('agendamentos.efetivacoes.index'))
            ->assertOk()
            ->assertSee('grid-agendamentos-efetivacoes', false)
            ->assertSee('Formação Inicial');
    }
}
