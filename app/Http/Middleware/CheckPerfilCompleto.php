<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class CheckPerfilCompleto
{
    private array $camposObrigatorios = [
        'identidade_genero',
        'raca_cor',
        'comunidade_tradicional',
        'faixa_etaria',
        'pcd',
        'orientacao_sexual',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            $incompleto = collect($this->camposObrigatorios)
                ->some(fn($campo) => empty($user->$campo));

            if ($incompleto) {
                View::share('exibirModalCompletarPerfil', true);
            }
        }

        return $next($request);
    }
}