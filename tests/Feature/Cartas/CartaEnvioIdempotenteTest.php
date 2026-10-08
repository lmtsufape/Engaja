<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\User;
use App\Notifications\Cartas\CartaRecebidaNotification;
use App\Services\Cartas\CartaTimbradoService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use RuntimeException;

class CartaEnvioIdempotenteTest extends CartasBaseTest
{
    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->spy(CartaTimbradoService::class);
        $this->actingAs($this->gestor);
    }

    public function test_repetir_envio_cria_uma_carta_uma_mensagem_e_uma_notificacao(): void
    {
        $token = (string) Str::uuid();

        $this->enviar($token)->assertRedirect(route('cartas.dashboard'));
        $this->enviar(strtoupper($token))->assertRedirect(route('cartas.dashboard'));

        $this->assertDatabaseCount('cartas', 1);
        $this->assertDatabaseCount('carta_mensagens', 1);
        $this->assertDatabaseCount('carta_eventos', 1);
        $this->assertDatabaseCount('inscricaos', 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
        Notification::assertSentTimes(CartaRecebidaNotification::class, 1);
        $this->app->make(CartaTimbradoService::class)->shouldHaveReceived('aplicarAnexo')->once();
    }

    public function test_nova_tentativa_pode_cadastrar_o_mesmo_remetente_e_pdf(): void
    {
        $this->enviar((string) Str::uuid())->assertRedirect(route('cartas.dashboard'));
        $this->enviar((string) Str::uuid())->assertRedirect(route('cartas.dashboard'));

        $this->assertDatabaseCount('cartas', 2);
        Notification::assertSentTimes(CartaRecebidaNotification::class, 2);
    }

    public function test_token_reutilizado_com_outro_pdf_e_rejeitado(): void
    {
        $token = (string) Str::uuid();
        $this->enviar($token)->assertRedirect(route('cartas.dashboard'));
        $arquivo = UploadedFile::fake()->createWithContent(
            'carta.pdf',
            file_get_contents(base_path('tests/Fixtures/cartas/exemplo-anexo.pdf'))."\n% outro conteudo\n"
        );

        $this->enviar($token, ['arquivo' => $arquivo])->assertSessionHasErrors('envio_token');

        $this->assertDatabaseCount('cartas', 1);
        Notification::assertSentTimes(CartaRecebidaNotification::class, 1);
    }

    public function test_token_reutilizado_com_outro_remetente_e_rejeitado(): void
    {
        $token = (string) Str::uuid();
        $this->enviar($token)->assertRedirect(route('cartas.dashboard'));
        $outroRemetente = User::factory()->create(['sistema_origem' => User::SISTEMA_ENGAJA]);

        $this->enviar($token, ['remetente_user_id' => $outroRemetente->id])
            ->assertSessionHasErrors('envio_token');

        $this->assertDatabaseCount('cartas', 1);
        Notification::assertSentTimes(CartaRecebidaNotification::class, 1);
    }

    public function test_chave_ausente_ou_invalida_nao_pode_burlar_a_protecao(): void
    {
        $this->enviar(null)->assertSessionHasErrors('envio_token');
        $this->enviar('invalido')->assertSessionHasErrors('envio_token');

        $this->assertDatabaseCount('cartas', 0);
        Notification::assertNothingSent();
    }

    public function test_chave_e_vinculada_ao_gestor_autenticado(): void
    {
        $token = (string) Str::uuid();
        $this->enviar($token)->assertRedirect(route('cartas.dashboard'));
        $outroGestor = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
        ]);
        $outroGestor->assignRole('cartas_gestao');

        $this->actingAs($outroGestor)->enviar($token)->assertRedirect(route('cartas.dashboard'));

        $this->assertDatabaseCount('cartas', 2);
        $this->assertDatabaseHas('cartas', ['criada_por' => $outroGestor->id, 'envio_token' => $token]);
    }

    public function test_repeticao_nao_recria_carta_excluida(): void
    {
        $token = (string) Str::uuid();
        $this->enviar($token)->assertRedirect(route('cartas.dashboard'));
        Carta::firstOrFail()->delete();

        $this->enviar($token)->assertSessionHasErrors('envio_token');

        $this->assertSame(1, Carta::withTrashed()->count());
        $this->assertSame(0, Carta::count());
        Notification::assertSentTimes(CartaRecebidaNotification::class, 1);
    }

    public function test_repeticao_e_reconhecida_mesmo_sem_voluntarios_disponiveis(): void
    {
        $token = (string) Str::uuid();
        $this->enviar($token)->assertRedirect(route('cartas.dashboard'));
        $this->voluntario->update(['cartas_limite_respostas' => 0]);
        $this->voluntario2->update(['cartas_limite_respostas' => 0]);

        $this->enviar($token)->assertRedirect(route('cartas.dashboard'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('cartas', 1);
        Notification::assertSentTimes(CartaRecebidaNotification::class, 1);
    }

    public function test_falha_no_processamento_permite_repetir_a_mesma_tentativa(): void
    {
        $token = (string) Str::uuid();
        $timbrado = $this->mock(CartaTimbradoService::class);
        $timbrado->shouldReceive('aplicarAnexo')->once()->andThrow(new RuntimeException('Falha simulada'));
        $timbrado->shouldReceive('aplicarAnexo')->once()->andReturnNull();
        $this->withoutExceptionHandling();

        try {
            $this->enviar($token);
            $this->fail('O processamento deveria falhar.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Falha simulada', $exception->getMessage());
        }

        $this->assertDatabaseCount('cartas', 0);
        $this->assertDatabaseCount('carta_mensagens', 0);
        Notification::assertNothingSent();
        $this->withExceptionHandling();

        $this->enviar($token)->assertRedirect(route('cartas.dashboard'));

        $this->assertDatabaseCount('cartas', 1);
        Notification::assertSentTimes(CartaRecebidaNotification::class, 1);
    }

    public function test_colisao_apos_consulta_inicial_recupera_o_envio_concluido(): void
    {
        $token = (string) Str::uuid();
        $outroEnvioConcluido = false;

        // Complete another request after the first lookup returned no rows.
        DB::listen(function (QueryExecuted $query) use ($token, &$outroEnvioConcluido) {
            if (! $outroEnvioConcluido
                && str_starts_with(strtolower($query->sql), 'select')
                && str_contains($query->sql, 'envio_token')
                && in_array($token, $query->bindings, true)) {
                $outroEnvioConcluido = true;
                $this->enviar($token)->assertRedirect(route('cartas.dashboard'));
            }
        });

        $this->enviar($token)->assertRedirect(route('cartas.dashboard'))->assertSessionHasNoErrors();

        $this->assertTrue($outroEnvioConcluido);
        $this->assertDatabaseCount('cartas', 1);
        $this->assertDatabaseCount('carta_mensagens', 1);
        $this->assertDatabaseCount('carta_eventos', 1);
        Notification::assertSentTimes(CartaRecebidaNotification::class, 1);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_mesma_chave_com_nome_de_arquivo_diferente_e_rejeitada(): void
    {
        $token = (string) Str::uuid();
        $this->enviar($token)->assertRedirect(route('cartas.dashboard'));

        $this->enviar($token, ['arquivo' => $this->pdfFalsoValido('outra-carta.pdf')])
            ->assertSessionHasErrors('envio_token');

        $this->assertDatabaseCount('cartas', 1);
        Notification::assertSentTimes(CartaRecebidaNotification::class, 1);
    }

    public function test_remetente_deve_ser_um_identificador_inteiro(): void
    {
        $this->enviar((string) Str::uuid(), ['remetente_user_id' => [$this->remetente->id]])
            ->assertSessionHasErrors('remetente_user_id');

        $this->assertDatabaseCount('cartas', 0);
        Notification::assertNothingSent();
    }

    public function test_dashboard_emite_uma_nova_chave_por_formulario(): void
    {
        $primeiraResposta = $this->get(route('cartas.dashboard'))->assertOk();
        $segundaResposta = $this->get(route('cartas.dashboard'))->assertOk();
        preg_match('/name="envio_token" value="([^"]+)"/', $primeiraResposta->getContent(), $primeiraChave);
        preg_match('/name="envio_token" value="([^"]+)"/', $segundaResposta->getContent(), $segundaChave);

        $this->assertTrue(Str::isUuid($primeiraChave[1]));
        $this->assertTrue(Str::isUuid($segundaChave[1]));
        $this->assertNotSame($primeiraChave[1], $segundaChave[1]);
    }

    public function test_erro_de_validacao_preserva_a_chave_para_correcao(): void
    {
        $token = (string) Str::uuid();
        $this->enviar($token, ['arquivo' => null])->assertSessionHasErrors('arquivo');

        $this->get(route('cartas.dashboard'))
            ->assertOk()
            ->assertSee('name="envio_token" value="'.$token.'"', false);
    }

    public function test_chave_invalida_e_substituida_no_formulario(): void
    {
        $this->enviar('invalido')->assertSessionHasErrors('envio_token');
        $resposta = $this->get(route('cartas.dashboard'))->assertOk();
        preg_match('/name="envio_token" value="([^"]+)"/', $resposta->getContent(), $chave);

        $this->assertTrue(Str::isUuid($chave[1]));
    }

    private function enviar(?string $token, array $overrides = []): TestResponse
    {
        return $this->post(route('cartas.cartas.store'), array_replace([
            'envio_token' => $token,
            'remetente_user_id' => $this->remetente->id,
            'arquivo' => $this->pdfFalsoValido(),
        ], $overrides));
    }
}
