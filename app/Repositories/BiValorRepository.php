<?php

namespace App\Repositories;

use App\Models\BiIndicador;
use App\Models\BiValor;
use Illuminate\Support\Facades\Cache;

class BiValorRepository
{
    public function rankingMunicipios(string $codigoIndicador, int $ano, ?int $dimensaoValorId = null): array
    {
        return $this->setCache('rankingMunicipios', [
            'codigoIndicador' => $codigoIndicador,
            'ano' => $ano,
            'dimensaoValorId' => $dimensaoValorId,
        ], function () use ($codigoIndicador, $ano, $dimensaoValorId) {

            $indicador = BiIndicador::where('codigo', $codigoIndicador)->firstOrFail();

            $query = BiValor::query()
                ->with('municipio')
                ->where('indicador_id', $indicador->id)
                ->where('ano', $ano);

            if ($dimensaoValorId) {
                $query->where('dimensao_valor_id', $dimensaoValorId);
            }

            $valores = $query->get();

            // Monta array de dados para o gráfico
            $dados = [];
            foreach ($valores as $item) {
                if ($item->municipio && $item->valor !== null) {
                    $dados[] = [
                        'municipio' => $item->municipio->nome,
                        'valor' => $item->valor,
                    ];
                }
            }
            // Ordena por valor decrescente
            usort($dados, fn($a, $b) => $b['valor'] <=> $a['valor']);

            return [
                'tipo_valor' => $indicador->tipo_valor,
                'dados' => $dados,
            ];
        });
    }

    protected function setCache(string $method, array $params, \Closure $callback)
    {
        $paramString = collect($params)
            ->map(fn($value, $key) => "{$key}:{$value}")
            ->implode('|');

        $key = "bi_valor:{$method}:{$paramString}";

        return Cache::rememberForever($key, $callback);
    }

    public function clearCache(): void
    {
        Cache::tags(['bi_valor'])->flush();
    }
}
