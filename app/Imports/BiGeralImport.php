<?php

namespace App\Imports;

use App\Models\{
    BiIndicador,
    BiValor,
    BiDimensao,
    BiDimensaoValor,
    Municipio
};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class BiGeralImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $rows)
    {
        DB::transaction(function () use ($rows) {

            Log::info('Iniciando importação do BI Geral', [
                'total_rows' => $rows->count()
            ]);

            foreach ($rows as $index => $row) {

                try {

                    $this->validateRow($row, $index);

                    $indicador = BiIndicador::where('codigo', $row['indicador_codigo'])->firstOrFail();

                    $municipio = Municipio::whereRaw(
                        'unaccent(lower(nome)) = unaccent(lower(?))',
                        [$row['municipio_nome']]
                    )->firstOrFail();

                    $dimensaoValorId = null;

                    if (!empty($row['dimensao_codigo']) && !empty($row['dimensao_valor_codigo'])) {

                        $dimensaoValorId = BiDimensao::where('codigo', $row['dimensao_codigo'])
                            ->firstOrFail()
                            ->valores()
                            ->where('codigo', $row['dimensao_valor_codigo'])
                            ->firstOrFail()
                            ->id;
                    }

                    $valor = $row['valor'];

                    if ($valor === null) {
                        throw new \Exception("Valor inválido ou nulo para o indicador {$row['indicador_codigo']}.");
                    }

                    BiValor::updateOrCreate(
                        [
                            'indicador_id'       => $indicador->id,
                            'municipio_id'       => $municipio->id,
                            'ano'                => (int) $row['ano'],
                            'dimensao_valor_id'  => $dimensaoValorId,
                        ],
                        [
                            'valor' => $valor,
                        ]
                    );
                } catch (\Throwable $e) {

                    Log::error('Erro na importação BI Geral', [
                        'linha' => $index + 2, // considerando heading row
                        'erro'  => $e->getMessage(),
                        'row'   => $row->toArray(),
                    ]);

                    throw $e; // aborta transação inteira
                }
            }

            Log::info('Importação do BI Geral finalizada com sucesso');
        });
    }

    private function validateRow($row, int $index): void
    {
        $required = [
            'indicador_codigo',
            'municipio_nome',
            'ano',
            'valor',
        ];

        foreach ($required as $field) {
            if (! isset($row[$field]) || trim((string)$row[$field]) === '') {
                throw new \Exception("Campo obrigatório '{$field}' ausente na linha " . ($index + 2));
            }
        }
    }
}
