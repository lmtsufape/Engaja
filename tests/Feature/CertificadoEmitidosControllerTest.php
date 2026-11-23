<?php

namespace Tests\Feature;

use App\Models\Certificado;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificadoEmitidosControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('administrador');

        $participanteUser = User::factory()->create(['name' => 'Participante Certificado']);

        Certificado::factory()->create([
            'participante_id' => $participanteUser->participante->id,
            'evento_nome' => 'Formação Alfa-EJA',
        ]);

        $this->actingAs($admin)
            ->get(route('certificados.emitidos'))
            ->assertOk()
            ->assertSee('grid-certificados-emitidos', false)
            ->assertSee('Participante Certificado')
            ->assertSee('Formação Alfa-EJA');
    }
}
