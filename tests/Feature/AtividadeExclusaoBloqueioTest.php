<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Participante;
use App\Models\Presenca;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AtividadeExclusaoBloqueioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function criarAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        return $admin;
    }

    public function test_permite_excluir_momento_sem_presencas(): void
    {
        $admin = $this->criarAdmin();
        $evento = Evento::factory()->create();
        $atividade = Atividade::factory()->create([
            'evento_id' => $evento->id,
            'descricao' => 'Momento sem presenças',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('eventos.show', $evento))
            ->delete(route('atividades.destroy', $atividade));

        $response->assertRedirect(route('eventos.show', $evento));
        $response->assertSessionHas('success', 'Momento removido com sucesso.');

        $this->assertSoftDeleted('atividades', [
            'id' => $atividade->id,
        ]);
    }

    public function test_bloqueia_exclusao_momento_com_presencas(): void
    {
        $admin = $this->criarAdmin();
        $evento = Evento::factory()->create();
        $atividade = Atividade::factory()->create([
            'evento_id' => $evento->id,
            'descricao' => 'Momento com presenças',
        ]);

        $participanteUser = User::factory()->create();
        $participante = Participante::create(['user_id' => $participanteUser->id]);
        $inscricao = Inscricao::create([
            'evento_id' => $evento->id,
            'atividade_id' => $atividade->id,
            'participante_id' => $participante->id,
        ]);

        Presenca::create([
            'inscricao_id' => $inscricao->id,
            'atividade_id' => $atividade->id,
            'status' => 'presente',
        ]);

        $response = $this->actingAs($admin)
            ->from(route('eventos.show', $evento))
            ->delete(route('atividades.destroy', $atividade));

        $response->assertRedirect(route('eventos.show', $evento));
        $response->assertSessionHas('error');
        $this->assertStringContainsString('Não é possível excluir este momento', session('error'));

        $this->assertNotSoftDeleted('atividades', [
            'id' => $atividade->id,
        ]);
    }

    public function test_eventos_show_exibe_botao_bloqueado_quando_tem_presencas(): void
    {
        $admin = $this->criarAdmin();
        $evento = Evento::factory()->create();
        $atividade = Atividade::factory()->create([
            'evento_id' => $evento->id,
            'descricao' => 'Momento com presenca registrada',
        ]);

        $participanteUser = User::factory()->create();
        $participante = Participante::create(['user_id' => $participanteUser->id]);
        $inscricao = Inscricao::create([
            'evento_id' => $evento->id,
            'atividade_id' => $atividade->id,
            'participante_id' => $participante->id,
        ]);

        Presenca::create([
            'inscricao_id' => $inscricao->id,
            'atividade_id' => $atividade->id,
            'status' => 'presente',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('eventos.show', $evento));

        $response->assertOk();
        $response->assertSee('data-blocked-delete', false);
        $response->assertSee('Este momento possui 1 presença registrada e não pode ser excluído.', false);
    }

    public function test_atividades_index_exibe_botao_bloqueado_quando_tem_presencas(): void
    {
        $admin = $this->criarAdmin();
        $evento = Evento::factory()->create();
        $atividade = Atividade::factory()->create([
            'evento_id' => $evento->id,
            'descricao' => 'Momento para listar no index',
        ]);

        $participanteUser = User::factory()->create();
        $participante = Participante::create(['user_id' => $participanteUser->id]);
        $inscricao = Inscricao::create([
            'evento_id' => $evento->id,
            'atividade_id' => $atividade->id,
            'participante_id' => $participante->id,
        ]);

        Presenca::create([
            'inscricao_id' => $inscricao->id,
            'atividade_id' => $atividade->id,
            'status' => 'presente',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('eventos.atividades.index', $evento));

        $response->assertOk();
        $response->assertSee('data-blocked-delete', false);
        $response->assertSee('Este momento possui 1 presença registrada e não pode ser excluído.', false);
    }
}
