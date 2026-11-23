<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaEvento;
use App\Models\Cartas\CartaMensagem;
use Illuminate\Support\Facades\Storage;

class CartasVoluntarioTest extends CartasBaseTest
{
    public function test_voluntario_pode_responder_carta_digitada(): void
    {
        $carta = $this->criarCartaParaVoluntario();

        $response = $this->actingAs($this->voluntario)
            ->post(route('cartas.cartas.respond', $carta), [
                'modo_resposta' => 'digitada',
                'texto' => 'Esta é a minha resposta ao educando.',
            ]);

        $response->assertRedirect(route('cartas.dashboard'));

        $this->assertDatabaseHas('cartas', [
            'id' => $carta->id,
            'status' => Carta::STATUS_AGUARDANDO_VERIFICACAO,
        ]);

        $mensagem = CartaMensagem::where('carta_id', $carta->id)
            ->where('tipo_remetente', CartaMensagem::TIPO_REMETENTE_VOLUNTARIO)
            ->first();

        $this->assertNotNull($mensagem);
        $this->assertEquals($this->voluntario->id, $mensagem->remetente_user_id);
        $this->assertEquals($this->educando->id, $mensagem->destinatario_participante_id);
        $this->assertEquals(CartaMensagem::CANAL_DIGITADA, $mensagem->canal_entrada);
        $this->assertEquals(CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO, $mensagem->status);
        $this->assertEquals('Esta é a minha resposta ao educando.', $mensagem->texto);
        $this->assertNotNull($mensagem->texto_resumo);

        $this->assertDatabaseHas('carta_eventos', [
            'carta_id' => $carta->id,
            'carta_mensagem_id' => $mensagem->id,
            'user_id' => $this->voluntario->id,
            'tipo' => CartaEvento::TIPO_MENSAGEM_ENVIADA,
        ]);
    }

    public function test_voluntario_pode_responder_carta_com_anexo_manuscrito(): void
    {
        $carta = $this->criarCartaParaVoluntario();
        $file = $this->pdfFalsoValido('resposta.pdf');

        $response = $this->actingAs($this->voluntario)
            ->post(route('cartas.cartas.respond', $carta), [
                'modo_resposta' => 'anexo_manuscrito',
                'arquivo' => $file,
            ]);

        $response->assertRedirect(route('cartas.dashboard'));

        $mensagem = CartaMensagem::where('carta_id', $carta->id)
            ->where('tipo_remetente', CartaMensagem::TIPO_REMETENTE_VOLUNTARIO)
            ->first();

        $this->assertEquals(CartaMensagem::CANAL_ANEXO_MANUSCRITO, $mensagem->canal_entrada);
        $this->assertNotNull($mensagem->anexo_original_path);
        $this->assertNotNull($mensagem->anexo_original_nome);
        $this->assertNotNull($mensagem->arquivo_final_path);
        Storage::disk('local')->assertExists($mensagem->arquivo_final_path);
    }

    public function test_voluntario_nao_pode_responder_carta_de_outro(): void
    {
        $carta = $this->criarCartaParaVoluntario();

        $response = $this->actingAs($this->voluntario2)
            ->post(route('cartas.cartas.respond', $carta), [
                'modo_resposta' => 'digitada',
                'texto' => 'Tentativa indevida.',
            ]);

        $response->assertStatus(403);
    }

    public function test_responder_incrementa_rodada_corretamente(): void
    {
        $carta = $this->criarCartaParaVoluntario();

        $this->actingAs($this->voluntario)
            ->post(route('cartas.cartas.respond', $carta), [
                'modo_resposta' => 'digitada',
                'texto' => 'Resposta rodada 2.',
            ]);

        $novaMensagem = CartaMensagem::where('carta_id', $carta->id)
            ->where('tipo_remetente', CartaMensagem::TIPO_REMETENTE_VOLUNTARIO)
            ->first();

        $this->assertEquals(2, $novaMensagem->rodada);
    }

    public function test_voluntario_pode_enviar_mensagem_ajustada(): void
    {
        [$carta, $mensagem] = $this->criarCartaComRespostaComAjusteSolicitado();

        $response = $this->actingAs($this->voluntario)
            ->put(route('cartas.mensagens.update-adjustment', $mensagem), [
                'modo_resposta' => 'digitada',
                'texto' => 'Mensagem ajustada corretamente.',
            ]);

        $response->assertRedirect(route('cartas.cartas.show', $carta));

        $mensagem->refresh();
        $this->assertEquals(CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO, $mensagem->status);
        $this->assertEquals('Mensagem ajustada corretamente.', $mensagem->texto);
        $this->assertNull($mensagem->parecer_verificacao);
        $this->assertNull($mensagem->verificada_por);
        $this->assertNull($mensagem->verificada_em);

        $carta->refresh();
        $this->assertEquals(Carta::STATUS_AGUARDANDO_VERIFICACAO, $carta->status);
    }

