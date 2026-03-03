<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BiIndicador;
use App\Models\BiFenomeno;

class BiIndicadorSeeder extends Seeder
{
    public function run(): void
    {
        $estrutura = [

            'ANALFABETISMO' => [
                [
                    'codigo'  => 'ANALFABETISMO_TAXA',
                    'tipo_valor' => BiIndicador::TIPO_PERCENTUAL,
                ],
                [
                    'codigo'  => 'ANALFABETISMO_QTDE',
                    'tipo_valor' => BiIndicador::TIPO_ABSOLUTO,
                ],
            ],

            'EJA' => [
                [
                    'codigo'  => 'EJA_MATRICULAS_QTDE',
                    'tipo_valor' => BiIndicador::TIPO_ABSOLUTO,
                ],
                [
                    'codigo'  => 'EJA_ACESSO_TAXA',
                    'tipo_valor' => BiIndicador::TIPO_PERCENTUAL,
                ],
            ],
        ];

        foreach ($estrutura as $fenomenoCodigo => $indicadores) {

            $fenomeno = BiFenomeno::where('codigo', $fenomenoCodigo)->firstOrFail();

            foreach ($indicadores as $indicador) {

                BiIndicador::updateOrCreate(
                    ['codigo' => $indicador['codigo']],
                    [
                        'fenomeno_id' => $fenomeno->id,
                        'tipo_valor'     => $indicador['tipo_valor'],
                    ]
                );
            }
        }
    }
}
