<?php

namespace Database\Seeders;

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
            // Região Norte
            ['nome' => 'Oiapoque',                 'uf' => 'AP', 'interlocutor_email' => 'valcienegarcia@gmail.com'],
            ['nome' => 'Coari',                    'uf' => 'AM', 'interlocutor_email' => 'sarmentonajar@gmail.com'],
            ['nome' => 'Carauari',                 'uf' => 'AM', 'interlocutor_email' => 'ausilenebraga4006@gmail.com'],
            ['nome' => 'Belém',                    'uf' => 'PA', 'interlocutor_email' => 'manuella.porto@semec.belem.pa.gov.br'],

            // Região Nordeste I
            ['nome' => 'Caucaia',                  'uf' => 'CE', 'interlocutor_email' => 'janainaguedes1006@gmail.com'],
            ['nome' => 'Fortaleza',                'uf' => 'CE', 'interlocutor_email' => 'osvaldo.melo@educacao.fortaleza.ce.gov.br'],
            ['nome' => 'Icapuí',                   'uf' => 'CE', 'interlocutor_email' => 'thtbmaia@gmail.com'],
            ['nome' => 'Alto do Rodrigues',        'uf' => 'RN', 'interlocutor_email' => 'eleonez@bol.com.br'],
            ['nome' => 'Porto do Mangue',          'uf' => 'RN', 'interlocutor_email' => null],

            // Região Nordeste II
            ['nome' => 'Araçás',                   'uf' => 'BA', 'interlocutor_email' => 'supervisaotecanosfinais.eja@gmail.com'],
            ['nome' => 'São Francisco do Conde',   'uf' => 'BA', 'interlocutor_email' => 'marciamarino@gmail.com'],
            ['nome' => 'Conde',                    'uf' => 'PB', 'interlocutor_email' => 'andersoneduardolopes@gmail.com'],
            ['nome' => 'Ipojuca',                  'uf' => 'PE', 'interlocutor_email' => 'myziara.miranda@educacao.ipojuca.pe.gov.br'],
            ['nome' => 'Cabo de Santo Agostinho',  'uf' => 'PE', 'interlocutor_email' => 'coordenacaoejaicabo25@gmail.com'],
            ['nome' => 'Brejo Grande',             'uf' => 'SE', 'interlocutor_email' => 'torres.lucas77@yahoo.com.br'],
            ['nome' => 'Santa Luzia do Itanhy',    'uf' => 'SE', 'interlocutor_email' => 'mariaizabelpassos@outlook.com'],
        ];

        $estadoIds = DB::table('estados')->pluck('id', 'sigla');

        foreach ($municipios as $municipio) {
            DB::table('municipios')->updateOrInsert(
                [
                    'estado_id' => $estadoIds[$municipio['uf']],
                    'nome' => $municipio['nome'],
                ],
                [
                    'interlocutor_email' => $municipio['interlocutor_email'],
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]
            );
        }
    }
}
