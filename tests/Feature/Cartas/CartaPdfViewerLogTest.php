<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\CartaMensagem;
use App\Services\Cartas\CartaViewerLogger;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Monolog\Handler\TestHandler;

class CartaPdfViewerLogTest extends CartasBaseTest
{
    private TestHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('logging.channels.'.CartaViewerLogger::CANAL, [
            'driver' => 'monolog',
            'handler' => TestHandler::class,
        ]);

        Log::forgetChannel(CartaViewerLogger::CANAL);
        $this->handler = Log::channel(CartaViewerLogger::CANAL)->getLogger()->getHandlers()[0];
    }

    private function mensagemComPdf(): CartaMensagem
    {
        $carta = $this->criarCartaParaVoluntario();
        $mensagem = $carta->mensagens()->first();

        Storage::disk('local')->put('cartas/anexos/teste.pdf', "%PDF-1.4\nconteudo");

        $mensagem->update([
            'anexo_original_path' => 'cartas/anexos/teste.pdf',
            'anexo_original_nome' => 'teste.pdf',
            'anexo_original_mime' => 'application/pdf',
        ]);

        return $mensagem->fresh();
    }

    /**
     * Todas as linhas gravadas no canal, na ordem.
     *
     * @return array<int, string>
     */
    private function linhas(): array
    {
        return array_map(
            fn ($registro) => $registro->message,
            $this->handler->getRecords()
        );
    }

    private function primeiraLinhaCom(string $trecho): string
    {
        foreach ($this->linhas() as $linha) {
            if (str_contains($linha, $trecho)) {
                return $linha;
            }
        }

        $this->fail("Nenhuma linha de log contém \"{$trecho}\". Linhas: ".implode(' | ', $this->linhas()));
    }

    public function test_preview_bem_sucedido_registra_as_quatro_etapas_do_servidor(): void
    {
        $mensagem = $this->mensagemComPdf();

        $this->actingAs($this->voluntario)
            ->get(route('cartas.mensagens.preview', $mensagem))
            ->assertOk();

        $this->assertStringContainsString(
            "msg#{$mensagem->id} etapa 1/6 autorizacao: OK",
            $this->primeiraLinhaCom('etapa 1/6')
        );
        $this->assertStringContainsString('etapa 2/6 arquivo: OK', $this->primeiraLinhaCom('etapa 2/6'));
        $this->assertStringContainsString('anexo_original', $this->primeiraLinhaCom('etapa 2/6'));
        $this->assertStringContainsString('etapa 3/6 conteudo: OK', $this->primeiraLinhaCom('etapa 3/6'));
        $this->assertStringContainsString('etapa 4/6 entrega: OK', $this->primeiraLinhaCom('etapa 4/6'));
        $this->assertStringContainsString('application/pdf', $this->primeiraLinhaCom('etapa 4/6'));
    }

    public function test_arquivo_ausente_no_disco_falha_na_etapa_2(): void
    {
        $mensagem = $this->mensagemComPdf();
        Storage::disk('local')->delete('cartas/anexos/teste.pdf');

        $this->actingAs($this->voluntario)
            ->get(route('cartas.mensagens.preview', $mensagem))
            ->assertNotFound();

        $linha = $this->primeiraLinhaCom('etapa 2/6');
        $this->assertStringContainsString('arquivo: FALHOU', $linha);
        $this->assertStringContainsString('nao encontrado no disco', $linha);

        $this->assertSame('ERROR', $this->handler->getRecords()[1]->level->getName());
    }

    public function test_arquivo_sem_assinatura_pdf_falha_na_etapa_3(): void
    {
        $mensagem = $this->mensagemComPdf();
        Storage::disk('local')->put('cartas/anexos/teste.pdf', '<html>erro</html>');

        $this->actingAs($this->voluntario)
            ->get(route('cartas.mensagens.preview', $mensagem))
            ->assertOk();

        $linha = $this->primeiraLinhaCom('etapa 3/6');
        $this->assertStringContainsString('conteudo: FALHOU', $linha);
        $this->assertStringContainsString('nao comeca com %PDF-', $linha);
    }

    public function test_acesso_negado_falha_na_etapa_1(): void
    {
        $mensagem = $this->mensagemComPdf();

        $this->actingAs($this->voluntario2)
            ->get(route('cartas.mensagens.preview', $mensagem))
            ->assertForbidden();

        $linha = $this->primeiraLinhaCom('etapa 1/6');
        $this->assertStringContainsString('autorizacao: FALHOU', $linha);
        $this->assertStringContainsString("usuario#{$this->voluntario2->id}", $linha);
    }

    public function test_abertura_da_carta_registra_uma_linha_com_o_visualizador(): void
    {
        $mensagem = $this->mensagemComPdf();

        $this->actingAs($this->voluntario)
            ->get(route('cartas.cartas.show', $mensagem->carta))
            ->assertOk();

        $linha = $this->primeiraLinhaCom('aberta por');
        $this->assertStringContainsString('voluntario#'.$this->voluntario->id, $linha);
        $this->assertStringContainsString("msg#{$mensagem->id}: pdfjs", $linha);
    }

    public function test_pdf_com_mime_errado_no_banco_gera_alerta(): void
    {
        $mensagem = $this->mensagemComPdf();
        $mensagem->update(['anexo_original_mime' => 'application/octet-stream']);

        $this->actingAs($this->voluntario)
            ->get(route('cartas.cartas.show', $mensagem->carta))
            ->assertOk();

        $this->assertStringContainsString(
            'arquivo nao renderizavel (mime=application/octet-stream)',
            $this->primeiraLinhaCom('aberta por')
        );
        $this->assertStringContainsString(
            'o pdf.js nao sera acionado',
            $this->primeiraLinhaCom('ATENCAO')
        );
    }

    public function test_navegador_registra_etapas_5_e_6(): void
    {
        $mensagem = $this->mensagemComPdf();

        $this->actingAs($this->voluntario)
            ->postJson(route('cartas.diagnostico.visualizador'), [
                'etapa' => 'download',
                'sucesso' => true,
                'detalhe' => '84.2 KB em 120ms',
                'mensagem_id' => $mensagem->id,
            ])
            ->assertOk();

        $this->actingAs($this->voluntario)
            ->postJson(route('cartas.diagnostico.visualizador'), [
                'etapa' => 'render',
                'sucesso' => false,
                'detalhe' => 'InvalidPDFException: Invalid PDF structure.',
                'mensagem_id' => $mensagem->id,
            ])
            ->assertOk();

        $this->assertStringContainsString(
            "msg#{$mensagem->id} etapa 5/6 download navegador: OK — 84.2 KB em 120ms",
            $this->primeiraLinhaCom('etapa 5/6')
        );
        $this->assertStringContainsString(
            'etapa 6/6 render navegador: FALHOU — InvalidPDFException',
            $this->primeiraLinhaCom('etapa 6/6')
        );
    }

    public function test_endpoint_de_diagnostico_exige_autenticacao(): void
    {
        $this->postJson(route('cartas.diagnostico.visualizador'), [
            'etapa' => 'render',
            'sucesso' => false,
        ])->assertUnauthorized();
    }
}
