<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Evento;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class WordExportTest extends TestCase
{
    use RefreshDatabase;

    private const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        return $admin;
    }

    /**
     * Um .docx é um arquivo ZIP — o conteúdo deve começar com a assinatura "PK".
     */
    private function assertDocxResponse(TestResponse $response): void
    {
        $response->assertOk();
        $this->assertStringContainsString(self::DOCX_MIME, $response->headers->get('content-type'));

        $conteudo = $response->streamedContent();
        $this->assertNotEmpty($conteudo);
        $this->assertSame('PK', substr($conteudo, 0, 2));
    }

    public function test_painel_gerencial_exporta_word(): void
    {
        $evento = Evento::factory()->create();
        Atividade::factory()->create(['evento_id' => $evento->id]);

        $response = $this->actingAs($this->admin())
            ->get(route('painel-gerencial.exportar', ['formato' => 'docx']));

        $this->assertDocxResponse($response);
    }

    public function test_relatorio_quantitativo_momento_exporta_word(): void
    {
        $evento = Evento::factory()->create();
        Atividade::factory()->create(['evento_id' => $evento->id]);

        $response = $this->actingAs($this->admin())
            ->get(route('relatorio-quantitativo.exportar-momento', ['formato' => 'docx']));

        $this->assertDocxResponse($response);
    }

    public function test_relatorio_quantitativo_total_geral_exporta_word(): void
    {
        $evento = Evento::factory()->create();
        Atividade::factory()->create(['evento_id' => $evento->id]);

        $response = $this->actingAs($this->admin())
            ->get(route('relatorio-quantitativo.exportar-total-geral', ['formato' => 'docx']));

        $this->assertDocxResponse($response);
    }

    public function test_usuarios_exporta_word(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('usuarios.export', ['formato' => 'docx']));

        $this->assertDocxResponse($response);
    }

    public function test_usuarios_sem_vinculo_exporta_word(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('usuarios.sem-vinculo.exportar', ['formato' => 'docx']));

        $this->assertDocxResponse($response);
    }

    public function test_evento_relatorios_exporta_word(): void
    {
        $evento = Evento::factory()->create();

        $response = $this->actingAs($this->admin())
            ->get(route('eventos.relatorios', ['evento' => $evento, 'tipo' => 'geral', 'formato' => 'docx']));

        $this->assertDocxResponse($response);
    }

    public function test_dashboard_atividades_exporta_word(): void
    {
        $evento = Evento::factory()->create();
        Atividade::factory()->create(['evento_id' => $evento->id]);

        $response = $this->actingAs($this->admin())
            ->get(route('dashboard.export', ['formato' => 'docx']));

        $this->assertDocxResponse($response);
    }

    public function test_matriz_presenca_exporta_word(): void
    {
        $evento = Evento::factory()->create();
        Atividade::factory()->create(['evento_id' => $evento->id]);

        $response = $this->actingAs($this->admin())
            ->get(route('dashboard.export.excel', ['evento_id' => $evento->id, 'formato' => 'docx']));

        $this->assertDocxResponse($response);
    }

    public function test_planejamento_exporta_word(): void
    {
        $evento = Evento::factory()->create();

        $response = $this->actingAs($this->admin())
            ->get(route('eventos.planejamento.pdf', ['evento' => $evento, 'formato' => 'docx']));

        $this->assertDocxResponse($response);
    }
}
