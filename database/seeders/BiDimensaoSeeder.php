<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\BiDimensao;
use App\Models\BiDimensaoValor;

class BiDimensaoSeeder extends Seeder
{
    public function run()
    {
        $dimensoes = [
            'SEXO' => [
                'MAS',
                'FEM'
            ],
            'RACA' => [
                'BRANCA',
                'PRETA',
                'PARDA',
                'INDIGENA'
            ],
            'RESIDENCIA' => [
                'RURAL',
                'URBANA',
                'FAVELA'
            ],
        ];

        foreach ($dimensoes as $codigo => $data) {
            $dimensao = BiDimensao::firstOrCreate(['codigo' => $codigo]);

            foreach ($data as $dimValor) {
                BiDimensaoValor::firstOrCreate(
                    [
                        'codigo' => $dimValor,
                        'dimensao_id' => $dimensao->id
                    ],
                );
            }
        }
    }
}