    public function test_outro_voluntario_nao_pode_ajustar_mensagem_alheia(): void
    {
        [$carta, $mensagem] = $this->criarCartaComRespostaComAjusteSolicitado();

        $response = $this->actingAs($this->voluntario2)
            ->put(route('cartas.mensagens.update-adjustment', $mensagem), [
                'modo_resposta' => 'digitada',
                'texto' => 'Invasão.',
            ]);

        $response->assertStatus(403);
    }

    public function test_nao_pode_ajustar_mensagem_que_nao_tem_ajuste_solicitado(): void
    {
        [$carta, $mensagem] = $this->criarCartaComRespostaAguardandoVerificacao();

        $response = $this->actingAs($this->voluntario)
            ->put(route('cartas.mensagens.update-adjustment', $mensagem), [
                'modo_resposta' => 'digitada',
                'texto' => 'Tentativa de ajuste quando nao solicitado.',
            ]);

        $response->assertStatus(403);
    }

    public function test_voluntario_pode_ajustar_mensagem_com_anexo_manuscrito(): void
    {
        [$carta, $mensagem] = $this->criarCartaComRespostaComAjusteSolicitado();

        $response = $this->actingAs($this->voluntario)
            ->put(route('cartas.mensagens.update-adjustment', $mensagem), [
                'modo_resposta' => 'anexo_manuscrito',
                'arquivo' => $this->pdfFalsoValido('ajuste.pdf'),
            ]);

        $response->assertRedirect();

        $mensagem->refresh();

        $this->assertEquals(CartaMensagem::CANAL_ANEXO_MANUSCRITO, $mensagem->canal_entrada);
        $this->assertNotNull($mensagem->anexo_original_path);
        $this->assertNotNull($mensagem->arquivo_final_path);
        Storage::disk('local')->assertExists($mensagem->arquivo_final_path);
    }

    public function test_voluntario_pode_iniciar_carta(): void
    {
        $file = $this->pdfFalsoValido('carta.pdf');

        $response = $this->actingAs($this->voluntario)
            ->post(route('cartas.voluntario.cartas.store'), [
                'destinatario_user_id' => $this->remetente->id,
                'arquivo' => $file,
            ]);

        $response->assertRedirect(route('cartas.dashboard'));

        $carta = Carta::first();
        $this->assertNotNull($carta);
        $this->assertEquals($this->educando->id, $carta->educando_participante_id);
        $this->assertEquals($this->voluntario->id, $carta->voluntario_user_id);
        $this->assertEquals(Carta::STATUS_AGUARDANDO_VERIFICACAO, $carta->status);
        $this->assertNotNull($carta->distribuida_em);

        $this->assertDatabaseHas('carta_mensagens', [
            'carta_id' => $carta->id,
            'remetente_user_id' => $this->voluntario->id,
            'destinatario_participante_id' => $this->educando->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO,
            'canal_entrada' => CartaMensagem::CANAL_ANEXO_MANUSCRITO,
            'status' => CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO,
        ]);

        $this->assertDatabaseHas('carta_eventos', [
            'carta_id' => $carta->id,
            'user_id' => $this->voluntario->id,
            'tipo' => CartaEvento::TIPO_MENSAGEM_ENVIADA,
        ]);
    }

    public function test_dashboard_prioriza_cartas_recebidas_e_com_ajuste(): void
    {
        // Prioridade menor primeiro: aguardando_voluntario (0), aguardando_ajuste (1), demais (2).
        $this->criarCartaComRespostaAguardandoVerificacao();
        $this->criarCartaParaVoluntario();
        $this->criarCartaComRespostaComAjusteSolicitado();

        $response = $this->actingAs($this->voluntario)->get(route('cartas.dashboard'));

        $response->assertOk();

        $ordemStatus = $response->viewData('cartas')->pluck('status')->all();

        $this->assertEqualsCanonicalizing(
            [Carta::STATUS_AGUARDANDO_VOLUNTARIO, Carta::STATUS_AGUARDANDO_AJUSTE],
            array_slice($ordemStatus, 0, 2),
        );
        $this->assertSame(Carta::STATUS_AGUARDANDO_VERIFICACAO, end($ordemStatus));
    }

    public function test_show_exibe_carta_centralizada_com_pdf_read_only(): void
    {
        $carta = $this->criarCartaParaVoluntario();

        $carta->mensagens()->first()->update([
            'status' => CartaMensagem::STATUS_APROVADA,
            'anexo_original_path' => 'cartas/exemplo.pdf',
            'anexo_original_nome' => 'exemplo.pdf',
            'anexo_original_mime' => 'application/pdf',
        ]);

        $response = $this->actingAs($this->voluntario)->get(route('cartas.cartas.show', $carta));

        $response->assertOk();
        // Lista lateral compacta e cabeçalho De/Para centralizado.
        $response->assertSee('cpe-msg-list', false);
        $response->assertSee('cpe-letter-header', false);
        // PDF renderizado como imagem (pdf.js), read-only e sem controles do navegador.
        $response->assertSee('cpe-letter-doc', false);
        $response->assertSee('data-pdf-src', false);
        $response->assertDontSee('<iframe', false);
        // Botão "Responder" (roxo, sem --ghost) ao lado do "Imprimir" quando o voluntário pode enviar.
        $response->assertSee('data-modal-open="respondCartaModal"', false);
    }
}
