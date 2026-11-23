<?php

namespace Tests\Feature;

use App\Models\Atividade;
use App\Models\Estado;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Regiao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CadastroParticipanteMunicipiosTest extends TestCase
{
    use RefreshDatabase;

    public function test_cadastro_via_qr_permite_escolher_estado_e_qualquer_municipio_cadastrado(): void
    {
        $regiao = Regiao::create(['nome' => 'Outras']);
        $estado = Estado::create([
            'regiao_id' => $regiao->id,
            'nome' => 'Ceará',
            'sigla' => 'CE',
        ]);
        $municipio = Municipio::create([
            'estado_id' => $estado->id,
            'nome' => 'Sobral',
        ]);
        $evento = Evento::factory()->create();
        $atividade = Atividade::factory()->create(['evento_id' => $evento->id]);

        $this->get(route('evento.cadastro_inscricao', [
            'evento_id' => $evento->id,
            'atividade_id' => $atividade->id,
        ]))
            ->assertOk()
            ->assertSee('Estado')
            ->assertSee('Ceará')
            ->assertSee('Sobral')
            ->assertSee('cadastro-municipios-json', false)
            ->assertSee('value="'.$estado->id.'"', false)
            ->assertSee('"id":"'.$municipio->id.'"', false);
    }
}
