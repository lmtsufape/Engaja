<?php

namespace App\Http\Middleware;

use App\Support\SistemaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        /*
         * A troca de senha obrigatória é um fluxo do Engaja (password.force.*).
         * Sem este early-return, um usuário do Cartas com a flag ligada seria
         * levado para uma tela do outro sistema.
         */
        if (SistemaContext::isCartasRequest($request)) {
            return $next($request);
        }

        $user = $request->user();

        if (
            $user?->force_password_change
            && ! $request->routeIs('password.force.*')
            && ! $request->routeIs('logout')
        ) {
            return redirect()->route('password.force.edit');
        }

        return $next($request);
    }
}
