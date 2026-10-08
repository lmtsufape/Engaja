<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class CartaDistribuicaoTest extends CartasBaseTest
{
    /**
     * Garante que o remetente (educando) nunca recebe sua própria carta,
     * mesmo que ele também seja voluntário no sistema Cartas.
     */
    public function test_remetente_nao_recebe_propria_carta(): void
    {
        // O remetente é um user Engaja. Vamos criar um cenário onde
        // ele TAMBÉM existe como voluntário no sistema Cartas (mesmo user_id).
        $this->remetente->update(['sistema_origem' => User::SISTEMA_CARTAS]);
        $this->remetente->assignRole('cartas_voluntario');

        // Enviar múltiplas cartas para garantir que nunca vai pro remetente
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($this->gestor)
                ->post(route('cartas.cartas.store'), [
                    'envio_token' => (string) Str::uuid(),
                    'remetente_user_id' => $this->remetente->id,
                    'arquivo' => UploadedFile::fake()->create("carta_{$i}.pdf", 100, 'application/pdf'),
                ]);
        }

        // Nenhuma carta deve ter sido atribuída ao remetente
        $cartasDoRemetente = Carta::where('voluntario_user_id', $this->remetente->id)->count();
        $this->assertEquals(0, $cartasDoRemetente, 'O remetente recebeu sua própria carta!');

        // Todas devem ter ido para os outros voluntários
        $totalCartas = Carta::count();
        $this->assertEquals(5, $totalCartas);
    }

    /**
     * Garante que a distribuição é baseada no total de cartas ENVIADAS
     * (atribuídas) e não apenas nas cartas abertas/pendentes.
     * Voluntários que já responderam todas as cartas NÃO devem
     * ser tratados como se tivessem contagem zero.
     */
    public function test_distribuicao_conta_todas_cartas_enviadas_nao_apenas_abertas(): void
    {
        // Dar 3 cartas ao voluntário 1, todas com status RESPONDIDA
        for ($i = 0; $i < 3; $i++) {
            Carta::create([
                'codigo' => str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'educando_participante_id' => $this->educando->id,
                'voluntario_user_id' => $this->voluntario->id,
                'municipio_id' => $this->educando->municipio_id,
                'status' => Carta::STATUS_RESPONDIDA,
                'criada_por' => $this->gestor->id,
                'atualizada_por' => $this->gestor->id,
            ]);
        }

        // voluntário 2 não tem nenhuma carta
        $file = UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf');

        $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $this->remetente->id,
                'arquivo' => $file,
            ]);

        $novaCarta = Carta::latest('id')->first();

        // A nova carta DEVE ir para o voluntário 2 (que tem 0 cartas totais)
        $this->assertEquals(
            $this->voluntario2->id,
            $novaCarta->voluntario_user_id,
            'A carta deveria ir para o voluntário com menos cartas totais atribuídas.'
        );
    }

    /**
     * Garante que a distribuição sequencial balanceia entre voluntários.
     * Ao enviar N cartas, cada voluntário deve receber no máximo 1 a mais
     * do que qualquer outro.
     */
    public function test_distribuicao_balanceada_entre_voluntarios(): void
    {
        $voluntario3 = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
            'cartas_terms_accepted_at' => now(),
            'cartas_limite_respostas' => 10,
        ]);
        $voluntario3->assignRole('cartas_voluntario');

        $voluntarioIds = [
            $this->voluntario->id,
            $this->voluntario2->id,
            $voluntario3->id,
        ];

        // Enviar 6 cartas (2 por voluntário, idealmente)
        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($this->gestor)
                ->post(route('cartas.cartas.store'), [
                    'envio_token' => (string) Str::uuid(),
                    'remetente_user_id' => $this->remetente->id,
                    'arquivo' => UploadedFile::fake()->create("carta_{$i}.pdf", 100, 'application/pdf'),
                ]);
        }

        $counts = [];
        foreach ($voluntarioIds as $id) {
            $counts[$id] = Carta::where('voluntario_user_id', $id)->count();
        }

        // Cada voluntário deve ter exatamente 2 cartas (6 / 3)
        foreach ($counts as $id => $count) {
            $this->assertEquals(2, $count, "Voluntário {$id} recebeu {$count} cartas, esperava 2.");
        }
    }

    /**
     * Garante que mesmo com cartas em vários status (respondida, encerrada,
     * aguardando), a contagem total é usada para distribuição.
     */
    public function test_distribuicao_considera_todos_os_status(): void
    {
        // Voluntário 1: 1 carta respondida + 1 encerrada = 2 total
        Carta::create([
            'codigo' => '001',
            'educando_participante_id' => $this->educando->id,
            'voluntario_user_id' => $this->voluntario->id,
            'municipio_id' => $this->educando->municipio_id,
            'status' => Carta::STATUS_RESPONDIDA,
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);
        Carta::create([
            'codigo' => '002',
            'educando_participante_id' => $this->educando->id,
            'voluntario_user_id' => $this->voluntario->id,
            'municipio_id' => $this->educando->municipio_id,
            'status' => Carta::STATUS_ENCERRADA,
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);

        // Voluntário 2: 1 carta aguardando = 1 total
        Carta::create([
            'codigo' => '003',
            'educando_participante_id' => $this->educando->id,
            'voluntario_user_id' => $this->voluntario2->id,
            'municipio_id' => $this->educando->municipio_id,
            'status' => Carta::STATUS_AGUARDANDO_VOLUNTARIO,
            'criada_por' => $this->gestor->id,
            'atualizada_por' => $this->gestor->id,
        ]);

        $file = UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf');

        $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $this->remetente->id,
                'arquivo' => $file,
            ]);

        $novaCarta = Carta::latest('id')->first();

        // Voluntário 2 tem 1 carta total, Voluntário 1 tem 2 total → deve ir para o Voluntário 2
        $this->assertEquals(
            $this->voluntario2->id,
            $novaCarta->voluntario_user_id,
            'A carta deveria ir para o voluntário 2 (menos cartas totais: 1 vs 2).'
        );
    }

    /**
     * Garante que a lista de remetentes candidatos só inclui
     * usuários do sistema Engaja com participante vinculado.
     */
    public function test_lista_de_remetentes_exclui_usuarios_cartas(): void
    {
        $response = $this->actingAs($this->gestor)
            ->get(route('cartas.dashboard'));

        $response->assertStatus(200);

        // Verifica que a view recebeu a variável engajaUsers
        $engajaUsers = $response->viewData('engajaUsers');

        // Voluntários do sistema Cartas NÃO devem aparecer como remetentes
        $this->assertFalse(
            $engajaUsers->contains('id', $this->voluntario->id),
            'Voluntário 1 (sistema Cartas) não deveria aparecer na lista de remetentes.'
        );
        $this->assertFalse(
            $engajaUsers->contains('id', $this->voluntario2->id),
            'Voluntário 2 (sistema Cartas) não deveria aparecer na lista de remetentes.'
        );

        // O remetente (sistema Engaja com participante) DEVE aparecer
        $this->assertTrue(
            $engajaUsers->contains('id', $this->remetente->id),
            'O remetente (sistema Engaja) deveria aparecer na lista de remetentes.'
        );
    }

    /**
     * Garante que se não houver voluntários disponíveis (excluindo o remetente),
     * o sistema retorna erro ao invés de atribuir ao remetente.
     */
    public function test_erro_quando_nao_ha_voluntarios_disponiveis(): void
    {
        // Remover todos os voluntários
        $this->voluntario->removeRole('cartas_voluntario');
        $this->voluntario2->removeRole('cartas_voluntario');

        $file = UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->gestor)
            ->post(route('cartas.cartas.store'), [
                'envio_token' => (string) Str::uuid(),
                'remetente_user_id' => $this->remetente->id,
                'arquivo' => $file,
            ]);

        $response->assertSessionHasErrors('destinatario');
        $this->assertDatabaseCount('cartas', 0);
    }
}
