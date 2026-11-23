<?php

namespace Tests\Feature\Cartas;

use App\Models\Inscricao;
use Illuminate\Support\Str;

class CartaEnvioInscreveRemetenteTest extends CartasBaseTest
{
    public function test_enviar_carta_inscreve_automaticamente_o_remetente_na_acao_de_cartas(): void
    {
        $this->assertDatabaseMissing('inscricaos', [
            'evento_id' => $this->eventoCartas->id,
            'participante_id' => $this->educando->id,
        ]);

        $file = $this->pdfFalsoValido('carta.pdf');

        $response = $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $this->remetente->id,
                'arquivo' => $file,
            ]);

        $response->assertRedirect(route('cartas.dashboard'));

        $this->assertDatabaseHas('inscricaos', [
            'evento_id' => $this->eventoCartas->id,
            'participante_id' => $this->educando->id,
        ]);
    }

    public function test_enviar_carta_nao_duplica_inscricao_de_remetente_ja_inscrito(): void
    {
        Inscricao::create([
            'evento_id' => $this->eventoCartas->id,
            'participante_id' => $this->educando->id,
        ]);

        $file = $this->pdfFalsoValido('carta.pdf');

        $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $this->remetente->id,
                'arquivo' => $file,
            ]);

        $this->assertDatabaseCount('inscricaos', 1);
    }
}
