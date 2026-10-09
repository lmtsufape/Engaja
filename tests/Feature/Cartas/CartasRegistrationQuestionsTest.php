<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Estado;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\Regiao;
use App\Models\User;
use App\Notifications\Cartas\CartasVerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartasRegistrationQuestionsTest extends TestCase
{
    use RefreshDatabase;

    private Regiao $regiao;

    private Estado $estado;

    private Municipio $municipio;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'cartas.ver', 'cartas.criar', 'cartas.editar', 'cartas.excluir',
            'cartas.distribuir', 'cartas.responder', 'cartas.verificar',
            'cartas.editar-enviada', 'cartas.relatorio', 'cartas.exportar',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'cartas_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cartas_gestao', 'guard_name' => 'web']);
        $voluntario = Role::firstOrCreate(['name' => 'cartas_voluntario', 'guard_name' => 'web']);
        $voluntario->syncPermissions(['cartas.ver', 'cartas.responder']);

        $this->regiao = Regiao::create(['nome' => 'Sudeste']);
        $this->estado = Estado::create(['nome' => 'São Paulo', 'sigla' => 'SP', 'regiao_id' => $this->regiao->id]);
        $this->municipio = Municipio::create([
            'nome' => 'São Paulo',
            'estado_id' => $this->estado->id,
            'regiao_id' => $this->regiao->id,
        ]);
    }

    /**
     * Cadastro completo com todas as perguntas preenchidas redireciona para aviso de verificação de email.
     */
    public function test_cadastro_completo_salva_usuario_e_redireciona_para_verificacao(): void
    {
        Notification::fake();

        $response = $this->post(route('cartas.register.store'), [
            'name' => 'Novo Voluntário',
            'email' => 'novo@exemplo.com',
            'password' => 'Senha123!@#',
            'password_confirmation' => 'Senha123!@#',
            'cpf' => '529.982.247-25',
            'telefone' => '(11) 99999-9999',
            'estado_id' => $this->estado->id,
            'municipio_id' => $this->municipio->id,
            'termos_aceitos' => '1',
            'cartas_tipo_vinculo' => 'petrobras',
            'cartas_limite_respostas' => 3,
        ]);

        $response->assertRedirect(route('cartas.verification.notice'));

        $this->assertDatabaseHas('users', [
            'email' => 'novo@exemplo.com',
            'cartas_tipo_vinculo' => 'petrobras',
            'cartas_limite_respostas' => 3,
        ]);

        $user = User::where('email', 'novo@exemplo.com')->first();
        Notification::assertSentTo($user, CartasVerifyEmailNotification::class);
    }

    /**
     * Validação rejeita o cadastro se as perguntas não forem preenchidas.
     */
    public function test_validacao_rejeita_sem_respostas(): void
    {
        $response = $this->post(route('cartas.register.store'), [
            'name' => 'Novo Voluntário',
            'email' => 'novo@exemplo.com',
            'password' => 'Senha123!@#',
            'password_confirmation' => 'Senha123!@#',
            'cpf' => '529.982.247-25',
            'telefone' => '(11) 99999-9999',
            'estado_id' => $this->estado->id,
            'municipio_id' => $this->municipio->id,
            'termos_aceitos' => '1',
        ]);

        $response->assertSessionHasErrors(['cartas_tipo_vinculo', 'cartas_limite_respostas']);
    }

    /**
     * Validação rejeita valores inválidos nas perguntas.
     */
    public function test_validacao_rejeita_valores_invalidos(): void
    {
        $response = $this->post(route('cartas.register.store'), [
            'name' => 'Novo Voluntário',
            'email' => 'novo@exemplo.com',
            'password' => 'Senha123!@#',
            'password_confirmation' => 'Senha123!@#',
            'cpf' => '529.982.247-25',
            'telefone' => '(11) 99999-9999',
            'estado_id' => $this->estado->id,
            'municipio_id' => $this->municipio->id,
            'termos_aceitos' => '1',
            'cartas_limite_respostas' => 10,
            'cartas_tipo_vinculo' => 'invalido',
        ]);

        $response->assertSessionHasErrors(['cartas_limite_respostas', 'cartas_tipo_vinculo']);
    }

    /**
     * Usuários com CPF já cadastrado no Engaja podem se cadastrar no Cartas.
     */
    public function test_usuario_engaja_pode_se_cadastrar_no_cartas_com_mesmo_cpf(): void
    {
        // Criar usuário Engaja com CPF
        $engajaUser = User::factory()->create([
            'sistema_origem' => User::SISTEMA_ENGAJA,
        ]);
        $engajaUser->participante->update([
            'cpf' => '52998224725',
            'municipio_id' => $this->municipio->id,
        ]);

        Notification::fake();

        // Cadastrar no Cartas com o mesmo CPF
        $response = $this->post(route('cartas.register.store'), [
            'name' => 'Mesmo CPF',
            'email' => 'cartas-novo@exemplo.com',
            'password' => 'Senha123!@#',
            'password_confirmation' => 'Senha123!@#',
            'cpf' => '529.982.247-25',
            'telefone' => '(11) 98888-8888',
            'estado_id' => $this->estado->id,
            'municipio_id' => $this->municipio->id,
            'termos_aceitos' => '1',
            'cartas_tipo_vinculo' => 'petrobras',
            'cartas_limite_respostas' => 1,
        ]);

        // Deve redirecionar para verificação (cadastro bem-sucedido)
        $response->assertRedirect(route('cartas.verification.notice'));

        // Deve existir o novo usuário com sistema_origem = cartas
        $this->assertDatabaseHas('users', [
            'email' => 'cartas-novo@exemplo.com',
            'sistema_origem' => User::SISTEMA_CARTAS,
        ]);
    }

    /**
     * Default de limite de cartas é 1 para usuários existentes.
     */
    public function test_default_limite_cartas_e_1(): void
    {
        $user = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
        ]);

        $user->refresh();
        $this->assertEquals(1, $user->cartas_limite_respostas);
    }

    /**
     * Distribuição prioriza voluntários Petrobras.
     */
    public function test_distribuicao_prioriza_petrobras(): void
    {
        $petroUser = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
            'cartas_tipo_vinculo' => User::VINCULO_PETROBRAS,
            'cartas_limite_respostas' => 3,
        ]);
        $petroUser->assignRole('cartas_voluntario');

        $comunidadeUser = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
            'cartas_tipo_vinculo' => User::VINCULO_COMUNIDADE,
            'cartas_limite_respostas' => 3,
        ]);
        $comunidadeUser->assignRole('cartas_voluntario');

        $gestor = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
        ]);
        $gestor->assignRole('cartas_gestao');

        $remetente = User::factory()->create([
            'sistema_origem' => User::SISTEMA_ENGAJA,
        ]);
        $remetente->participante->update([
            'municipio_id' => $this->municipio->id,
            'cpf' => '52998224725',
        ]);

        $eventoCartas = Evento::factory()->create(['is_cartas' => true]);

        Notification::fake();

        $this->actingAs($gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $remetente->id,
                'arquivo' => UploadedFile::fake()->createWithContent(
                    'carta.pdf',
                    file_get_contents(base_path('tests/Fixtures/cartas/exemplo-anexo.pdf'))
                ),
            ]);

        $carta = Carta::latest('id')->first();

        // A carta deve ir para o voluntário Petrobras primeiro
        $this->assertEquals($petroUser->id, $carta->voluntario_user_id);
    }

    /**
     * Voluntários que atingiram o limite não recebem novas cartas.
     */
    public function test_voluntario_no_limite_nao_recebe_cartas(): void
    {
        $voluntario = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
            'cartas_tipo_vinculo' => User::VINCULO_PETROBRAS,
            'cartas_limite_respostas' => 1,
        ]);
        $voluntario->assignRole('cartas_voluntario');

        $voluntario2 = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
            'cartas_tipo_vinculo' => User::VINCULO_COMUNIDADE,
            'cartas_limite_respostas' => 2,
        ]);
        $voluntario2->assignRole('cartas_voluntario');

        $gestor = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
        ]);
        $gestor->assignRole('cartas_gestao');

        $remetente = User::factory()->create([
            'sistema_origem' => User::SISTEMA_ENGAJA,
        ]);
        $remetente->participante->update([
            'municipio_id' => $this->municipio->id,
            'cpf' => '52998224725',
        ]);

        $eventoCartas = Evento::factory()->create(['is_cartas' => true]);

        // Dar uma carta ao voluntário 1 (atingindo seu limite de 1)
        Carta::create([
            'codigo' => '001',
            'educando_participante_id' => $remetente->participante->id,
            'voluntario_user_id' => $voluntario->id,
            'municipio_id' => $this->municipio->id,
            'status' => Carta::STATUS_AGUARDANDO_VOLUNTARIO,
            'criada_por' => $gestor->id,
            'atualizada_por' => $gestor->id,
        ]);

        Notification::fake();

        // Nova carta deve ir para voluntário 2 (ainda tem capacidade)
        $this->actingAs($gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $remetente->id,
                'arquivo' => UploadedFile::fake()->createWithContent(
                    'carta.pdf',
                    file_get_contents(base_path('tests/Fixtures/cartas/exemplo-anexo.pdf'))
                ),
            ]);

        $novaCarta = Carta::latest('id')->first();
        $this->assertEquals($voluntario2->id, $novaCarta->voluntario_user_id);
    }

    /**
     * O gestor pode ver e editar o limite e vínculo dos usuários.
     */
    public function test_gestor_pode_editar_limite_e_vinculo(): void
    {
        $admin = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
        ]);
        $admin->assignRole('cartas_admin');

        $voluntario = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
            'cartas_limite_respostas' => 1,
            'cartas_tipo_vinculo' => null,
        ]);
        $voluntario->assignRole('cartas_voluntario');

        $response = $this->actingAs($admin)->put(route('cartas.usuarios.update', $voluntario), [
            'name' => $voluntario->name,
            'email' => $voluntario->email,
            'role' => 'cartas_voluntario',
            'cartas_limite_respostas' => 4,
            'cartas_tipo_vinculo' => 'petrobras',
        ]);

        $response->assertRedirect(route('cartas.usuarios.index'));

        $voluntario->refresh();
        $this->assertEquals(4, $voluntario->cartas_limite_respostas);
        $this->assertEquals('petrobras', $voluntario->cartas_tipo_vinculo);
    }

    /**
     * A tabela de usuários no painel do gestor exibe cartas atribuídas e limite.
     */
    public function test_tabela_usuarios_exibe_cartas_e_vinculo(): void
    {
        $admin = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
            'cartas_limite_respostas' => 3,
            'cartas_tipo_vinculo' => User::VINCULO_PETROBRAS,
        ]);
        $admin->assignRole('cartas_admin');

        $response = $this->actingAs($admin)->get(route('cartas.usuarios.index'));

        $response->assertOk();
        $response->assertSee('Funcionário Petrobras');
    }

    /**
     * A tabela do dashboard exibe as cartas atribuídas e o limite do voluntário.
     */
    public function test_dashboard_exibe_cartas_limite(): void
    {
        $admin = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
        ]);
        $admin->assignRole('cartas_admin');

        $educando = Participante::factory()->create();
        $voluntario = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
            'cartas_limite_respostas' => 5,
        ]);
        $voluntario->assignRole('cartas_voluntario');

        Carta::create([
            'codigo' => '123',
            'educando_participante_id' => $educando->id,
            'voluntario_user_id' => $voluntario->id,
            'status' => 'aguardando_voluntario',
            'criada_por' => $admin->id,
            'atualizada_por' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('cartas.dashboard'));
        $response->assertOk();
        $response->assertSee('1 / 5');
    }
}
