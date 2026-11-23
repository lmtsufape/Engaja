<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use App\Models\Estado;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\Regiao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

abstract class CartasBaseTest extends TestCase
{
    use RefreshDatabase;

    protected User $gestor;

    protected User $voluntario;

    protected User $voluntario2;

    protected User $remetente;

    protected Participante $educando;

    protected Evento $eventoCartas;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        foreach ([
            'cartas.ver', 'cartas.criar', 'cartas.editar', 'cartas.excluir',
            'cartas.distribuir', 'cartas.responder', 'cartas.verificar',
            'cartas.editar-enviada', 'cartas.relatorio', 'cartas.exportar',
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'cartas_admin', 'guard_name' => 'web']);
        $cartasGestao = Role::firstOrCreate(['name' => 'cartas_gestao', 'guard_name' => 'web']);
        $cartasVoluntario = Role::firstOrCreate(['name' => 'cartas_voluntario', 'guard_name' => 'web']);

        $cartasGestao->syncPermissions([
            'cartas.ver', 'cartas.criar', 'cartas.editar',
            'cartas.verificar', 'cartas.relatorio', 'cartas.exportar',
        ]);

        $cartasVoluntario->syncPermissions([
            'cartas.ver', 'cartas.responder',
        ]);

        $this->gestor = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
        ]);
        $this->gestor->assignRole('cartas_gestao');

        $this->voluntario = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
            'cartas_limite_respostas' => 10,
        ]);
        $this->voluntario->assignRole('cartas_voluntario');

        $this->voluntario2 = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
            'cartas_limite_respostas' => 10,
        ]);
        $this->voluntario2->assignRole('cartas_voluntario');

        $regiao = Regiao::create(['nome' => 'Sudeste']);
        $estado = Estado::create(['nome' => 'São Paulo', 'sigla' => 'SP', 'regiao_id' => $regiao->id]);
        $municipio = Municipio::create([
            'nome' => 'São Paulo',
            'estado_id' => $estado->id,
            'regiao_id' => $regiao->id,
        ]);

        $this->remetente = User::factory()->create([
            'sistema_origem' => User::SISTEMA_ENGAJA,
        ]);

        $this->educando = $this->remetente->participante;
        $this->educando->update([
            'municipio_id' => $municipio->id,
            'cpf' => '12345678901',
            'telefone' => '11999999999',
        ]);

        $this->eventoCartas = Evento::factory()->create(['is_cartas' => true]);
    }

    /**
     * PDF real mínimo (não bytes aleatórios) — necessário porque o upload
     * agora é processado pelo FPDI (CartaTimbradoService::aplicarAnexo()),
     * que rejeita conteúdo que não seja um PDF válido.
     */
    protected function pdfFalsoValido(string $nome = 'carta.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $nome,
            file_get_contents(base_path('tests/Fixtures/cartas/exemplo-anexo.pdf'))
        );
    }

    protected function criarCartaParaVoluntario(): Carta
    {
        $carta = Carta::create([
            'codigo' => str_pad((string) (Carta::withTrashed()->count() + 1), 3, '0', STR_PAD_LEFT),
            'educando_participante_id' => $this->educando->id,
            'voluntario_user_id' => $this->voluntario->id,
            'municipio_id' => $this->educando->municipio_id,
            'status' => Carta::STATUS_AGUARDANDO_VOLUNTARIO,
            'distribuida_em' => now(),
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);

        CartaMensagem::create([
            'carta_id' => $carta->id,
            'rodada' => 1,
            'remetente_participante_id' => $this->educando->id,
            'destinatario_user_id' => $this->voluntario->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
            'canal_entrada' => CartaMensagem::CANAL_ANEXO_DIGITALIZADO,
            'status' => CartaMensagem::STATUS_APROVADA,
            'enviada_em' => now(),
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);

        return $carta;
    }

    protected function criarCartaComRespostaAguardandoVerificacao(): array
    {
        $carta = Carta::create([
            'codigo' => str_pad((string) (Carta::withTrashed()->count() + 1), 3, '0', STR_PAD_LEFT),
            'educando_participante_id' => $this->educando->id,
            'voluntario_user_id' => $this->voluntario->id,
            'municipio_id' => $this->educando->municipio_id,
            'status' => Carta::STATUS_AGUARDANDO_VERIFICACAO,
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);

        $mensagem = CartaMensagem::create([
            'carta_id' => $carta->id,
            'rodada' => 1,
            'remetente_user_id' => $this->voluntario->id,
            'destinatario_participante_id' => $this->educando->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO,
            'canal_entrada' => CartaMensagem::CANAL_DIGITADA,
            'status' => CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO,
            'texto' => 'Mensagem do voluntário.',
            'enviada_em' => now(),
            'criada_por' => $this->voluntario->id,
            'atualizada_por' => $this->voluntario->id,
        ]);

        return [$carta, $mensagem];
    }

    protected function criarCartaComRespostaComAjusteSolicitado(): array
    {
        $carta = Carta::create([
            'codigo' => str_pad((string) (Carta::withTrashed()->count() + 1), 3, '0', STR_PAD_LEFT),
            'educando_participante_id' => $this->educando->id,
            'voluntario_user_id' => $this->voluntario->id,
            'municipio_id' => $this->educando->municipio_id,
            'status' => Carta::STATUS_AGUARDANDO_AJUSTE,
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);

        $mensagem = CartaMensagem::create([
            'carta_id' => $carta->id,
            'rodada' => 1,
            'remetente_user_id' => $this->voluntario->id,
            'destinatario_participante_id' => $this->educando->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO,
            'canal_entrada' => CartaMensagem::CANAL_DIGITADA,
            'status' => CartaMensagem::STATUS_AJUSTE_SOLICITADO,
            'texto' => 'Mensagem antiga.',
            'parecer_verificacao' => 'Ajuste necessário',
            'enviada_em' => now(),
            'criada_por' => $this->voluntario->id,
            'atualizada_por' => $this->voluntario->id,
        ]);

        return [$carta, $mensagem];
    }
}
