<?php

namespace App\Services\BI;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class KpiService
{
    /**
     * KPIs principais do dashboard
     */
    public static function overview(int $days = 30): array
    {
        return Cache::remember(
            "bi:kpis:overview:$days",
            now()->addMinutes(5),
            function () use ($days) {

                // ⚠️ DADOS MOCKADOS (por enquanto)
                // depois isso virá do banco BI
                return [
                    'surveys' => 12,
                    'responses' => 348,
                    'last_response' => Carbon::now()->subHours(3),
                ];
            }
        );
    }
}
