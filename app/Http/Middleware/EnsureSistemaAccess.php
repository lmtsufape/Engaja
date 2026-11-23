<?php

namespace App\Http\Middleware;

use App\Support\SistemaContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Impede que um usuário de um sistema navegue no espaço de URL do outro
 * (Engaja na raiz, Cartas sob /cartas), exceto nas poucas telas de autenticação
 * que os dois compartilham.
 */
class EnsureSistemaAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $isCartasRoute = SistemaContext::isCartasRequest($request);

        if ($isCartasRoute && ! $user->isCartasUser() && ! $this->isSharedAuthRoute($request)) {
            abort(403);
        }

        if (! $isCartasRoute && $user->isCartasUser() && ! $this->isSharedAuthRoute($request)) {
            abort(403);
        }

        return $next($request);
    }

    /**
     * Rotas que atendem os dois sistemas e por isso não podem ser barradas.
     *
     * As de verificação são abertas a visitantes e identificam o usuário pela
     * URL assinada — o controller decide o destino pelo sistema_origem do dono
     * do link, então uma sessão do outro sistema não pode gerar 403 aqui.
     */
    private function isSharedAuthRoute(Request $request): bool
    {
        return $request->routeIs(
            'logout',
            'password.force.*',
            'verification.verify',
            'cartas.verification.verify',
        );
    }
}
