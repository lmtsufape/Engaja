<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Eixo;

class EixoSeeder extends Seeder
{
    public function run(): void
    {
        $eixos = [
            'Educação Básica',
            'Educação de Jovens e Adultos',
            'Formação Continuada',
            'Gestão Escolar',
            'Tecnologia e Inovação',
        ];

        foreach ($eixos as $nome) {
            Eixo::firstOrCreate(['nome' => $nome]);
        }
    }
}
