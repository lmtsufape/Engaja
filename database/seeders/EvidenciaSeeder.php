<?php

namespace Database\Seeders;

use App\Models\Evidencia;
use App\Models\Indicador;
use Illuminate\Database\Seeder;

class EvidenciaSeeder extends Seeder
{
    public function run(): void
    {
        $indicadores = Indicador::all();

        foreach ($indicadores as $indicador) {
            Evidencia::firstOrCreate(
                [
                    'indicador_id' => $indicador->id,
                    'descricao'    => 'Evidência padrão',
                ],
                []
            );
        }
    }
}

