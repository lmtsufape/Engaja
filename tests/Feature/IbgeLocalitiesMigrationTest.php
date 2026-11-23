<?php

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Regiao;
use Database\Seeders\EstadoSeeder;
use Database\Seeders\MunicipioSeeder;
use Database\Seeders\RegiaoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IbgeLocalitiesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_ibge_cadastra_todos_os_estados_e_municipios_brasileiros(): void
    {
        config(['engaja.import_ibge_localities_in_tests' => true]);

        $migration = require database_path('migrations/2026_07_22_120100_import_all_ibge_localities.php');
        $migration->up();

        $this->assertSame(27, Estado::count());
        $this->assertSame(5571, Municipio::count());
        $this->assertDatabaseHas('municipios', ['nome' => 'Sobral']);
        $this->assertDatabaseHas('municipios', ['nome' => 'Boa Esperança do Norte']);
        $this->assertTrue(Regiao::where('nome', 'Outras')->exists());
        $this->assertSame(
            'Nordeste I',
            Estado::where('sigla', 'CE')->firstOrFail()->regiao->nome
        );

        $this->seed([RegiaoSeeder::class, EstadoSeeder::class, MunicipioSeeder::class]);

        $this->assertSame(27, Estado::count());
        $this->assertSame(5571, Municipio::count());
        $this->assertSame(
            'Nordeste I',
            Estado::where('sigla', 'CE')->firstOrFail()->regiao->nome
        );
    }
}
