<?php

namespace App\Services\BI;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class ResponsesService
{
    /**
     * Respostas agrupadas por dia (para gráfico)
     */
    public static function byDay(int $days = 30): array
    {
        return Cache::remember(
            "bi:responses:by-day:$days",
            now()->addMinutes(5),
            function () use ($days) {

                $labels = [];
                $series = [];

                for ($i = $days - 1; $i >= 0; $i--) {
                    $date = Carbon::now()->subDays($i);
                    $labels[] = $date->format('d/m');
                    $series[] = rand(0, 25); // mock
                }

                return [
                    'labels' => $labels,
                    'series' => $series,
                ];
            }
        );
    }
}
