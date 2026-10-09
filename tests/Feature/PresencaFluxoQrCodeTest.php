<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Eixo;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\Presenca;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testa o fluxo completo de confirmação de presença via QR Code / link público.
 *
 * Rota: GET  /presenca/{atividade}/confirmar           → exibe o formulário
 *       POST /presenca/{atividade}/confirmar           → processa a confirmação / abre modal para dados incompletos
 *       POST /presenca/{atividade}/demograficos        → salva dados demográficos e confirma presença
 *
 * Qualquer pessoa (mesmo deslogada via QR code) pode preencher os dados demográficos
 * pendentes do participante e confirmar a presença em um único fluxo.
 */
class PresencaFluxoQrCodeTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Helpers
    // =========================================================================

    /** Cria um usuário com todos os campos demográficos obrigatórios preenchidos. */
    private function criarUsuarioComDemograficos(array $extra = []): User
    {
        return User::factory()->create(array_merge([
            'identidade_genero' => 'Homem Cisgênero',
            'raca_cor' => 'Parda',
            'comunidade_tradicional' => 'Não',
            'faixa_etaria' => 'Adulto (18 a 59 anos)',
            'pcd' => 'Não',
            'orientacao_sexual' => 'Heterossexual',
        ], $extra));
    }

    /** Cria um usuário SEM dados demográficos. */
    private function criarUsuarioSemDemograficos(array $extra = []): User
    {
        return User::factory()->create($extra);
    }

    /** Cria uma Atividade com presença aberta. */
    private function criarAtividadeAtiva(): Atividade
    {
        $eixo = Eixo::create(['nome' => 'Eixo Teste']);
        $evento = Evento::factory()->create(['eixo_id' => $eixo->id]);

        return Atividade::factory()->create([
            'evento_id' => $evento->id,
            'presenca_ativa' => true,
        ]);
    }

    /** Retorna os dados demográficos válidos para o formulário. */
    private function dadosDemograficosValidos(): array
    {
        return [
            'identidade_genero' => 'Homem Cisgênero',
            'raca_cor' => 'Parda',
            'comunidade_tradicional' => 'Não',
            'faixa_etaria' => 'Adulto (18 a 59 anos)',
            'pcd' => 'Não',
            'orientacao_sexual' => 'Heterossexual',
        ];
    }

    // =========================================================================
    // 1. PÁGINA DO FORMULÁRIO (GET)
    // =========================================================================

    public function test_pagina_de_confirmacao_via_qr_e_acessivel_por_qualquer_pessoa(): void
    {
        $atividade = $this->criarAtividadeAtiva();

        $response = $this->get(route('presenca.confirmar', $atividade));

        $response->assertOk();
        $response->assertSee('E-mail, CPF ou telefone');
    }

    // =========================================================================
    // 2. DETECÇÃO DE DADOS DEMOGRÁFICOS INCOMPLETOS (POST /confirmar)
    // =========================================================================

    public function test_store_redireciona_com_token_quando_usuario_sem_demograficos(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioSemDemograficos(['email' => 'sem.dados@test.com']);
        Participante::firstOrCreate(['user_id' => $user->id]);

        // Visitante deslogado buscando por e-mail
        $response = $this
            ->from(route('presenca.confirmar', $atividade))
            ->post(route('presenca.store', $atividade), ['campo' => $user->email]);

        // Redireciona com as flags para abrir o modal de dados demográficos
        $response->assertRedirect(route('presenca.confirmar', $atividade));
        $response->assertSessionHas('demograficos_pendentes', true);
        $response->assertSessionHas('demograficos_user_token');
        $response->assertSessionHas('demograficos_user_nome', $user->name);
        $response->assertSessionHas('demograficos_campo_input', $user->email);

        // NÃO deve criar presença ainda
        $this->assertDatabaseCount('presencas', 0);
    }

    public function test_store_redireciona_com_token_quando_encontrado_por_cpf_sem_demograficos(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioSemDemograficos();
        $participante = Participante::firstOrCreate(['user_id' => $user->id]);
        $participante->update(['cpf' => '390.533.447-05']);

        $response = $this
            ->from(route('presenca.confirmar', $atividade))
            ->post(route('presenca.store', $atividade), ['campo' => '390.533.447-05']);

        $response->assertRedirect(route('presenca.confirmar', $atividade));
        $response->assertSessionHas('demograficos_pendentes', true);
        $response->assertSessionHas('demograficos_user_token');
        $this->assertDatabaseCount('presencas', 0);
    }

    public function test_store_redireciona_com_token_quando_encontrado_por_telefone_sem_demograficos(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioSemDemograficos();
        $participante = Participante::firstOrCreate(['user_id' => $user->id]);
        $participante->update(['telefone' => '85999999999']);

        $response = $this
            ->from(route('presenca.confirmar', $atividade))
            ->post(route('presenca.store', $atividade), ['campo' => '85999999999']);

        $response->assertRedirect(route('presenca.confirmar', $atividade));
        $response->assertSessionHas('demograficos_pendentes', true);
        $this->assertDatabaseCount('presencas', 0);
    }

    public function test_pagina_exibe_modal_e_nome_do_usuario_com_demograficos_pendentes(): void
    {
        $atividade = $this->criarAtividadeAtiva();

        $pageResponse = $this
            ->withSession([
                'demograficos_pendentes' => true,
                'demograficos_user_token' => encrypt(999),
                'demograficos_user_nome' => 'João Silva',
                'demograficos_campo_input' => 'joao@test.com',
            ])
            ->get(route('presenca.confirmar', $atividade));

        $pageResponse->assertOk();
        $pageResponse->assertSee('modalDemograficos');
        $pageResponse->assertSee('João Silva');
        $pageResponse->assertSee('Preenchendo dados de');
    }

    // =========================================================================
    // 3. SALVAR DEMOGRÁFICOS E CONFIRMAR PRESENÇA (POST /demograficos)
    // =========================================================================

    public function test_salvar_demograficos_e_confirmar_preenche_dados_e_cria_presenca(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioSemDemograficos(['email' => 'preenchendo@test.com']);
        Participante::firstOrCreate(['user_id' => $user->id]);

        $this->assertFalse($user->demograficosCompletos());

        // Qualquer pessoa (mesmo visitante deslogado) envia os dados via formulário
        $response = $this->post(route('presenca.demograficos', $atividade), array_merge(
            $this->dadosDemograficosValidos(),
            ['user_token' => encrypt($user->id)]
        ));

        // Redireciona para a página de confirmação com sucesso
        $response->assertRedirect(route('presenca.confirmar', $atividade->id));
        $response->assertSessionHas('success-presenca', 'Presença confirmada com sucesso!');
        $response->assertSessionHas('usuario_nome', $user->name);

        // Os dados demográficos do usuário são atualizados diretamente na tabela users
        $user->refresh();
        $this->assertTrue($user->demograficosCompletos());
        $this->assertEquals('Homem Cisgênero', $user->identidade_genero);
        $this->assertEquals('Parda', $user->raca_cor);

        // A presença deve ter sido criada
        $this->assertDatabaseCount('presencas', 1);
        $this->assertDatabaseHas('presencas', ['status' => 'presente']);
    }

    public function test_salvar_demograficos_cria_inscricao_automaticamente(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioSemDemograficos();
        Participante::firstOrCreate(['user_id' => $user->id]);

        $this->assertDatabaseCount('inscricaos', 0);

        $this->post(route('presenca.demograficos', $atividade), array_merge(
            $this->dadosDemograficosValidos(),
            ['user_token' => encrypt($user->id)]
        ));

        $this->assertDatabaseCount('inscricaos', 1);
        $this->assertDatabaseHas('inscricaos', [
            'atividade_id' => $atividade->id,
            'evento_id' => $atividade->evento_id,
        ]);
    }

    public function test_salvar_demograficos_gera_token_de_avaliacao(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioSemDemograficos();
        Participante::firstOrCreate(['user_id' => $user->id]);

        $response = $this->post(route('presenca.demograficos', $atividade), array_merge(
            $this->dadosDemograficosValidos(),
            ['user_token' => encrypt($user->id)]
        ));

        $response->assertSessionHas('avaliacao_token');
        $response->assertSessionHas('avaliacao_disponivel', true);
    }

    public function test_salvar_demograficos_falha_com_token_invalido(): void
    {
        $atividade = $this->criarAtividadeAtiva();

        $response = $this->post(route('presenca.demograficos', $atividade), array_merge(
            $this->dadosDemograficosValidos(),
            ['user_token' => 'token-invalido-nao-criptografado']
        ));

        $response->assertRedirect(route('presenca.confirmar', $atividade));
        $response->assertSessionHas('error');
        $this->assertDatabaseCount('presencas', 0);
    }

    public function test_salvar_demograficos_falha_sem_campos_obrigatorios(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioSemDemograficos();

        $response = $this
            ->from(route('presenca.confirmar', $atividade))
            ->post(route('presenca.demograficos', $atividade), [
                'user_token' => encrypt($user->id),
                // Campos obrigatórios ausentes intencionalmente
            ]);

        $response->assertSessionHasErrors([
            'identidade_genero',
            'raca_cor',
            'comunidade_tradicional',
            'faixa_etaria',
            'pcd',
            'orientacao_sexual',
        ]);
        $this->assertDatabaseCount('presencas', 0);
    }

    public function test_salvar_demograficos_nao_duplica_presenca_em_segunda_chamada(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioSemDemograficos();
        Participante::firstOrCreate(['user_id' => $user->id]);

        $payload = array_merge($this->dadosDemograficosValidos(), ['user_token' => encrypt($user->id)]);

        $this->post(route('presenca.demograficos', $atividade), $payload);
        $this->assertDatabaseCount('presencas', 1);

        $this->post(route('presenca.demograficos', $atividade), $payload);
        $this->assertDatabaseCount('presencas', 1);
    }

    // =========================================================================
    // 4. CONFIRMAÇÃO BEM-SUCEDIDA VIA BUSCA DIRETA (dados já completos)
    // =========================================================================

    public function test_confirmacao_bem_sucedida_por_email_quando_dados_completos(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioComDemograficos(['email' => 'confirmado@test.com']);
        Participante::firstOrCreate(['user_id' => $user->id]);

        $response = $this->post(route('presenca.store', $atividade), ['campo' => $user->email]);

        $response->assertRedirect(route('presenca.confirmar', $atividade->id));
        $response->assertSessionHas('success-presenca', 'Presença confirmada com sucesso!');
        $response->assertSessionHas('usuario_nome', $user->name);

        $this->assertDatabaseCount('presencas', 1);
        $this->assertDatabaseHas('presencas', ['status' => 'presente']);
    }

    public function test_confirmacao_bem_sucedida_define_avaliacao_respondida_como_false(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioComDemograficos(['email' => 'flag@test.com']);
        Participante::firstOrCreate(['user_id' => $user->id]);

        $this->post(route('presenca.store', $atividade), ['campo' => $user->email]);

        $presenca = Presenca::first();
        $this->assertNotNull($presenca);
        $this->assertFalse((bool) $presenca->avaliacao_respondida);
    }

    // =========================================================================
    // 5. USUÁRIO / PARTICIPANTE NÃO ENCONTRADO
    // =========================================================================

    public function test_confirmacao_falha_quando_campo_nao_corresponde_a_nenhum_registro(): void
    {
        $atividade = $this->criarAtividadeAtiva();

        $response = $this
            ->from(route('presenca.confirmar', $atividade))
            ->post(route('presenca.store', $atividade), ['campo' => 'naoexiste@nenhum.com']);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $response->assertSessionHas('show_register_button', true);
        $this->assertDatabaseCount('presencas', 0);
    }

    public function test_campo_obrigatorio_e_validado_no_post(): void
    {
        $atividade = $this->criarAtividadeAtiva();

        $response = $this
            ->from(route('presenca.confirmar', $atividade))
            ->post(route('presenca.store', $atividade), ['campo' => '']);

        $response->assertSessionHasErrors('campo');
        $this->assertDatabaseCount('presencas', 0);
    }

    // =========================================================================
    // 6. CHECKIN AUTENTICADO (/atividades/{id}/presenca/checkin)
    // =========================================================================

    public function test_checkin_autenticado_e_bloqueado_sem_dados_demograficos(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioSemDemograficos();

        $response = $this
            ->actingAs($user)
            ->from(route('atividades.show', $atividade))
            ->post(route('atividades.presenca.checkin', $atividade));

        $response->assertRedirect();
        $response->assertSessionHas('erro_demograficos');
        $this->assertDatabaseCount('presencas', 0);
    }

    public function test_checkin_autenticado_bem_sucedido_cria_presenca(): void
    {
        $atividade = $this->criarAtividadeAtiva();
        $user = $this->criarUsuarioComDemograficos();

        $response = $this
            ->actingAs($user)
            ->from(route('atividades.show', $atividade))
            ->post(route('atividades.presenca.checkin', $atividade));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Presença confirmada com sucesso!');
        $this->assertDatabaseCount('presencas', 1);
        $this->assertDatabaseHas('presencas', ['status' => 'presente']);
    }

    // =========================================================================
    // 7. MÉTODO demograficosCompletos() NO MODEL User
    // =========================================================================

    public function test_metodo_demograficos_completos_retorna_true_quando_todos_campos_preenchidos(): void
    {
        $user = $this->criarUsuarioComDemograficos();
        $this->assertTrue($user->demograficosCompletos());
    }

    public function test_metodo_demograficos_completos_retorna_false_quando_algum_campo_falta(): void
    {
        $camposObrigatorios = [
            'identidade_genero', 'raca_cor', 'comunidade_tradicional',
            'faixa_etaria', 'pcd', 'orientacao_sexual',
        ];

        foreach ($camposObrigatorios as $campo) {
            $dados = [
                'identidade_genero' => 'Homem Cisgênero',
                'raca_cor' => 'Parda',
                'comunidade_tradicional' => 'Não',
                'faixa_etaria' => 'Adulto (18 a 59 anos)',
                'pcd' => 'Não',
                'orientacao_sexual' => 'Heterossexual',
            ];
            $dados[$campo] = null;

            $user = User::factory()->create($dados);
            $this->assertFalse(
                $user->demograficosCompletos(),
                "Esperado false quando o campo '{$campo}' está nulo."
            );
        }
    }
}
