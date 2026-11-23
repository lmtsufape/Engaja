<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Fonte única de verdade para a separação entre os dois sistemas hospedados no
 * mesmo domínio e no mesmo guard `web`: o Engaja (raiz) e o Cartas (prefixo
 * /cartas, users.sistema_origem = 'cartas').
 *
 * Todo redirecionamento que precise escolher entre os dois deve passar por aqui,
 * em vez de repetir checagens de prefixo/rota — foi essa duplicação que fez o
 * link de confirmação de e-mail do Cartas cair no login do Engaja.
 */
final class SistemaContext
{
    /**
     * A URL/rota pertence ao espaço do Cartas?
     *
     * Testa nome de rota e caminho: o nome cobre rotas resolvidas (inclusive as
     * que um dia deixem de morar sob /cartas) e o caminho cobre requisições
     * interrompidas antes do route matching.
     */
    public static function isCartasRequest(Request $request): bool
    {
        return $request->routeIs('cartas.*') || $request->is('cartas', 'cartas/*');
    }

    /**
     * Tela de login do sistema a que a requisição pertence.
     */
    public static function loginRoute(Request $request): string
    {
        return self::isCartasRequest($request)
            ? route('cartas.login')
            : route('login');
    }

    /**
     * Tela de login do sistema do usuário informado, quando o destino é decidido
     * a partir da identidade (ex.: link assinado aberto sem sessão).
     */
    public static function loginRouteFor(?User $user): string
    {
        return $user?->isCartasUser()
            ? route('cartas.login')
            : route('login');
    }

    /**
     * Home autenticada do sistema do usuário informado.
     */
    public static function homeRoute(?User $user): string
    {
        return $user?->isCartasUser()
            ? route('cartas.dashboard')
            : route('dashboard');
    }

    /**
     * Destino após a confirmação de e-mail de um usuário com sessão ativa.
     */
    public static function verifiedRoute(?User $user): string
    {
        return $user?->isCartasUser()
            ? route('cartas.dashboard', ['verified' => 1])
            : '/?verified=1';
    }

    /**
     * Destino após o logout (telas públicas de cada sistema).
     */
    public static function afterLogoutRoute(?User $user): string
    {
        return $user?->isCartasUser()
            ? route('cartas.login')
            : '/';
    }

    /**
     * Tela de aviso "verifique seu e-mail" do sistema do usuário informado.
     */
    public static function verificationNoticeRoute(?User $user): string
    {
        return $user?->isCartasUser()
            ? route('cartas.verification.notice')
            : route('verification.notice');
    }
}
