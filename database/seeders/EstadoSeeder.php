<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $estados = [
            // Região Norte
            ['nome' => 'Amapá',                 'sigla' => 'AP',  'regiao' => 'Norte'],
            ['nome' => 'Amazonas',              'sigla' => 'AM',  'regiao' => 'Norte'],
            ['nome' => 'Pará',                  'sigla' => 'PA',  'regiao' => 'Norte'],

            // Região Nordeste I
            ['nome' => 'Ceará',                 'sigla' => 'CE',  'regiao' => 'Nordeste I'],
            ['nome' => 'Rio Grande do Norte',   'sigla' => 'RN',  'regiao' => 'Nordeste I'],

            // Região Nordeste II
            ['nome' => 'Bahia',                 'sigla' => 'BA',  'regiao' => 'Nordeste II'],
            ['nome' => 'Paraíba',               'sigla' => 'PB',  'regiao' => 'Nordeste II'],
            ['nome' => 'Pernambuco',            'sigla' => 'PE',  'regiao' => 'Nordeste II'],
            ['nome' => 'Sergipe',               'sigla' => 'SE',  'regiao' => 'Nordeste II'],
        ];

        $regioes = DB::table('regiaos')->pluck('id', 'nome');

        foreach ($estados as $estado) {
            DB::table('estados')->updateOrInsert(
                ['sigla' => $estado['sigla']],
                [
                    'nome' => $estado['nome'],
                    'regiao_id' => $regioes[$estado['regiao']],
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );
        }
    }
}
