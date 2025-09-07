<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Eixo;

class EixoSeeder extends Seeder
{
    public function run(): void
    {
        $eixos = [
            'Formação e Assessoria Pedagógica',
            'Leitura do Mundo e Escuta Territorial',
            'Protagonismo dos Educandos e Cultura na EJA',
            'Memória, Documentação e Referência',
            'Comunicação, Mobilização e Visibilidade',
            'Articulação com movimentos sociais',
        ];

        foreach ($eixos as $nome) {
            Eixo::firstOrCreate(['nome' => $nome]);
        }
    }
}
