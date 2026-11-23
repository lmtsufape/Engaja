<?php

namespace Tests\Feature;

use App\Mail\AgendamentoCriadoMail;
use App\Models\AtividadeAcao;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Regiao;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AgendamentoCriadoEmailTest extends TestCase
{
    use RefreshDatabase;

    private AtividadeAcao $atividadeAcao;

    private Municipio $municipio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);

        $regiao = Regiao::create(['nome' => 'Nordeste']);
        $estado = Estado::create(['regiao_id' => $regiao->id, 'nome' => 'Bahia', 'sigla' => 'BA']);
        $this->municipio = Municipio::create(['estado_id' => $estado->id, 'nome' => 'Salvador']);
        $this->atividadeAcao = AtividadeAcao::create(['nome' => 'Formação de Professores', 'usa_turmas' => false]);
    }

    public function test_criar_agendamento_envia_email_para_usuarios_com_permissao(): void
    {
        Mail::fake();

        $notificado = User::factory()->create(['email' => 'notificado@test.local']);
        $notificado->givePermissionTo('agendamento.notificar');

        $criador = User::factory()->create(['email' => 'articulador@test.local']);
        $criador->assignRole('articulador');

        $this->actingAs($criador)->post(route('agendamentos.store'), [
            'atividade_acao_id' => $this->atividadeAcao->id,
            'municipio_id' => $this->municipio->id,
            'data_horario' => '2026-07-10 09:00',
            'publico_participante' => 'Professores da rede',
            'local_acao' => 'Escola Municipal Central',
        ]);

        Mail::assertQueued(AgendamentoCriadoMail::class, fn ($mail) => $mail->hasTo('notificado@test.local'));
    }

    public function test_criar_agendamento_envia_email_para_varios_usuarios_marcados(): void
    {
        Mail::fake();

        $primeiro = User::factory()->create(['email' => 'primeiro@test.local']);
        $primeiro->givePermissionTo('agendamento.notificar');

        $segundo = User::factory()->create(['email' => 'segundo@test.local']);
        $segundo->givePermissionTo('agendamento.notificar');

        $criador = User::factory()->create(['email' => 'articulador2@test.local']);
        $criador->assignRole('articulador');

        $this->actingAs($criador)->post(route('agendamentos.store'), [
            'atividade_acao_id' => $this->atividadeAcao->id,
            'municipio_id' => $this->municipio->id,
            'data_horario' => '2026-07-10 09:00',
            'publico_participante' => 'Professores da rede',
            'local_acao' => 'Escola Municipal Central',
        ]);

        Mail::assertQueued(AgendamentoCriadoMail::class, fn ($mail) => $mail->hasTo('primeiro@test.local'));
        Mail::assertQueued(AgendamentoCriadoMail::class, fn ($mail) => $mail->hasTo('segundo@test.local'));
    }

    public function test_criar_agendamento_nao_envia_email_para_usuarios_sem_permissao(): void
    {
        Mail::fake();

        $semPermissao = User::factory()->create(['email' => 'sempermissao@test.local']);

        $adminSemPermissao = User::factory()->create(['email' => 'admin@test.local']);
        $adminSemPermissao->assignRole('administrador');

        $criador = User::factory()->create(['email' => 'articulador3@test.local']);
        $criador->assignRole('articulador');

        $this->actingAs($criador)->post(route('agendamentos.store'), [
            'atividade_acao_id' => $this->atividadeAcao->id,
            'municipio_id' => $this->municipio->id,
            'data_horario' => '2026-07-10 09:00',
            'publico_participante' => 'Professores da rede',
            'local_acao' => 'Escola Municipal Central',
        ]);

        Mail::assertNotQueued(AgendamentoCriadoMail::class, fn ($mail) => $mail->hasTo('sempermissao@test.local'));
        Mail::assertNotQueued(AgendamentoCriadoMail::class, fn ($mail) => $mail->hasTo('admin@test.local'));
    }
}
