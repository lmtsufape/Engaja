<?php

namespace Tests\Feature;

use App\Models\Evento;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventoControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        Evento::factory()->create(['nome' => 'Formação Alfa-EJA']);

        $this->actingAs($user)
            ->get(route('eventos.index'))
            ->assertOk()
            ->assertSee('grid-eventos', false)
            ->assertSee('data-row-selection="multiple"', false)
            ->assertSee('Formação Alfa-EJA');
    }

    public function test_index_ordena_por_nome(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrador');

        Evento::factory()->create(['nome' => 'Zeta']);
        Evento::factory()->create(['nome' => 'Alfa']);

        $response = $this->actingAs($user)
            ->get(route('eventos.index', ['sort' => 'nome', 'dir' => 'asc']))
            ->assertOk();

        $posicaoAlfa = strpos($response->getContent(), 'Alfa');
        $posicaoZeta = strpos($response->getContent(), 'Zeta');

        $this->assertNotFalse($posicaoAlfa);
        $this->assertNotFalse($posicaoZeta);
        $this->assertLessThan($posicaoZeta, $posicaoAlfa);
    }
}
