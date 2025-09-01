<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
            //Região Norte
            ['nome' => 'Amapá',                 'sigla' => 'AP',  'regiao_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Amazonas',              'sigla' => 'AM',  'regiao_id' => 1, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Pará',                  'sigla' => 'PA',  'regiao_id' => 1, 'created_at' => $now, 'updated_at' => $now],

            //Região Nordeste I
            ['nome' => 'Ceará',                 'sigla' => 'CE',  'regiao_id' => 2, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Rio Grande do Norte',   'sigla' => 'RN',  'regiao_id' => 2, 'created_at' => $now, 'updated_at' => $now],
            
            //Região Nordeste II
            ['nome' => 'Bahia',                 'sigla' => 'BA',  'regiao_id' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Paraíba',               'sigla' => 'PB',  'regiao_id' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Pernambuco',            'sigla' => 'PE',  'regiao_id' => 3, 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Sergipe',               'sigla' => 'SE',  'regiao_id' => 3, 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('estados')->insert($estados);
    }
}
