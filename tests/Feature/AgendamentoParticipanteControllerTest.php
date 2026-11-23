<?php

namespace Tests\Feature;

use App\Models\Agendamento;
use App\Models\AgendamentoParticipante;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendamentoParticipanteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_acoes_quando_nao_efetivado(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        $agendamento = Agendamento::factory()->create(['efetivado' => false]);
        AgendamentoParticipante::factory()->create([
            'agendamento_id' => $agendamento->id,
            'nome' => 'Maria Participante',
        ]);

        $this->actingAs($user)
            ->get(route('agendamentos.participantes.index', $agendamento))
            ->assertOk()
            ->assertSee('grid-agendamento-participantes', false)
            ->assertSee('Maria Participante')
            ->assertSee('Editar');
    }

    public function test_index_oculta_acoes_quando_efetivado(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        $agendamento = Agendamento::factory()->create(['efetivado' => true]);
        AgendamentoParticipante::factory()->create([
            'agendamento_id' => $agendamento->id,
            'nome' => 'João Participante',
        ]);

        $this->actingAs($user)
            ->get(route('agendamentos.participantes.index', $agendamento))
            ->assertOk()
            ->assertSee('João Participante')
            ->assertDontSee('Editar');
    }
}
