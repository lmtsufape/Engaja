<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\Cache;

class AnalfabetismoService
{
    /**
     * Taxa de analfabetismo por município
     */
    public static function taxaPorMunicipio(): array
    {
        return Cache::remember(
            'bi:analfabetismo:taxa-por-municipio',
            now()->addMinutes(10),
            function () {

                // ⚠️ MOCK — depois vem do Excel / banco
                return [
                    'labels' => [
                        'Município A',
                        'Município B',
                        'Município C',
                        'Município D',
                    ],
                    'series' => [
                        14.2,
                        11.6,
                        9.4,
                        7.8,
                    ],
                ];
            }
        );
    }
}
