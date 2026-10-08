<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use App\Models\User;
use Illuminate\Support\Str;

class CartasFluxoTest extends CartasBaseTest
{
    public function test_ciclo_completo_carta(): void
    {
        // 1. Gestor cadastra carta (aguardando_voluntario / aguardando_verificacao? Nao, vai direto para voluntário)
        $file1 = $this->pdfFalsoValido('carta1.pdf');

        $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $this->remetente->id,
                'arquivo' => $file1,
            ]);

        $carta = Carta::latest('id')->first();
        $this->assertEquals(Carta::STATUS_AGUARDANDO_VOLUNTARIO, $carta->status);

        $voluntarioDesignado = User::find($carta->voluntario_user_id);

        // 2. Voluntário selecionado responde
        $this->actingAs($voluntarioDesignado)
            ->post(route('cartas.cartas.respond', $carta), [
                'modo_resposta' => 'digitada',
                'texto' => 'Olá, aqui é o voluntário.',
            ]);

        $carta->refresh();
        $this->assertEquals(Carta::STATUS_AGUARDANDO_VERIFICACAO, $carta->status);

        $mensagem = CartaMensagem::where('carta_id', $carta->id)
            ->where('tipo_remetente', CartaMensagem::TIPO_REMETENTE_VOLUNTARIO)
            ->latest('id')
            ->first();

        // 3. Gestor analisa e pede ajuste
        $this->actingAs($this->gestor)
            ->post(route('cartas.mensagens.adjustment', $mensagem), [
                'parecer_verificacao' => 'Faltou se despedir.',
            ]);

        $carta->refresh();
        $mensagem->refresh();
        $this->assertEquals(Carta::STATUS_AGUARDANDO_AJUSTE, $carta->status);
        $this->assertEquals(CartaMensagem::STATUS_AJUSTE_SOLICITADO, $mensagem->status);

        // 4. Voluntário ajusta
        $this->actingAs($voluntarioDesignado)
            ->put(route('cartas.mensagens.update-adjustment', $mensagem), [
                'modo_resposta' => 'digitada',
                'texto' => 'Olá. Tchau.',
            ]);

        $carta->refresh();
        $mensagem->refresh();
        $this->assertEquals(Carta::STATUS_AGUARDANDO_VERIFICACAO, $carta->status);
        $this->assertEquals(CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO, $mensagem->status);

        // 5. Gestor aprova
        $this->actingAs($this->gestor)
            ->post(route('cartas.mensagens.approve', $mensagem));

        $carta->refresh();
        $mensagem->refresh();
        $this->assertEquals(Carta::STATUS_RESPONDIDA, $carta->status);
        $this->assertEquals(CartaMensagem::STATUS_APROVADA, $mensagem->status);
    }
}
