<?php

namespace Database\Seeders;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DimensaoSeeder::class,
            IndicadorSeeder::class,
            EvidenciaSeeder::class,
            EscalaSeeder::class,
            QuestaoSeeder::class,
            TemplateAvaliacaoSeeder::class,
            EixoSeeder::class,
            RegiaoSeeder::class,
            EstadoSeeder::class,
            MunicipioSeeder::class,
            RolesPermissionsSeeder::class,
            MatrizAprendizagemSeeder::class,
            SituacaoDesafiadoraSeeder::class,
            BiFenomenoSeeder::class,
            BiIndicadorSeeder::class,
            BiDimensaoSeeder::class,
            UpdateAnexosSeeder::class,
            CartasAdminSeeder::class,
            CartasTestSeeder::class,
        ]);

        $administrador = User::factory()
            ->has(Evento::factory()->count(4)->hasAtividades(3))
            ->create([
                'name' => 'Admin Engaja',
                'email' => 'admin@engaja.local',
            ]);

        $administrador->assignRole('administrador');

        $this->call([
            QuestionarioRespostaSeeder::class,
        ]);
    }
}
