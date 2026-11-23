<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartaInterleavingTest extends TestCase
{
    use RefreshDatabase;

    private function mensagem(Carta $carta, int $rodada, string $tipo, string $status): void
    {
        CartaMensagem::factory()->create([
            'carta_id' => $carta->id,
            'rodada' => $rodada,
            'tipo_remetente' => $tipo,
            'status' => $status,
        ]);
    }

    public function test_apos_carta_do_educando_e_a_vez_do_voluntario(): void
    {
        $carta = Carta::factory()->create();
        $this->mensagem($carta, 1, CartaMensagem::TIPO_REMETENTE_EDUCANDO, CartaMensagem::STATUS_APROVADA);

        $carta->load('ultimaMensagem');

        $this->assertSame(CartaMensagem::TIPO_REMETENTE_VOLUNTARIO, $carta->proximoTipoRemetente());
        $this->assertTrue($carta->podeVoluntarioEnviar());
        $this->assertFalse($carta->podeEducandoEnviar());
        $this->assertFalse($carta->temMensagemPendente());
    }

    public function test_apos_resposta_do_voluntario_e_a_vez_do_educando(): void
    {
        $carta = Carta::factory()->create();
        $this->mensagem($carta, 1, CartaMensagem::TIPO_REMETENTE_EDUCANDO, CartaMensagem::STATUS_APROVADA);
        $this->mensagem($carta, 2, CartaMensagem::TIPO_REMETENTE_VOLUNTARIO, CartaMensagem::STATUS_APROVADA);

        $carta->load('ultimaMensagem');

        $this->assertSame(CartaMensagem::TIPO_REMETENTE_EDUCANDO, $carta->proximoTipoRemetente());
        $this->assertTrue($carta->podeEducandoEnviar());
        $this->assertFalse($carta->podeVoluntarioEnviar());
    }

    public function test_mensagem_aguardando_verificacao_bloqueia_os_dois_lados(): void
    {
        $carta = Carta::factory()->create();
        $this->mensagem($carta, 1, CartaMensagem::TIPO_REMETENTE_EDUCANDO, CartaMensagem::STATUS_APROVADA);
        $this->mensagem($carta, 2, CartaMensagem::TIPO_REMETENTE_VOLUNTARIO, CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO);

        $carta->load('ultimaMensagem');

        $this->assertTrue($carta->temMensagemPendente());
        $this->assertNull($carta->proximoTipoRemetente());
        $this->assertFalse($carta->podeEducandoEnviar());
        $this->assertFalse($carta->podeVoluntarioEnviar());
    }

    public function test_mensagem_com_ajuste_solicitado_bloqueia_novos_envios(): void
    {
        $carta = Carta::factory()->create();
        $this->mensagem($carta, 1, CartaMensagem::TIPO_REMETENTE_EDUCANDO, CartaMensagem::STATUS_APROVADA);
        $this->mensagem($carta, 2, CartaMensagem::TIPO_REMETENTE_VOLUNTARIO, CartaMensagem::STATUS_AJUSTE_SOLICITADO);

        $carta->load('ultimaMensagem');

        $this->assertTrue($carta->temMensagemPendente());
        $this->assertFalse($carta->podeEducandoEnviar());
        $this->assertFalse($carta->podeVoluntarioEnviar());
    }
}
