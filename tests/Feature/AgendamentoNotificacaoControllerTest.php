<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendamentoNotificacaoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_administrador_pode_listar_usuarios(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $response = $this->actingAs($admin)->get(route('usuarios.notificacoes-agendamento.index'));

        $response->assertOk();
    }

    public function test_administrador_pode_conceder_permissao_a_um_usuario(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $usuario = User::factory()->create();
        $this->assertFalse($usuario->hasPermissionTo('agendamento.notificar'));

        $this->actingAs($admin)
            ->post(route('usuarios.notificacoes-agendamento.toggle', $usuario))
            ->assertRedirect();

        $this->assertTrue($usuario->refresh()->hasPermissionTo('agendamento.notificar'));
    }

    public function test_administrador_pode_revogar_permissao_de_um_usuario(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $usuario = User::factory()->create();
        $usuario->givePermissionTo('agendamento.notificar');

        $this->actingAs($admin)
            ->post(route('usuarios.notificacoes-agendamento.toggle', $usuario))
            ->assertRedirect();

        $this->assertFalse($usuario->refresh()->hasPermissionTo('agendamento.notificar'));
    }

    public function test_usuario_sem_role_administrador_nao_acessa_a_tela(): void
    {
        $gerente = User::factory()->create();
        $gerente->assignRole('gerente');

        $usuario = User::factory()->create();

        $this->actingAs($gerente)
            ->get(route('usuarios.notificacoes-agendamento.index'))
            ->assertForbidden();

        $this->actingAs($gerente)
            ->post(route('usuarios.notificacoes-agendamento.toggle', $usuario))
            ->assertForbidden();
    }
}
