<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantesExclusivosControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_selecao_preexistente(): void
    {
        $evento = Evento::factory()->create(['nome' => 'Formação Alfa-EJA']);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('usuarios.participantes-exclusivos.index', ['eventos' => [$evento->id]]))
            ->assertOk()
            ->assertSee('grid-participantes-exclusivos', false)
            ->assertSee('Formação Alfa-EJA')
            ->assertSee('data-selected-ids="['.$evento->id.']"', false);
    }
}
