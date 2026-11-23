<?php

namespace Tests\Feature;

use App\Models\TemplateAvaliacao;
use App\Models\User;
use Database\Seeders\RolesPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateAvaliacaoControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesPermissionsSeeder::class);
    }

    public function test_index_renderiza_grid_com_dados(): void
    {
        $template = TemplateAvaliacao::factory()->create(['nome' => 'Modelo padrão de momento']);

        $user = User::factory()->create();
        $user->assignRole('administrador');

        $this->actingAs($user)
            ->get(route('templates-avaliacao.index'))
            ->assertOk()
            ->assertSee('data-ag-grid', false)
            ->assertSee('Modelo padrão de momento');
    }
}
