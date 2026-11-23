<?php

namespace Tests\Feature\Cartas;

class CartasAutorizacaoTest extends CartasBaseTest
{
    public function test_voluntario_nao_pode_excluir_carta(): void
    {
        $carta = $this->criarCartaParaVoluntario();

        $response = $this->actingAs($this->voluntario)
            ->delete(route('cartas.cartas.destroy', $carta));

        $response->assertStatus(403);
    }

    public function test_voluntario_nao_pode_aprovar_mensagem(): void
    {
        [$carta, $mensagem] = $this->criarCartaComRespostaAguardandoVerificacao();

        $response = $this->actingAs($this->voluntario)
            ->post(route('cartas.mensagens.approve', $mensagem));

        $response->assertStatus(403);
    }

    public function test_voluntario_nao_pode_solicitar_ajuste(): void
    {
        [$carta, $mensagem] = $this->criarCartaComRespostaAguardandoVerificacao();

        $response = $this->actingAs($this->voluntario)
            ->post(route('cartas.mensagens.adjustment', $mensagem), [
                'parecer_verificacao' => 'Ajuste',
            ]);

        $response->assertStatus(403);
    }

    public function test_voluntario_nao_pode_cadastrar_carta_como_gestor(): void
    {
        $response = $this->actingAs($this->voluntario)
            ->post(route('cartas.cartas.store'), [
                'remetente_user_id' => $this->remetente->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_voluntario_nao_pode_ver_carta_de_outro_voluntario(): void
    {
        $carta = $this->criarCartaParaVoluntario(); // voluntario1

        $response = $this->actingAs($this->voluntario2)
            ->get(route('cartas.cartas.show', $carta));

        $response->assertStatus(403);
    }

    public function test_voluntario_pode_ver_sua_propria_carta(): void
    {
        $carta = $this->criarCartaParaVoluntario();

        $response = $this->actingAs($this->voluntario)
            ->get(route('cartas.cartas.show', $carta));

        $response->assertOk();
    }

    public function test_gestor_pode_ver_qualquer_carta(): void
    {
        $carta = $this->criarCartaParaVoluntario();

        $response = $this->actingAs($this->gestor)
            ->get(route('cartas.cartas.show', $carta));

        $response->assertOk();
    }

    public function test_usuario_engaja_nao_pode_acessar_dashboard_cartas(): void
    {
        // $this->remetente é do SISTEMA_ENGAJA
        $response = $this->actingAs($this->remetente)
            ->get(route('cartas.dashboard'));

        $response->assertStatus(403);
    }
}
