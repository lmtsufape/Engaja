<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Eixo;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Participante;
use App\Models\Presenca;
use App\Models\User;
use App\Services\ParticipanteImportIdentityResolver;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PresencaImportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Atividade $atividade;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesPermissionsSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('administrador');
        $eixo = Eixo::create(['nome' => 'Eixo importação']);
        $evento = Evento::factory()->create(['user_id' => $this->admin->id, 'eixo_id' => $eixo->id]);
        $this->atividade = Atividade::factory()->create(['evento_id' => $evento->id]);
        $this->actingAs($this->admin);
    }

    public function test_upload_bloqueia_todas_as_linhas_sem_nome_ou_identificacao_com_origem_real(): void
    {
        $counts = [User::count(), Participante::count()];
        $response = $this->upload([
            'Presenças' => [
                ['Nome válido', 'valido@example.com', '', 'presente'],
                ['', '', '', ''],
                ['', 'sem.nome@example.com', '', 'presente'],
                ['Sem identificação', '', '', 'presente'],
            ],
            'Outra aba' => [
                ['', '', '01234567890', 'presente'],
            ],
        ]);

        $response->assertSessionHasErrors(['rows.1.nome', 'rows.2.identificacao', 'rows.3.nome']);
        $errors = session('errors')->all();
        $this->assertStringContainsString('Linha 4', implode(' ', $errors));
        $this->assertStringContainsString('Linha 5', implode(' ', $errors));
        $this->assertStringNotContainsString('Aba "', implode(' ', $errors));
        $this->assertSame($counts, [User::count(), Participante::count()]);
        $this->assertSame(0, Inscricao::count());
        $this->assertSame(0, Presenca::count());
        $this->get(route('atividades.presencas.import', $this->atividade))
            ->assertOk()->assertSee('Linha 4')->assertSee('Linha 5')->assertDontSee('Outra aba');
    }

    public function test_upload_preserva_metadados_e_cpf_com_zero_inicial(): void
    {
        $response = $this->upload(['Presenças' => [
            ['Maria Ana Alves', '', '012.345.678-90', 'presente'],
            ['', '', '', ''],
            ['Outro Nome', 'OUTRO@example.com', '', 'ausente'],
        ]]);
        $response->assertRedirect();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $preview = session($query['session_key']);
        $this->assertSame('01234567890', $preview['rows'][0]['cpf']);
        $this->assertSame('outro@example.com', $preview['rows'][1]['email']);
        $this->assertSame(4, $preview['rows'][1]['linha_original']);
        $this->assertSame($this->admin->id, $preview['user_id']);
        $this->get($response->headers->get('Location'))->assertOk()->assertSee('Outro Nome');
    }

    public function test_confirmacao_sem_nome_bloqueia_todos_os_cenarios_sem_gravar(): void
    {
        $user = $this->profile('existente@example.com', '12345678901');
        $counts = [User::count(), Participante::count()];
        $key = $this->preview([
            $this->row(['email' => 'valido@example.com']),
            $this->row(['nome' => ' ', 'email' => $user->email]),
            $this->row(['nome' => '', 'email' => '', 'cpf' => '12345678901']),
            $this->row(['nome' => '', 'email' => 'novo@example.com', 'cpf' => '98765432100']),
        ]);
        $this->confirm($key)->assertSessionHasErrors(['rows.1.nome', 'rows.2.nome', 'rows.3.nome']);
        $this->assertSame($counts, [User::count(), Participante::count()]);
        $this->assertSame(0, Inscricao::count());
        $this->assertSame(0, Presenca::count());
        $this->assertNotNull(session($key));
    }

    public function test_sem_email_e_sem_cpf_bloqueia_inclusive_identificadores_com_apenas_mascara(): void
    {
        $key = $this->preview([$this->row(['email' => ' ', 'cpf' => ' . - '])]);
        $this->confirm($key)->assertSessionHasErrors('rows.0.identificacao');
        $this->assertSame(0, Presenca::count());
    }

    public function test_email_existente_reaproveita_perfil_e_preserva_cpf_e_nome(): void
    {
        $user = $this->profile('Existente@Example.com', '01234567890');
        $name = $user->name;
        $counts = [User::count(), Participante::count()];
        $key = $this->preview([$this->row([
            'email' => ' EXISTENTE@example.com ',
            'cpf' => null,
            'telefone' => '(81) 99999-0000',
        ])]);
        $this->confirm($key)->assertRedirect(route('eventos.show', $this->atividade->evento_id));
        $this->assertSame($counts, [User::count(), Participante::count()]);
        $this->assertSame('01234567890', $user->participante->fresh()->cpf);
        $this->assertSame('81999990000', $user->participante->fresh()->telefone);
        $this->assertSame($name, $user->fresh()->name);
        $this->assertSame(1, Presenca::count());
        $this->assertNull(session($key));
    }

    public function test_email_novo_cria_perfil_com_nome_informado_e_reimporta_sem_duplicar(): void
    {
        $row = $this->row(['nome' => 'Ana Silva', 'email' => 'ana@example.com']);
        $this->confirm($this->preview([$row]))->assertRedirect();
        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame('Ana Silva', $user->name);
        $this->assertNull($user->email_verified_at);
        $counts = [User::count(), Participante::count(), Inscricao::count(), Presenca::count()];
        $this->confirm($this->preview([array_merge($row, ['status' => 'ausente'])]))->assertRedirect();
        $this->assertSame($counts, [User::count(), Participante::count(), Inscricao::count(), Presenca::count()]);
        $this->assertSame('ausente', Presenca::first()->status);
    }

    public function test_cpf_sem_email_reaproveita_usuario_mais_recente_com_cpf_mascarado(): void
    {
        $older = $this->profile('antigo@example.com', '01234567890', [
            'created_at' => '2026-01-01 10:00:00', 'updated_at' => '2026-10-05 10:00:00',
        ]);
        $newer = $this->profile('recente@example.com', '012.345.678-90', [
            'created_at' => '2026-02-01 10:00:00',
        ]);
        $counts = [User::count(), Participante::count()];
        $this->confirm($this->preview([$this->row([
            'email' => '', 'cpf' => '012.345.678-90', 'telefone' => '81999990000',
        ])]))->assertRedirect();
        $this->assertSame($counts, [User::count(), Participante::count()]);
        $this->assertSame($newer->participante->id, Inscricao::first()->participante_id);
        $this->assertNull($older->participante->fresh()->telefone);
        $this->assertSame('recente@example.com', $newer->fresh()->email);
    }

    public function test_empate_de_data_do_cpf_escolhe_usuario_com_maior_id(): void
    {
        $this->profile('primeiro@example.com', '12345678901', ['created_at' => '2026-01-01 10:00:00']);
        $last = $this->profile('ultimo@example.com', '12345678901', ['created_at' => '2026-01-01 10:00:00']);
        $this->confirm($this->preview([$this->row(['email' => '', 'cpf' => '12345678901'])]))
            ->assertRedirect();
        $this->assertSame($last->participante->id, Inscricao::first()->participante_id);
    }

    public function test_cpf_novo_cria_ficticio_pelo_nome_e_reimportacao_nao_duplica(): void
    {
        $row = $this->row(['nome' => 'Mária   Ana Álves', 'email' => '', 'cpf' => '01234567890']);
        $this->confirm($this->preview([$row, $row]))->assertRedirect();
        $user = User::where('email', 'maria.ana.alves@ficticio.org.br')->firstOrFail();
        $this->assertSame('Mária   Ana Álves', $user->name);
        $this->assertNull($user->email_verified_at);
        $this->assertSame(1, Inscricao::count());
        $counts = [User::count(), Participante::count()];
        $this->confirm($this->preview([$row]))->assertRedirect();
        $this->assertSame($counts, [User::count(), Participante::count()]);
        $this->assertSame(1, Presenca::count());
    }

    public function test_homonimos_com_cpfs_diferentes_criam_ficticios_distintos(): void
    {
        $this->confirm($this->preview([
            $this->row(['email' => '', 'cpf' => '12345678901']),
            $this->row(['email' => '', 'cpf' => '98765432100']),
        ]))->assertRedirect();
        $emails = User::where('email', 'like', '%@ficticio.org.br')->pluck('email');
        $this->assertCount(2, $emails);
        $this->assertSame(2, $emails->unique()->count());
        $this->assertContains('maria.ana.alves@ficticio.org.br', $emails->all());
        $this->assertSame(2, Presenca::count());
    }

    public function test_email_ficticio_ocupado_nao_reaproveita_perfil_de_outra_pessoa(): void
    {
        $existing = $this->profile('maria.ana.alves@ficticio.org.br', '11111111111');
        $this->confirm($this->preview([$this->row(['email' => '', 'cpf' => '22222222222'])]))
            ->assertRedirect();
        $this->assertSame('11111111111', $existing->participante->fresh()->cpf);
        $this->assertNotSame($existing->participante->id, Inscricao::first()->participante_id);
        $email = Inscricao::first()->participante->user->email;
        $this->assertMatchesRegularExpression('/^maria\.ana\.alves\.[a-z0-9]{8}@ficticio\.org\.br$/', $email);
    }

    public function test_email_e_cpf_do_cartas_nao_sao_reutilizados_no_engaja(): void
    {
        $cartas = $this->profile('cartas@example.com', '12345678901', [
            'sistema_origem' => User::SISTEMA_CARTAS,
        ]);
        $this->confirm($this->preview([
            $this->row(['email' => '', 'cpf' => '12345678901']),
            $this->row(['email' => 'cartas@example.com', 'cpf' => null]),
        ]))->assertRedirect();
        $this->assertSame(2, Inscricao::count());
        $this->assertFalse(Inscricao::where('participante_id', $cartas->participante->id)->exists());
        $this->assertSame(2, User::where('email', 'cartas@example.com')->count());
    }

    public function test_quando_ambos_identificadores_existirem_email_continua_tendo_prioridade(): void
    {
        $emailUser = $this->profile('email@example.com', '11111111111');
        $this->profile('cpf@example.com', '22222222222');
        $this->confirm($this->preview([$this->row([
            'email' => 'email@example.com', 'cpf' => '22222222222',
        ])]))->assertRedirect();
        $this->assertSame($emailUser->participante->id, Inscricao::first()->participante_id);
    }

    public function test_cache_acompanha_cpf_alterado_em_linha_anterior(): void
    {
        $user = $this->profile('existente@example.com', '11111111111');
        $this->confirm($this->preview([
            $this->row(['email' => $user->email, 'cpf' => '22222222222']),
            $this->row(['email' => '', 'cpf' => '22222222222']),
            $this->row(['email' => $user->email, 'cpf' => null]),
        ]))->assertRedirect();
        $this->assertSame('22222222222', $user->participante->fresh()->cpf);
        $this->assertSame(1, Presenca::count());
    }

    public function test_perfil_excluido_bloqueia_importacao_sem_criar_duplicado(): void
    {
        $user = $this->profile('excluido@example.com', '12345678901');
        $user->delete();
        $counts = [User::withTrashed()->count(), Participante::withTrashed()->count()];
        foreach ([
            $this->row(['email' => $user->email]),
            $this->row(['email' => '', 'cpf' => '12345678901']),
        ] as $row) {
            $this->confirm($this->preview([$row]))->assertSessionHasErrors('rows');
        }
        $this->assertSame($counts, [User::withTrashed()->count(), Participante::withTrashed()->count()]);
        $this->assertSame(0, Presenca::count());
    }

    public function test_edicao_revalida_nome_identificadores_e_preserva_origem(): void
    {
        $row = $this->row();
        $key = $this->preview([$row]);
        foreach ([['nome' => ' '], ['email' => '', 'cpf' => '---']] as $edit) {
            $this->post(route('atividades.presencas.savepage', $this->atividade), [
                'session_key' => $key, 'rows' => [0 => $edit],
            ])->assertSessionHasErrors();
            $this->assertSame($row, session($key)['rows'][0]);
        }
        $this->post(route('atividades.presencas.savepage', $this->atividade), [
            'session_key' => $key,
            'rows' => [0 => ['email' => '', 'cpf' => '012.345.678-90']],
        ])->assertSessionHasNoErrors();
        $this->assertSame('01234567890', session($key)['rows'][0]['cpf']);
        $this->assertSame(2, session($key)['rows'][0]['linha_original']);
    }

    public function test_edicao_nao_aceita_metadados_de_origem_enviados_pelo_cliente(): void
    {
        $key = $this->preview([$this->row()]);
        $this->post(route('atividades.presencas.savepage', $this->atividade), [
            'session_key' => $key,
            'rows' => [0 => ['nome' => 'Nome novo', 'linha_original' => 999]],
        ])->assertSessionHasErrors('rows.0');
        $this->assertSame(2, session($key)['rows'][0]['linha_original']);
    }

    public function test_sessao_de_outra_atividade_ou_usuario_nao_pode_ser_confirmada(): void
    {
        $key = $this->preview([$this->row()]);
        $other = Atividade::factory()->create(['evento_id' => $this->atividade->evento_id]);
        $this->post(route('atividades.presencas.confirmar', $other), ['session_key' => $key])
            ->assertSessionHasErrors('rows');
        $another = User::factory()->create();
        $another->assignRole('administrador');
        $this->actingAs($another);
        $this->confirm($key)->assertSessionHasErrors('rows');
        $this->assertSame(0, Presenca::count());
    }

    public function test_inscricao_excluida_e_restaurada_e_presenca_existente_e_atualizada(): void
    {
        $user = $this->profile('existente@example.com', '12345678901');
        $inscricao = Inscricao::create([
            'participante_id' => $user->participante->id,
            'evento_id' => $this->atividade->evento_id,
            'atividade_id' => null,
            'ouvinte' => true,
        ]);
        $inscricao->delete();
        $row = $this->row(['email' => $user->email]);
        $this->confirm($this->preview([$row]))->assertRedirect();
        $this->assertFalse($inscricao->fresh()->trashed());
        $this->assertSame($this->atividade->id, $inscricao->fresh()->atividade_id);
        $this->assertFalse((bool) $inscricao->fresh()->ouvinte);
        $this->confirm($this->preview([array_merge($row, ['status' => 'justificado', 'justificativa' => 'Teste'])]))
            ->assertRedirect();
        $this->assertSame(1, Inscricao::count());
        $this->assertSame(1, Presenca::count());
        $this->assertSame('Teste', Presenca::first()->justificativa);
    }

    public function test_falha_durante_gravacao_reverte_todo_lote_e_mantem_previa(): void
    {
        $counts = [User::count(), Participante::count()];
        $roleCount = DB::table('model_has_roles')->count();
        $resolver = new class extends ParticipanteImportIdentityResolver
        {
            private int $calls = 0;

            public function resolve(array $row): Participante
            {
                if (++$this->calls === 2) {
                    throw ValidationException::withMessages(['rows' => 'Falha simulada.']);
                }

                return parent::resolve($row);
            }
        };
        $this->app->instance(ParticipanteImportIdentityResolver::class, $resolver);
        $key = $this->preview([$this->row(), $this->row(['email' => 'segunda@example.com'])]);
        $this->confirm($key)->assertSessionHasErrors('rows');
        $this->assertSame($counts, [User::count(), Participante::count()]);
        $this->assertSame(0, Inscricao::count());
        $this->assertSame(0, Presenca::count());
        $this->assertSame($roleCount, DB::table('model_has_roles')->count());
        $this->assertNotNull(session($key));
    }

    public function test_confirmacao_concorrente_e_bloqueada_antes_da_gravacao(): void
    {
        $connection = DB::connection();
        $config = $connection->getConfig();
        config(['database.connections.presenca_lock_test' => $config]);
        $other = DB::connection('presenca_lock_test');
        $other->beginTransaction();
        try {
            $other->selectOne('SELECT pg_advisory_xact_lock(?, ?)', [17012026, 1]);
            $key = $this->preview([$this->row()]);
            $this->confirm($key)->assertSessionHasErrors('rows');
            $this->assertSame(0, Presenca::count());
            $this->assertNotNull(session($key));
        } finally {
            $other->rollBack();
            DB::purge('presenca_lock_test');
        }
    }

    #[DataProvider('formats')]
    public function test_fluxo_completo_nos_formatos_aceitos(string $format): void
    {
        $response = $this->upload(['Presenças' => [
            ['Maria Ana Alves', '', '012.345.678-90', 'presente'],
            ['', '', '', ''],
            ['Maria Ana Alves', '', '012.345.678-90', 'ausente'],
        ]], $format);
        $response->assertRedirect()->assertSessionHasNoErrors();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $key = $query['session_key'];
        $this->assertSame(4, session($key)['rows'][1]['linha_original']);
        $this->confirm($key)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(1, Presenca::count());
        $this->assertSame('ausente', Presenca::first()->status);
        $this->assertSame('01234567890', Inscricao::first()->participante->cpf);
    }

    public static function formats(): array
    {
        return [['xlsx'], ['xls'], ['csv']];
    }

    #[DataProvider('auxiliarySheets')]
    public function test_upload_ignora_abas_auxiliares_e_preserva_abas_visiveis(string $name, string $state): void
    {
        $response = $this->upload([
            'Participantes' => [
                ['Primeira Pessoa', 'primeira@example.com', '', 'presente'],
                ['', '', '', ''],
                ['Segunda Pessoa', 'segunda@example.com', '', 'ausente'],
            ],
            $name => [
                ['', '', '', 'presente'],
                ['', '', '', 'ausente'],
            ],
            'Outro grupo' => [
                ['Terceira Pessoa', 'terceira@example.com', '', 'justificado'],
            ],
        ], 'xlsx', [$name => $state]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $rows = session($query['session_key'])['rows'];

        $this->assertCount(3, $rows);
        $this->assertSame(['Primeira Pessoa', 'Segunda Pessoa', 'Terceira Pessoa'], array_column($rows, 'nome'));
        $this->assertSame(['Participantes', 'Participantes', 'Outro grupo'], array_column($rows, 'aba_original'));
        $this->assertSame([2, 4, 2], array_column($rows, 'linha_original'));
    }

    public static function auxiliarySheets(): array
    {
        return [
            'validacao oculta' => ['_valid', Worksheet::SHEETSTATE_HIDDEN],
            'validacao muito oculta' => ['_valid', Worksheet::SHEETSTATE_VERYHIDDEN],
            'validacao visivel' => ['_valid', Worksheet::SHEETSTATE_VISIBLE],
            'validacao com maiusculas' => ['_VALID', Worksheet::SHEETSTATE_VISIBLE],
            'outra aba oculta' => ['Apoio', Worksheet::SHEETSTATE_HIDDEN],
            'outra aba muito oculta' => ['Apoio', Worksheet::SHEETSTATE_VERYHIDDEN],
        ];
    }

    public function test_modelo_original_importa_apenas_os_participantes(): void
    {
        $response = $this->post(route('atividades.presencas.cadastro', $this->atividade), [
            'your_file' => new UploadedFile(
                public_path('modelos/modelo_presencas_engaja.xlsx'),
                'presencas.xlsx',
                null,
                null,
                true
            ),
        ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
        $key = $query['session_key'];
        $rows = session($key)['rows'];
        $this->assertCount(3, $rows);
        $this->assertSame([2, 3, 4], array_column($rows, 'linha_original'));
        $this->assertSame(['Participantes', 'Participantes', 'Participantes'], array_column($rows, 'aba_original'));
        $this->confirm($key)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(3, Presenca::count());
    }

    public function test_modelo_com_dois_registros_invalidos_informa_somente_as_linhas_reais(): void
    {
        $counts = [User::count(), Participante::count()];
        $workbook = IOFactory::load(public_path('modelos/modelo_presencas_engaja.xlsx'));
        $sheet = $workbook->getSheetByName('Participantes');
        $sheet->fromArray(['', '', ''], null, 'A2', true);
        $sheet->fromArray(['', '', ''], null, 'A3', true);
        $sheet->fromArray(array_fill(0, 8, ''), null, 'A4', true);

        $response = $this->uploadWorkbook($workbook);
        $response->assertSessionHasErrors([
            'rows.0.nome', 'rows.0.identificacao', 'rows.1.nome', 'rows.1.identificacao',
        ]);
        $messages = session('errors')->all();
        $this->assertCount(4, $messages);
        foreach ($messages as $message) {
            $this->assertMatchesRegularExpression('/^Linha [23]:/', $message);
        }
        $this->assertSame($counts, [User::count(), Participante::count()]);
        $this->assertSame(0, Inscricao::count());
        $this->assertSame(0, Presenca::count());
    }

    #[DataProvider('missingStatuses')]
    public function test_status_ausente_ou_desconhecido_e_aceito_no_upload_e_destacado_na_previa(string $status): void
    {
        $response = $this->upload(['Participantes' => [
            ['Pessoa sem status', 'sem.status@example.com', '', $status],
            ['Pessoa com status', 'com.status@example.com', '', 'presente'],
        ]]);
        $response->assertRedirect()->assertSessionHasNoErrors();
        $preview = $this->get($response->headers->get('Location'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($preview->getContent());
        $xpath = new \DOMXPath($dom);
        $invalid = $xpath->query('//select[@name="rows[0][status]"]')->item(0);
        $valid = $xpath->query('//select[@name="rows[1][status]"]')->item(0);

        $this->assertStringContainsString('is-invalid', $invalid->getAttribute('class'));
        $this->assertSame('true', $invalid->getAttribute('aria-invalid'));
        $this->assertTrue($invalid->hasAttribute('required'));
        $this->assertStringNotContainsString('is-invalid', $valid->getAttribute('class'));
        $this->assertSame(
            'Selecione o status da presença.',
            trim($xpath->query('//*[@id="status-error-0"]')->item(0)->textContent)
        );
    }

    public static function missingStatuses(): array
    {
        return [[''], ['desconhecido']];
    }

    public function test_confirmacao_exige_status_valido_em_todas_as_linhas_antes_de_gravar(): void
    {
        $counts = [User::count(), Participante::count()];
        $withoutStatus = $this->row(['email' => 'sem.status@example.com']);
        unset($withoutStatus['status']);
        $key = $this->preview([
            $this->row(['email' => 'valido@example.com']),
            $withoutStatus,
            $this->row(['email' => 'invalido@example.com', 'status' => 'invalido', 'linha_original' => 8]),
        ]);
        $url = route('atividades.presencas.preview', ['atividade' => $this->atividade, 'session_key' => $key]);

        $this->from($url)->confirm($key)->assertRedirect($url)
            ->assertSessionHasErrors(['rows.1.status', 'rows.2.status']);
        $this->assertSame($counts, [User::count(), Participante::count()]);
        $this->assertSame(0, Inscricao::count());
        $this->assertSame(0, Presenca::count());
        $this->assertNotNull(session($key));
        $this->get($url)->assertOk()->assertSee('status-error-1')->assertSee('status-error-2');
    }

    public function test_status_pode_ser_corrigido_por_pagina_antes_de_confirmar(): void
    {
        $key = $this->preview([
            $this->row(['email' => 'primeira@example.com', 'status' => null]),
            $this->row(['email' => 'segunda@example.com', 'status' => null]),
            $this->row(['email' => 'terceira@example.com', 'status' => null]),
        ]);
        foreach (['presente', 'ausente', 'justificado'] as $index => $status) {
            $this->post(route('atividades.presencas.savepage', $this->atividade), [
                'session_key' => $key,
                'rows' => [$index => ['status' => $status]],
            ])->assertSessionHasNoErrors();
            $this->assertSame($status, session($key)['rows'][$index]['status']);
        }

        $this->confirm($key)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(['presente', 'ausente', 'justificado'], Presenca::orderBy('id')->pluck('status')->all());
    }

    public function test_previa_preserva_status_escolhido_quando_outro_campo_da_edicao_falha(): void
    {
        $key = $this->preview([$this->row(['status' => null])]);
        $url = route('atividades.presencas.preview', ['atividade' => $this->atividade, 'session_key' => $key]);
        $this->from($url)->post(route('atividades.presencas.savepage', $this->atividade), [
            'session_key' => $key,
            'rows' => [0 => ['nome' => '', 'status' => 'justificado']],
        ])->assertRedirect($url)->assertSessionHasErrors('rows.0.nome');
        $preview = $this->get($url)->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($preview->getContent());
        $xpath = new \DOMXPath($dom);
        $selected = $xpath->query('//select[@name="rows[0][status]"]/option[@selected]')->item(0);

        $this->assertSame('justificado', $selected->getAttribute('value'));
        $this->assertNull(session($key)['rows'][0]['status']);
    }

    public function test_criacao_atribui_role_de_participante_e_libera_meus_certificados(): void
    {
        $roleCount = DB::table('model_has_roles')->count();
        $key = $this->preview([
            $this->row(['nome' => 'Ana Silva', 'email' => 'ana@example.com']),
            $this->row(['nome' => 'Maria Ana Alves', 'email' => '', 'cpf' => '01234567890']),
        ]);
        $this->get(route('atividades.presencas.preview', ['atividade' => $this->atividade, 'session_key' => $key]))->assertOk();
        $this->assertSame($roleCount, DB::table('model_has_roles')->count());
        $this->confirm($key)->assertSessionHasNoErrors();

        $users = User::whereIn('email', ['ana@example.com', 'maria.ana.alves@ficticio.org.br'])->get();
        $this->assertCount(2, $users);
        foreach ($users as $user) {
            $this->assertSame(['participante'], $user->getRoleNames()->all());
            $this->assertDatabaseHas('model_has_roles', [
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->id,
                'role_id' => $user->roles->sole()->id,
            ]);
            $this->actingAs($user);
            $this->assertStringContainsString('Meus certificados', view('layouts.partials.admin-sidebar')->render());
        }
    }

    #[DataProvider('existingRoles')]
    public function test_reimportacao_preserva_roles_de_perfis_existentes(array $roles): void
    {
        $byEmail = $this->profile('email@example.com', '11111111111');
        $byCpf = $this->profile('cpf@example.com', '22222222222');
        if ($roles !== []) {
            $byEmail->assignRole($roles);
            $byCpf->assignRole($roles);
        }
        $roleCount = DB::table('model_has_roles')->count();

        $this->confirm($this->preview([
            $this->row(['email' => $byEmail->email]),
            $this->row(['email' => '', 'cpf' => '22222222222']),
        ]))->assertSessionHasNoErrors();

        $this->assertEqualsCanonicalizing($roles, $byEmail->fresh()->getRoleNames()->all());
        $this->assertEqualsCanonicalizing($roles, $byCpf->fresh()->getRoleNames()->all());
        $this->assertSame($roleCount, DB::table('model_has_roles')->count());
    }

    public static function existingRoles(): array
    {
        return [
            'sem role' => [[]],
            'participante' => [['participante']],
            'multiplas roles' => [['administrador', 'gerente']],
        ];
    }

    private function profile(string $email, string $cpf, array $extra = []): User
    {
        $user = User::factory()->create(array_merge(['email' => $email], $extra));
        $user->participante->update(['cpf' => $cpf]);

        return $user;
    }

    private function row(array $extra = []): array
    {
        return array_merge([
            'linha_original' => 2,
            'aba_original' => 'Presenças',
            'nome' => 'Maria Ana Alves',
            'email' => 'maria@example.com',
            'cpf' => null,
            'telefone' => null,
            'municipio' => '',
            'status' => 'presente',
            'justificativa' => null,
            'data_entrada' => null,
        ], $extra);
    }

    private function preview(array $rows): string
    {
        $key = "presenca_import_preview_atividade_{$this->atividade->id}_".Str::uuid();
        session([$key => [
            'atividade_id' => $this->atividade->id,
            'user_id' => $this->admin->id,
            'rows' => $rows,
        ]]);

        return $key;
    }

    private function confirm(string $key)
    {
        return $this->post(route('atividades.presencas.confirmar', $this->atividade), ['session_key' => $key]);
    }

    private function upload(array $sheets, string $format = 'xlsx', array $sheetStates = [])
    {
        $workbook = new Spreadsheet;
        foreach ($sheets as $name => $rows) {
            $sheet = $workbook->getSheetCount() === 1 && $workbook->getActiveSheet()->getHighestRow() === 1
                && $workbook->getActiveSheet()->getCell('A1')->getValue() === null
                ? $workbook->getActiveSheet() : $workbook->createSheet();
            $sheet->setTitle($name);
            $sheet->setSheetState($sheetStates[$name] ?? Worksheet::SHEETSTATE_VISIBLE);
            $sheet->fromArray(['nome', 'email', 'cpf', 'status'], null, 'A1');
            foreach ($rows as $index => $values) {
                $sheet->fromArray($values, null, 'A'.($index + 2));
            }
        }

        return $this->uploadWorkbook($workbook, $format);
    }

    private function uploadWorkbook(Spreadsheet $workbook, string $format = 'xlsx')
    {
        $path = tempnam(sys_get_temp_dir(), 'presencas_test_');
        try {
            IOFactory::createWriter($workbook, ['xlsx' => 'Xlsx', 'xls' => 'Xls', 'csv' => 'Csv'][$format])->save($path);

            return $this->post(route('atividades.presencas.cadastro', $this->atividade), [
                'your_file' => new UploadedFile($path, 'presencas.'.$format, null, null, true),
            ]);
        } finally {
            $workbook->disconnectWorksheets();
            unlink($path);
        }
    }
}
