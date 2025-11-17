<?php

namespace Database\Seeders;

use App\Models\Escala;
use Illuminate\Database\Seeder;

class EscalaSeeder extends Seeder
{
    public function run(): void
    {
        $escalas = [
            [
                'descricao' => 'Nível de concordância',
                'opcao1'    => 'Discordo totalmente',
                'opcao2'    => 'Discordo parcialmente',
                'opcao3'    => 'Neutro',
                'opcao4'    => 'Concordo parcialmente',
                'opcao5'    => 'Concordo totalmente',
            ],
            [
                'descricao' => 'Avaliação de satisfação',
                'opcao1'    => 'Muito insatisfeito',
                'opcao2'    => 'Insatisfeito',
                'opcao3'    => 'Regular',
                'opcao4'    => 'Satisfeito',
                'opcao5'    => 'Muito satisfeito',
            ],
        ];

        foreach ($escalas as $escala) {
            Escala::updateOrCreate(
                ['descricao' => $escala['descricao']],
                $escala
            );
        }
    }
}
