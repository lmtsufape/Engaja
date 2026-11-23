<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartaRemetenteFilterTest extends TestCase
{
    use RefreshDatabase;

    private function participanteInscrito(bool $inscrito): Participante
    {
        $user = User::factory()->create(['sistema_origem' => User::SISTEMA_ENGAJA]);

        // User::booted() ja cria o participante; reaproveitar evita um segundo
        // registro que a relacao hasOne do usuario nunca enxergaria.
        $participante = $user->participante;

        $evento = Evento::factory()->create(['is_cartas' => $inscrito]);
        Inscricao::create([
            'evento_id' => $evento->id,
            'participante_id' => $participante->id,
        ]);

        return $participante;
    }

    private function gestor(): User
    {
        Role::findOrCreate('cartas_gestao', 'web');
        $gestor = User::factory()->create([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'email_verified_at' => now(),
        ]);
        $gestor->assignRole('cartas_gestao');

        return $gestor;
    }

    public function test_lista_de_remetentes_traz_apenas_usuarios_engaja_com_participante(): void
    {
        $inscrito = $this->participanteInscrito(true);
        $foraDaAcao = $this->participanteInscrito(false);
        $usuarioCartas = User::factory()->create(['sistema_origem' => User::SISTEMA_CARTAS]);

        $gestor = $this->gestor();

        $response = $this->actingAs($gestor)->get(route('cartas.dashboard'));

        $response->assertOk();

        $ids = collect($response->viewData('engajaUsers'))->pluck('id');

        // Usuários Engaja com participante DEVEM aparecer
        $this->assertTrue($ids->contains($inscrito->user_id));
        $this->assertTrue($ids->contains($foraDaAcao->user_id));

        // Usuário do sistema Cartas NÃO deve aparecer
        $this->assertFalse($ids->contains($usuarioCartas->id));
    }

    public function test_combobox_de_remetente_expoe_cpf_sem_exibi_lo_na_lista(): void
    {
        $remetente = $this->participanteInscrito(true);
        $remetente->update(['cpf' => '123.456.789-09']);
        $remetente->user->update(['name' => 'Maria da Silva']);

        $response = $this->actingAs($this->gestor())->get(route('cartas.dashboard'));

        $response->assertOk();
        $response->assertSee('data-cpf="12345678909"', false);

        // O rótulo visível continua sendo apenas o nome (com localidade).
        $response->assertDontSee('data-label="Maria da Silva - 123', false);
        $response->assertDontSee('>Maria da Silva - 123.456.789-09<', false);
    }

    public function test_busca_por_cpf_do_remetente_retorna_a_carta(): void
    {
        $remetente = $this->participanteInscrito(true);
        $remetente->update(['cpf' => '123.456.789-09']);

        $carta = Carta::factory()->create(['educando_participante_id' => $remetente->id]);
        $outra = Carta::factory()->create();

        $response = $this->actingAs($this->gestor())
            ->get(route('cartas.dashboard', ['q' => '12345678909']));

        $response->assertOk();

        $ids = collect($response->viewData('cartas')->items())->pluck('id');
        $this->assertTrue($ids->contains($carta->id));
        $this->assertFalse($ids->contains($outra->id));
    }

    public function test_busca_por_cpf_com_pontuacao_retorna_a_carta(): void
    {
        $remetente = $this->participanteInscrito(true);
        $remetente->update(['cpf' => '12345678909']);

        $carta = Carta::factory()->create(['educando_participante_id' => $remetente->id]);

        $response = $this->actingAs($this->gestor())
            ->get(route('cartas.dashboard', ['q' => '123.456.789-09']));

        $response->assertOk();

        $ids = collect($response->viewData('cartas')->items())->pluck('id');
        $this->assertTrue($ids->contains($carta->id));
    }

    public function test_busca_por_cpf_do_destinatario_retorna_a_carta(): void
    {
        $carta = Carta::factory()->create();
        $carta->voluntario->participante->update(['cpf' => '987.654.321-00']);

        $outra = Carta::factory()->create();

        $response = $this->actingAs($this->gestor())
            ->get(route('cartas.dashboard', ['q' => '98765432100']));

        $response->assertOk();

        $ids = collect($response->viewData('cartas')->items())->pluck('id');
        $this->assertTrue($ids->contains($carta->id));
        $this->assertFalse($ids->contains($outra->id));
    }

    public function test_busca_por_cpf_inexistente_nao_retorna_cartas(): void
    {
        $remetente = $this->participanteInscrito(true);
        $remetente->update(['cpf' => '123.456.789-09']);

        Carta::factory()->create(['educando_participante_id' => $remetente->id]);

        $response = $this->actingAs($this->gestor())
            ->get(route('cartas.dashboard', ['q' => '00000000000']));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('cartas')->items());
    }

    public function test_busca_por_nome_continua_funcionando(): void
    {
        $remetente = $this->participanteInscrito(true);
        $remetente->update(['cpf' => '123.456.789-09']);
        $remetente->user->update(['name' => 'Joana Esperanca']);

        $carta = Carta::factory()->create(['educando_participante_id' => $remetente->id]);
        $outra = Carta::factory()->create();

        $response = $this->actingAs($this->gestor())
            ->get(route('cartas.dashboard', ['q' => 'Joana']));

        $response->assertOk();

        $ids = collect($response->viewData('cartas')->items())->pluck('id');
        $this->assertTrue($ids->contains($carta->id));
        $this->assertFalse($ids->contains($outra->id));
    }
}
