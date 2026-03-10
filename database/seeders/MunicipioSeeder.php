<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MunicipioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $municipios = [
            //RegiÃ£o Norte
            ['nome' => 'Oiapoque',                 'estado_id' => 1, 'interlocutor_email' => 'valcienegarcia@gmail.com', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Coari',                    'estado_id' => 2, 'interlocutor_email' => 'sarmentonajar@gmail.com', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Carauari',                 'estado_id' => 2, 'interlocutor_email' => 'ausilenebraga4006@gmail.com', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Belém',                    'estado_id' => 3, 'interlocutor_email' => 'manuella.porto@semec.belem.pa.gov.br', 'created_at' => $now, 'updated_at' => $now],

            //RegiÃ£o Nordeste I
            ['nome' => 'Caucaia',                  'estado_id' => 4, 'interlocutor_email' => 'janainaguedes1006@gmail.com', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Fortaleza',                'estado_id' => 4, 'interlocutor_email' => 'osvaldo.melo@educacao.fortaleza.ce.gov.br', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Icapuí­',                   'estado_id' => 4, 'interlocutor_email' => 'thtbmaia@gmail.com', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Alto do Rodrigues',        'estado_id' => 5, 'interlocutor_email' => 'eleonez@bol.com.br', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Porto do Mangue',          'estado_id' => 5, 'interlocutor_email' => null, 'created_at' => $now, 'updated_at' => $now],

            //RegiÃ£o Nordeste II
            ['nome' => 'Araçás',                   'estado_id' => 6, 'interlocutor_email' => 'supervisaotecanosfinais.eja@gmail.com', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'São Francisco do Conde',   'estado_id' => 6, 'interlocutor_email' => 'marciamarino@gmail.com', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Conde',                    'estado_id' => 7, 'interlocutor_email' => 'andersoneduardolopes@gmail.com', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Ipojuca',                  'estado_id' => 8, 'interlocutor_email' => 'myziara.miranda@educacao.ipojuca.pe.gov.br', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Cabo de Santo Agostinho',  'estado_id' => 8, 'interlocutor_email' => 'coordenacaoejaicabo25@gmail.com', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Brejo Grande',             'estado_id' => 9, 'interlocutor_email' => 'torres.lucas77@yahoo.com.br', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Santa Luzia do Itanhy',    'estado_id' => 9, 'interlocutor_email' => 'mariaizabelpassos@outlook.com', 'created_at' => $now, 'updated_at' => $now],
        ];

        DB::table('municipios')->insert($municipios);
    }
}
