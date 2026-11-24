<?php

namespace Tests\Feature;

use App\Enums\OrigemAutorizacaoImagem;
use App\Exports\AutorizacoesImagemPorAcaoExport;
use App\Models\Atividade;
use App\Models\AutorizacaoImagem;
use App\Models\Evento;
use App\Models\User;
use App\Services\AutorizacaoImagemService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Concerns\InteractsWithAutorizacaoImagem;
use Tests\TestCase;

class AutorizacaoImagemImportAcaoTest extends TestCase
{
    use InteractsWithAutorizacaoImagem, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararAutorizacoes();
    }

    public function test_index_exibe_botao_de_importacao_somente_para_quem_pode_registrar(): void
    {
        $this->actingAs($this->gestor)
            ->get(route('autorizacoes-imagem.index', ['evento_id' => $this->acao->id]))
            ->assertOk()
            ->assertSee(route('autorizacoes-imagem.import', $this->acao), false);

        $somenteLeitura = $this->pessoaComDados();
        $somenteLeitura->givePermissionTo('autorizacao-imagem.ver');

        $this->actingAs($somenteLeitura)
            ->get(route('autorizacoes-imagem.index', ['evento_id' => $this->acao->id]))
            ->assertOk()
            ->assertDontSee(route('autorizacoes-imagem.import', $this->acao), false);
    }

    public function test_rejeita_usuario_sem_permissao_e_usuario_do_cartas(): void
    {
        $this->actingAs($this->pessoa)
            ->get(route('autorizacoes-imagem.import', $this->acao))
            ->assertForbidden();

        $usuarioCartas = $this->pessoaComDados(['sistema_origem' => User::SISTEMA_CARTAS]);
        $usuarioCartas->assignRole('administrador');

        $this->actingAs($usuarioCartas)
            ->get(route('autorizacoes-imagem.import', $this->acao))
            ->assertForbidden();

        $this->actingAs($usuarioCartas)
            ->get(route('autorizacoes-imagem.import.modelo', $this->acao))
            ->assertForbidden();
    }

    public function test_baixa_modelo_xlsx_com_cabecalhos_e_formato_texto(): void
    {
        $response = $this->actingAs($this->gestor)
            ->get(route('autorizacoes-imagem.import.modelo', $this->acao))
            ->assertOk()
            ->assertDownload('modelo_autorizacao_imagem.xlsx');

        $tmp = tempnam(sys_get_temp_dir(), 'modelo-auth-');
        try {
            file_put_contents($tmp, $response->streamedContent());
            $sheet = IOFactory::load($tmp)->getActiveSheet();
            $this->assertSame('nome', $sheet->getCell('A1')->getValue());
            $this->assertSame('cpf', $sheet->getCell('B1')->getValue());
            $this->assertSame('@', $sheet->getStyle('B2')->getNumberFormat()->getFormatCode());
        } finally {
            @unlink($tmp);
        }
    }

    public function test_importa_xlsx_e_csv_com_zeros_a_esquerda_e_registra_origem_propria_e_historico(): void
    {
        $this->vinculo($this->pessoa, presente: false);
        $segunda = $this->pessoaComDados(['name' => 'Maria da Silva']);
        $segunda->participante->update(['cpf' => '00987654321']);
        $this->vinculo($segunda, presente: true);

        // Upload via XLSX com máscara e zeros à esquerda + linha duplicada na planilha
        $sessionKey = $this->uploadXlsx([
            ['Nome Completo', 'CPF'],
            ['Participante desta ação', '012.345.678-90'],
            ['Participante desta ação', '01234567890'],
        ]);

        $this->actingAs($this->gestor)
            ->get(route('autorizacoes-imagem.import.preview', ['evento' => $this->acao, 'session_key' => $sessionKey]))
            ->assertOk()
            ->assertSee('Pronta para autorizar')
            ->assertSee('Duplicada na planilha');

        $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.confirmar', $this->acao), ['session_key' => $sessionKey])
            ->assertRedirect(route('autorizacoes-imagem.index', ['evento_id' => $this->acao->id]))
            ->assertSessionHas('success');

        $registro = AutorizacaoImagem::where('participante_id', $this->pessoa->participante->id)
            ->where('evento_id', $this->acao->id)
            ->sole();

        $this->assertTrue($registro->autorizado);
        $this->assertSame(OrigemAutorizacaoImagem::ImportacaoGestao->value, $registro->origem);
        $this->assertSame($this->gestor->id, $registro->registrado_por_user_id);
        $this->assertSame($this->acao->nome, $registro->acao_nome);
        $this->assertSame(1, $registro->versao);
        $this->assertSame(1, $registro->historico()->count());

        // Upload via CSV para a segunda participante
        $csvContent = "nome;cpf\nMaria da Silva;009.876.543-21\n";
        $csvFile = UploadedFile::fake()->createWithContent('lista.csv', $csvContent);
        $response = $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.upload', $this->acao), ['your_file' => $csvFile])
            ->assertRedirect();

        $csvSessionKey = $this->extractSessionKey($response->headers->get('Location'));

        $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.confirmar', $this->acao), ['session_key' => $csvSessionKey])
            ->assertRedirect(route('autorizacoes-imagem.index', ['evento_id' => $this->acao->id]));

        $this->assertDatabaseHas('autorizacoes_imagem', [
            'participante_id' => $segunda->participante->id,
            'evento_id' => $this->acao->id,
            'autorizado' => true,
            'origem' => OrigemAutorizacaoImagem::ImportacaoGestao->value,
        ]);

        // Verifica visualização na index e exportação XLSX com o novo rótulo de origem
        $this->actingAs($this->gestor)
            ->get(route('autorizacoes-imagem.index', ['evento_id' => $this->acao->id]))
            ->assertOk()
            ->assertSee('Importação em lote pela gestão');

        $query = app(AutorizacaoImagemService::class)->participantesDaAcao($this->acao);
        $export = new AutorizacoesImagemPorAcaoExport($this->acao, $query);
        $arquivo = tempnam(sys_get_temp_dir(), 'auth-import-exp-');
        try {
            file_put_contents($arquivo, app(Excel::class)->raw($export, Excel::XLSX));
            $sheet = IOFactory::load($arquivo)->getActiveSheet();
            $this->assertSame('Importação em lote pela gestão', $sheet->getCell('I2')->getValue());
        } finally {
            @unlink($arquivo);
        }
    }

    public function test_bloqueia_nao_encontrado_cpf_invalido_e_nao_inscrito_sem_criar_cadastros(): void
    {
        $foraDaAcao = $this->pessoaComDados(['name' => 'Pessoa fora da ação']);
        $foraDaAcao->participante->update(['cpf' => '99988877766']);

        $usersAntes = User::count();
        $sessionKey = $this->uploadXlsx([
            ['nome', 'cpf'],
            ['Inexistente', '55544433322'],
            ['CPF curto', '12345'],
            ['Pessoa fora da ação', '99988877766'],
        ]);

        $this->actingAs($this->gestor)
            ->get(route('autorizacoes-imagem.import.preview', ['evento' => $this->acao, 'session_key' => $sessionKey]))
            ->assertOk()
            ->assertSee('Participante não encontrado no sistema.')
            ->assertSee('tem 5 dígito(s)')
            ->assertSee('Participante não está inscrito nesta ação.');

        $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.confirmar', $this->acao), ['session_key' => $sessionKey])
            ->assertSessionHasErrors('rows');

        $this->assertSame($usersAntes, User::count());
        $this->assertDatabaseCount('autorizacoes_imagem', 0);
        $this->assertDatabaseCount('inscricaos', 0);
        $this->assertDatabaseCount('presencas', 0);
    }

    public function test_permite_editar_cpf_e_remover_pendencias_na_previa_para_liberar_confirmacao(): void
    {
        $this->vinculo($this->pessoa, presente: false);

        $sessionKey = $this->uploadXlsx([
            ['nome', 'cpf'],
            ['Participante com CPF errado', '0123456789'],
            ['Inexistente na base', '77766655544'],
        ]);

        // Corrige o CPF da linha 0 e marca a linha 1 para remoção
        $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.save', $this->acao), [
                'session_key' => $sessionKey,
                'rows' => [
                    0 => ['nome' => 'Participante desta ação', 'cpf' => '012.345.678-90'],
                    1 => ['nome' => 'Inexistente na base', 'cpf' => '77766655544'],
                ],
                'remove' => [
                    1 => '1',
                ],
            ])
            ->assertRedirect();

        $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.confirmar', $this->acao), ['session_key' => $sessionKey])
            ->assertRedirect(route('autorizacoes-imagem.index', ['evento_id' => $this->acao->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('autorizacoes_imagem', [
            'participante_id' => $this->pessoa->participante->id,
            'evento_id' => $this->acao->id,
            'autorizado' => true,
        ]);
    }

    public function test_botao_remover_todas_pendencias_limpa_erros_e_mantem_validos(): void
    {
        $this->vinculo($this->pessoa, presente: false);

        $sessionKey = $this->uploadXlsx([
            ['nome', 'cpf'],
            ['Participante desta ação', '01234567890'],
            ['Inexistente', '11122233344'],
        ]);

        $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.remove-pending', $this->acao), ['session_key' => $sessionKey])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.confirmar', $this->acao), ['session_key' => $sessionKey])
            ->assertRedirect(route('autorizacoes-imagem.index', ['evento_id' => $this->acao->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('autorizacoes_imagem', 1);
    }

    public function test_sobrescreve_recusa_anterior_com_historico_e_e_idempotente_para_ja_autorizado(): void
    {
        $this->vinculo($this->pessoa, presente: false);
        $this->respostaDaGestao($this->pessoa, false);

        $jaAutorizada = $this->pessoaComDados(['name' => 'Pessoa já autorizada']);
        $jaAutorizada->participante->update(['cpf' => '22233344455']);
        $this->vinculo($jaAutorizada, presente: false);
        $this->respostaDaGestao($jaAutorizada, true);

        $sessionKey = $this->uploadXlsx([
            ['nome', 'cpf'],
            ['Participante desta ação', '01234567890'],
            ['Pessoa já autorizada', '22233344455'],
        ]);

        $this->actingAs($this->gestor)
            ->get(route('autorizacoes-imagem.import.preview', ['evento' => $this->acao, 'session_key' => $sessionKey]))
            ->assertOk()
            ->assertSee('Alterará de Não autorizada para Autorizada')
            ->assertSee('Já autorizada (sem alteração)');

        $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.confirmar', $this->acao), ['session_key' => $sessionKey])
            ->assertRedirect(route('autorizacoes-imagem.index', ['evento_id' => $this->acao->id]))
            ->assertSessionHasNoErrors();

        $atualizada = AutorizacaoImagem::where('participante_id', $this->pessoa->participante->id)
            ->where('evento_id', $this->acao->id)
            ->sole();
        $this->assertTrue($atualizada->autorizado);
        $this->assertSame(2, $atualizada->versao);
        $this->assertSame(OrigemAutorizacaoImagem::ImportacaoGestao->value, $atualizada->origem);
        $this->assertSame(2, $atualizada->historico()->count());
        $this->assertFalse($atualizada->historico()->first()->autorizado_anterior);
        $this->assertTrue($atualizada->historico()->first()->autorizado);

        $preservada = AutorizacaoImagem::where('participante_id', $jaAutorizada->participante->id)
            ->where('evento_id', $this->acao->id)
            ->sole();
        $this->assertTrue($preservada->autorizado);
        $this->assertSame(1, $preservada->versao);
        $this->assertSame(OrigemAutorizacaoImagem::Gestao->value, $preservada->origem);
        $this->assertSame(1, $preservada->historico()->count());
    }

    public function test_desambiguacao_de_cpf_seleciona_vinculado_a_acao_e_mais_recente_entre_vinculados(): void
    {
        // Cenário 1: dois cadastros com mesmo CPF, apenas 1 vinculado à ação
        $antigoVinculado = $this->pessoaComDados(['name' => 'Cadastro vinculado']);
        $antigoVinculado->forceFill(['created_at' => now()->subDays(10)])->save();
        $antigoVinculado->participante->update(['cpf' => '33344455566']);
        $this->vinculo($antigoVinculado, presente: false);

        $recenteNaoVinculado = $this->pessoaComDados(['name' => 'Cadastro fora da ação']);
        $recenteNaoVinculado->forceFill(['created_at' => now()->subDay()])->save();
        $recenteNaoVinculado->participante->update(['cpf' => '33344455566']);

        // Cenário 2: dois cadastros com mesmo CPF, ambos vinculados à ação -> seleciona o mais recente
        $vinculadoAntigo = $this->pessoaComDados(['name' => 'Duplicado antigo']);
        $vinculadoAntigo->forceFill(['created_at' => now()->subDays(5)])->save();
        $vinculadoAntigo->participante->update(['cpf' => '44455566677']);
        $this->vinculo($vinculadoAntigo, presente: false);

        $vinculadoRecente = $this->pessoaComDados(['name' => 'Duplicado recente']);
        $vinculadoRecente->forceFill(['created_at' => now()])->save();
        $vinculadoRecente->participante->update(['cpf' => '44455566677']);
        $this->vinculo($vinculadoRecente, presente: false);

        $sessionKey = $this->uploadXlsx([
            ['nome', 'cpf'],
            ['Cadastro vinculado', '33344455566'],
            ['Duplicado recente', '44455566677'],
        ]);

        $this->actingAs($this->gestor)
            ->get(route('autorizacoes-imagem.import.preview', ['evento' => $this->acao, 'session_key' => $sessionKey]))
            ->assertOk()
            ->assertSee('foi selecionado o cadastro mais recente.');

        $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.confirmar', $this->acao), ['session_key' => $sessionKey])
            ->assertRedirect(route('autorizacoes-imagem.index', ['evento_id' => $this->acao->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('autorizacoes_imagem', [
            'participante_id' => $antigoVinculado->participante->id,
            'evento_id' => $this->acao->id,
            'autorizado' => true,
        ]);
        $this->assertDatabaseMissing('autorizacoes_imagem', [
            'participante_id' => $recenteNaoVinculado->participante->id,
            'evento_id' => $this->acao->id,
        ]);

        $this->assertDatabaseHas('autorizacoes_imagem', [
            'participante_id' => $vinculadoRecente->participante->id,
            'evento_id' => $this->acao->id,
            'autorizado' => true,
        ]);
        $this->assertDatabaseMissing('autorizacoes_imagem', [
            'participante_id' => $vinculadoAntigo->participante->id,
            'evento_id' => $this->acao->id,
        ]);
    }

    public function test_session_key_nao_pode_ser_reutilizada_em_outro_evento_ou_por_outro_usuario(): void
    {
        $this->vinculo($this->pessoa, presente: false);
        $sessionKey = $this->uploadXlsx([
            ['nome', 'cpf'],
            ['Participante desta ação', '01234567890'],
        ]);

        $outroEvento = Evento::factory()->create(['user_id' => $this->gestor->id]);
        $outroMomento = Atividade::factory()->create(['evento_id' => $outroEvento->id]);
        $this->vinculo($this->pessoa, $outroMomento, presente: false);

        // Tentativa de confirmar a mesma sessão em outro evento
        $this->actingAs($this->gestor)
            ->post(route('autorizacoes-imagem.import.confirmar', $outroEvento), ['session_key' => $sessionKey])
            ->assertRedirect(route('autorizacoes-imagem.import', $outroEvento))
            ->assertSessionHasErrors('your_file');

        // Tentativa por outro gestor autenticado
        $outroGestor = $this->pessoaComDados();
        $outroGestor->assignRole('administrador');

        $this->actingAs($outroGestor)
            ->post(route('autorizacoes-imagem.import.confirmar', $this->acao), ['session_key' => $sessionKey])
            ->assertRedirect(route('autorizacoes-imagem.import', $this->acao))
            ->assertSessionHasErrors('your_file');

        $this->assertDatabaseCount('autorizacoes_imagem', 0);
    }

    private function uploadXlsx(array $matrix): string
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        foreach ($matrix as $rIndex => $row) {
            foreach ($row as $cIndex => $value) {
                $col = chr(ord('A') + $cIndex);
                $sheet->setCellValueExplicit($col.($rIndex + 1), (string) $value, DataType::TYPE_STRING);
            }
        }

        $tmp = tempnam(sys_get_temp_dir(), 'auth-import-').'.xlsx';
        try {
            (new Xlsx($spreadsheet))->save($tmp);
            $spreadsheet->disconnectWorksheets();

            $file = new UploadedFile(
                $tmp,
                'autorizacoes.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true
            );

            $response = $this->actingAs($this->gestor)
                ->post(route('autorizacoes-imagem.import.upload', $this->acao), ['your_file' => $file])
                ->assertRedirect();

            return $this->extractSessionKey($response->headers->get('Location'));
        } finally {
            @unlink($tmp);
        }
    }

    private function extractSessionKey(?string $location): string
    {
        $this->assertNotNull($location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('session_key', $query);

        return (string) $query['session_key'];
    }
}
