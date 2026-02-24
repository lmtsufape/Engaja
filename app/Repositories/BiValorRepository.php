<?php

namespace App\Repositories;

use App\Models\BiDimensao;
use App\Models\BiIndicador;
use App\Models\BiValor;
use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BiValorRepository
{
    private const CACHE_TAG = 'bi_valor';
    private const CACHE_VERSION_KEY = 'bi_valor:version';

    public function rankingMunicipios(string $codigoIndicador, int $ano, ?int $dimensaoValorId = null): array
    {
        return $this->setCache('rankingMunicipios', [
            'codigoIndicador' => $codigoIndicador,
            'ano' => $ano,
            'dimensaoValorId' => $dimensaoValorId,
        ], function () use ($codigoIndicador, $ano, $dimensaoValorId) {
            $indicador = BiIndicador::where('codigo', $codigoIndicador)->firstOrFail();

            $query = BiValor::query()
                ->join('municipios', 'municipios.id', '=', 'bi_valores.municipio_id')
                ->where('bi_valores.indicador_id', $indicador->id)
                ->where('bi_valores.ano', $ano)
                ->whereNotNull('bi_valores.valor');

            if ($dimensaoValorId !== null) {
                $query->where('bi_valores.dimensao_valor_id', $dimensaoValorId);
            }

            $dados = $query
                ->orderByDesc('bi_valores.valor')
                ->get([
                    'municipios.nome as municipio',
                    'bi_valores.valor as valor',
                ])
                ->map(fn ($item) => [
                    'municipio' => $item->municipio,
                    'valor' => (float) $item->valor,
                ])
                ->all();

            return [
                'tipo_valor' => $indicador->tipo_valor,
                'dados' => $dados,
            ];
        });
    }

    public function rankingMunicipiosComIndicadores(
        string $codigoIndicadorPercentual,
        string $codigoIndicadorAbsoluto,
        int $ano,
        ?int $dimensaoValorId = null
    ): array
    {
        return $this->setCache('rankingMunicipiosComIndicadores', [
            'codigoIndicadorPercentual' => $codigoIndicadorPercentual,
            'codigoIndicadorAbsoluto' => $codigoIndicadorAbsoluto,
            'ano' => $ano,
            'dimensaoValorId' => $dimensaoValorId,
        ], function () use ($codigoIndicadorPercentual, $codigoIndicadorAbsoluto, $ano, $dimensaoValorId) {
            $indicadorPercentual = BiIndicador::where('codigo', $codigoIndicadorPercentual)->firstOrFail();
            $indicadorAbsoluto = BiIndicador::where('codigo', $codigoIndicadorAbsoluto)->firstOrFail();

            $query = BiValor::query()
                ->join('municipios', 'municipios.id', '=', 'bi_valores.municipio_id')
                ->whereIn('bi_valores.indicador_id', [$indicadorPercentual->id, $indicadorAbsoluto->id])
                ->where('bi_valores.ano', $ano)
                ->whereNotNull('bi_valores.valor');

            if ($dimensaoValorId !== null) {
                $query->where('bi_valores.dimensao_valor_id', $dimensaoValorId);
            }

            $valores = $query->get([
                'bi_valores.municipio_id',
                'municipios.nome as municipio',
                'bi_valores.indicador_id',
                'bi_valores.valor',
            ]);

            $valoresPorMunicipio = $valores->groupBy('municipio_id');

            $percentuaisPorMunicipio = $valoresPorMunicipio
                ->mapWithKeys(function ($itensMunicipio, $municipioId) use ($indicadorPercentual) {
                    $itens = $itensMunicipio->where('indicador_id', $indicadorPercentual->id);
                    if ($itens->isEmpty()) {
                        return [];
                    }

                    return [$municipioId => (float) $itens->avg('valor')];
                });

            $absolutosPorMunicipio = $valoresPorMunicipio
                ->mapWithKeys(function ($itensMunicipio, $municipioId) use ($indicadorAbsoluto) {
                    $itens = $itensMunicipio->where('indicador_id', $indicadorAbsoluto->id);
                    if ($itens->isEmpty()) {
                        return [];
                    }

                    return [$municipioId => (float) $itens->sum('valor')];
                });

            $dados = $valoresPorMunicipio
                ->map(function ($itensMunicipio, $municipioId) use ($percentuaisPorMunicipio, $absolutosPorMunicipio) {
                    return [
                        'municipio' => (string) ($itensMunicipio->first()->municipio ?? ''),
                        'percentual' => $percentuaisPorMunicipio->get($municipioId),
                        'absoluto' => $absolutosPorMunicipio->get($municipioId),
                    ];
                })
                ->filter(fn (array $item) => $item['municipio'] !== '' && $item['percentual'] !== null)
                ->sortByDesc('percentual')
                ->values();

            $mediaPercentual = $percentuaisPorMunicipio->isNotEmpty()
                ? round((float) $percentuaisPorMunicipio->avg(), 2)
                : 0.0;

            $totalAbsoluto = (float) $absolutosPorMunicipio->sum();

            $municipiosComDado = $percentuaisPorMunicipio
                ->keys()
                ->merge($absolutosPorMunicipio->keys())
                ->unique()
                ->count();

            return [
                'dados' => $dados->all(),
                'indicador_percentual' => [
                    'codigo' => $indicadorPercentual->codigo,
                    'label' => $this->formatarIndicadorCodigo($indicadorPercentual->codigo),
                    'tipo_valor' => $indicadorPercentual->tipo_valor,
                ],
                'indicador_absoluto' => [
                    'codigo' => $indicadorAbsoluto->codigo,
                    'label' => $this->formatarIndicadorCodigo($indicadorAbsoluto->codigo),
                    'tipo_valor' => $indicadorAbsoluto->tipo_valor,
                ],
                'resumo' => [
                    'total_absoluto' => $totalAbsoluto,
                    'media_percentual' => $mediaPercentual,
                    'municipios' => $municipiosComDado,
                ],
            ];
        });
    }

    public function distribuicaoPorDimensao(
        string $codigoIndicador,
        int $ano,
        string $codigoDimensao,
        ?int $municipioId = null
    ): array
    {
        return $this->setCache('distribuicaoPorDimensao', [
            'codigoIndicador' => $codigoIndicador,
            'ano' => $ano,
            'codigoDimensao' => $codigoDimensao,
            'municipioId' => $municipioId,
        ], function () use ($codigoIndicador, $ano, $codigoDimensao, $municipioId) {
            $indicador = BiIndicador::where('codigo', $codigoIndicador)->firstOrFail();
            $dimensao = BiDimensao::where('codigo', $codigoDimensao)->firstOrFail();

            $dados = BiValor::query()
                ->join('bi_dimensao_valores', 'bi_dimensao_valores.id', '=', 'bi_valores.dimensao_valor_id')
                ->where('bi_valores.indicador_id', $indicador->id)
                ->where('bi_valores.ano', $ano)
                ->where('bi_dimensao_valores.dimensao_id', $dimensao->id)
                ->whereNotNull('bi_valores.valor')
                ->when($municipioId !== null, fn ($query) => $query->where('bi_valores.municipio_id', $municipioId))
                ->groupBy('bi_dimensao_valores.codigo')
                ->orderByDesc(DB::raw('SUM(bi_valores.valor)'))
                ->get([
                    'bi_dimensao_valores.codigo as codigo',
                    DB::raw('SUM(bi_valores.valor) as total'),
                ])
                ->map(fn ($item) => [
                    'label' => $this->formatarDimensaoValor($dimensao->codigo, (string) $item->codigo),
                    'codigo' => (string) $item->codigo,
                    'valor' => (float) $item->total,
                ])
                ->values();

            $somaTotal = (float) $dados->sum('valor');

            $dados = $dados->map(function (array $item) use ($somaTotal) {
                $item['percentual'] = $somaTotal > 0
                    ? round(($item['valor'] / $somaTotal) * 100, 2)
                    : 0.0;

                return $item;
            })->all();

            return [
                'tipo_valor' => $indicador->tipo_valor,
                'dimensao' => $dimensao->codigo,
                'dados' => $dados,
            ];
        });
    }

    protected function setCache(string $method, array $params, Closure $callback)
    {
        $paramString = collect($params)
            ->map(fn ($value, $key) => "{$key}:" . ($value ?? 'null'))
            ->implode('|');

        $key = "bi_valor:{$method}:{$paramString}";

        if ($this->supportsCacheTags()) {
            return Cache::tags([self::CACHE_TAG])->rememberForever($key, $callback);
        }

        $version = (int) Cache::get(self::CACHE_VERSION_KEY, 1);
        $versionedKey = "bi_valor:v{$version}:{$method}:{$paramString}";

        return Cache::rememberForever($versionedKey, $callback);
    }

    public function clearCache(): void
    {
        if ($this->supportsCacheTags()) {
            Cache::tags([self::CACHE_TAG])->flush();
            return;
        }

        $currentVersion = (int) Cache::get(self::CACHE_VERSION_KEY, 1);
        Cache::forever(self::CACHE_VERSION_KEY, $currentVersion + 1);
    }

    protected function supportsCacheTags(): bool
    {
        return method_exists(Cache::getStore(), 'tags');
    }

    protected function formatarDimensaoValor(string $codigoDimensao, string $codigoValor): string
    {
        $mapa = [
            'SEXO' => [
                'MAS' => 'Masculino',
                'FEM' => 'Feminino',
            ],
            'RACA' => [
                'BRANCA' => 'Branca',
                'PRETA' => 'Preta',
                'PARDA' => 'Parda',
                'INDIGENA' => 'Indigena',
            ],
            'RESIDENCIA' => [
                'RURAL' => 'Rural',
                'URBANA' => 'Urbana',
                'FAVELA' => 'Favela',
            ],
        ];

        return $mapa[$codigoDimensao][$codigoValor] ?? $codigoValor;
    }

    protected function formatarIndicadorCodigo(string $codigoIndicador): string
    {
        $mapa = [
            'ANALFABETISMO_TAXA' => 'Taxa de analfabetismo',
            'ANALFABETISMO_QTDE' => 'Quantidade de analfabetos',
            'EJA_ACESSO_TAXA' => 'Taxa de acesso EJA',
            'EJA_MATRICULAS_QTDE' => 'Matriculas EJA',
        ];

        return $mapa[$codigoIndicador] ?? str_replace('_', ' ', $codigoIndicador);
    }
}
