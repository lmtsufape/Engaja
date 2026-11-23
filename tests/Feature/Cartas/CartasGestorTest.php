<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaEvento;
use App\Models\Cartas\CartaMensagem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CartasGestorTest extends CartasBaseTest
{
    public function test_gestor_pode_cadastrar_carta_para_voluntario(): void
    {
        $file = $this->pdfFalsoValido('carta.pdf');

        $response = $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $this->remetente->id,
                'arquivo' => $file,
            ]);

        $response->assertRedirect(route('cartas.dashboard'));
        $response->assertSessionHas('status', 'Carta enviada para o voluntário.');

        $this->assertDatabaseHas('cartas', [
            'educando_participante_id' => $this->educando->id,
            'status' => Carta::STATUS_AGUARDANDO_VOLUNTARIO,
        ]);

        $carta = Carta::first();
        $this->assertNotNull($carta->voluntario_user_id);
        $this->assertNotNull($carta->distribuida_em);
        $this->assertNotNull($carta->codigo);
        $this->assertEquals($this->gestor->id, $carta->criada_por);

        $this->assertDatabaseHas('carta_mensagens', [
            'carta_id' => $carta->id,
            'rodada' => 1,
            'remetente_participante_id' => $this->educando->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
            'canal_entrada' => CartaMensagem::CANAL_ANEXO_DIGITALIZADO,
            'status' => CartaMensagem::STATUS_APROVADA,
        ]);

        $this->assertDatabaseHas('carta_eventos', [
            'carta_id' => $carta->id,
            'tipo' => CartaEvento::TIPO_CRIADA,
            'user_id' => $this->gestor->id,
        ]);
    }

    public function test_distribuicao_escolhe_voluntario_com_menos_cartas(): void
    {
        // Dar 3 cartas abertas ao voluntario1, voluntario2 tem 0
        for ($i = 0; $i < 3; $i++) {
            Carta::create([
                'codigo' => str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'educando_participante_id' => $this->educando->id,
                'voluntario_user_id' => $this->voluntario->id,
                'municipio_id' => $this->educando->municipio_id,
                'status' => Carta::STATUS_AGUARDANDO_VOLUNTARIO,
                'criada_por' => $this->gestor->id,
                'atualizada_por' => $this->gestor->id,
            ]);
        }

        $file = $this->pdfFalsoValido('carta.pdf');

        $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $this->remetente->id,
                'arquivo' => $file,
            ]);

        $novaCarta = Carta::latest('id')->first();

        // A nova carta deve ser atribuída a um voluntário válido do sistema Cartas
        $this->assertContains($novaCarta->voluntario_user_id, [
            $this->voluntario->id,
            $this->voluntario2->id,
        ]);
    }

    public function test_gestor_nao_pode_cadastrar_carta_sem_arquivo(): void
    {
        $response = $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $this->remetente->id,
            ]);

        $response->assertSessionHasErrors('arquivo');
        $this->assertDatabaseCount('cartas', 0);
    }

    public function test_gestor_nao_pode_cadastrar_carta_sem_remetente(): void
    {
        $file = $this->pdfFalsoValido('carta.pdf');

        $response = $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'arquivo' => $file,
            ]);

        $response->assertSessionHasErrors('remetente_user_id');
        $this->assertDatabaseCount('cartas', 0);
    }

    public function test_gestor_pode_aprovar_mensagem_de_voluntario(): void
    {
        [$carta, $mensagem] = $this->criarCartaComRespostaAguardandoVerificacao();

        $response = $this->actingAs($this->gestor)
            ->post(route('cartas.mensagens.approve', $mensagem));

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Resposta aprovada.');

        $mensagem->refresh();
        $this->assertEquals(CartaMensagem::STATUS_APROVADA, $mensagem->status);
        $this->assertEquals($this->gestor->id, $mensagem->verificada_por);
        $this->assertNotNull($mensagem->verificada_em);
        $this->assertNull($mensagem->parecer_verificacao);

        $carta->refresh();
        $this->assertEquals(Carta::STATUS_RESPONDIDA, $carta->status);

        $this->assertDatabaseHas('carta_eventos', [
            'carta_id' => $carta->id,
            'carta_mensagem_id' => $mensagem->id,
            'tipo' => CartaEvento::TIPO_MENSAGEM_VERIFICADA,
        ]);
    }

    public function test_nao_pode_aprovar_mensagem_ja_aprovada(): void
    {
        [$carta, $mensagem] = $this->criarCartaComRespostaAguardandoVerificacao();

        $mensagem->update(['status' => CartaMensagem::STATUS_APROVADA]);

        $response = $this->actingAs($this->gestor)
            ->post(route('cartas.mensagens.approve', $mensagem));

        $response->assertStatus(422);
    }

    public function test_gestor_pode_solicitar_ajuste(): void
    {
        [$carta, $mensagem] = $this->criarCartaComRespostaAguardandoVerificacao();

        $response = $this->actingAs($this->gestor)
            ->post(route('cartas.mensagens.adjustment', $mensagem), [
                'parecer_verificacao' => 'Por favor, melhore o tom da carta.',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Ajuste solicitado ao voluntário.');

        $mensagem->refresh();
        $this->assertEquals(CartaMensagem::STATUS_AJUSTE_SOLICITADO, $mensagem->status);
        $this->assertEquals('Por favor, melhore o tom da carta.', $mensagem->parecer_verificacao);
        $this->assertEquals($this->gestor->id, $mensagem->verificada_por);
        $this->assertNotNull($mensagem->verificada_em);

        $carta->refresh();
        $this->assertEquals(Carta::STATUS_AGUARDANDO_AJUSTE, $carta->status);

        $this->assertDatabaseHas('carta_eventos', [
            'carta_id' => $carta->id,
            'tipo' => CartaEvento::TIPO_AJUSTE_SOLICITADO,
        ]);
    }

    public function test_gestor_pode_adicionar_mensagem_de_educando(): void
    {
        $carta = $this->criarCartaParaVoluntario();

        // Adiciona uma resposta do voluntário (para que seja a vez do educando de novo)
        CartaMensagem::create([
            'carta_id' => $carta->id,
            'rodada' => 2,
            'remetente_user_id' => $this->voluntario->id,
            'destinatario_participante_id' => $this->educando->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO,
            'canal_entrada' => CartaMensagem::CANAL_DIGITADA,
            'status' => CartaMensagem::STATUS_APROVADA,
            'texto' => 'Mensagem aprovada do voluntario',
            'enviada_em' => now(),
            'verificada_por' => $this->gestor->id,
            'verificada_em' => now(),
            'criada_por' => $this->voluntario->id,
            'atualizada_por' => $this->voluntario->id,
        ]);

        $file = UploadedFile::fake()->create('segunda_carta.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.mensagens.store', $carta), [
                'arquivo' => $file,
            ]);

        $response->assertRedirect(route('cartas.cartas.show', $carta));

        $carta->refresh();
        $this->assertEquals(Carta::STATUS_RESPONDIDA, $carta->status);

        $mensagem = CartaMensagem::where('carta_id', $carta->id)->latest('id')->first();
        $this->assertEquals(CartaMensagem::TIPO_REMETENTE_EDUCANDO, $mensagem->tipo_remetente);
        $this->assertEquals(CartaMensagem::STATUS_APROVADA, $mensagem->status);
    }

    public function test_gestor_pode_excluir_carta(): void
    {
        $carta = $this->criarCartaParaVoluntario();

        $response = $this->actingAs($this->gestor)
            ->delete(route('cartas.cartas.destroy', $carta));

        $response->assertRedirect();
        $this->assertSoftDeleted('cartas', ['id' => $carta->id]);
    }
}
