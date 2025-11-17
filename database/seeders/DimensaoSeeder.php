<?php

namespace Database\Seeders;

use App\Models\Dimensao;
use Illuminate\Database\Seeder;

class DimensaoSeeder extends Seeder
{
    public function run(): void
    {
        $dimensoes = [
            'Planejamento pedagógico',
            'Metodologias de ensino',
            'Participação e engajamento',
            'Gestão e logística',
        ];

        foreach ($dimensoes as $descricao) {
            Dimensao::firstOrCreate(['descricao' => $descricao]);
        }
    }
}
