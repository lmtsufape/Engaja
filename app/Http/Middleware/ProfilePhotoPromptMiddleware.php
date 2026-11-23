<?php

namespace App\Http\Middleware;

use App\Support\SistemaContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Oferece o modal de foto de perfil em qualquer rota GET autenticada, sem foto cadastrada.
 * O aviso repete no máximo a cada {@see HOURS_BETWEEN_PROMPTS} horas (sessão longa sem logout).
 */
class ProfilePhotoPromptMiddleware
{
    public const SESSION_KEY_OFFERED_AT = 'profile_photo_prompt_offered_at';

    public const HOURS_BETWEEN_PROMPTS = 24;

    public function handle(Request $request, Closure $next): Response
    {
        if (SistemaContext::isCartasRequest($request)) {
            return $next($request);
        }

        if (! auth()->check()) {
            return $next($request);
        }

        if (! empty(View::shared('exibirModalCompletarPerfil'))) {
            return $next($request);
        }

        $user = auth()->user();
        if ($user->profile_photo_path) {
            return $next($request);
        }

        if (! $this->shouldOfferPrompt($request)) {
            return $next($request);
        }

        if (! $request->isMethod('GET')) {
            return $next($request);
        }

        $request->session()->put(self::SESSION_KEY_OFFERED_AT, now());
        View::share('showProfilePhotoPromptModal', true);

        return $next($request);
    }

    private function shouldOfferPrompt(Request $request): bool
    {
        $last = $request->session()->get(self::SESSION_KEY_OFFERED_AT);

        if ($last === null) {
            return true;
        }

        $lastAt = $last instanceof \DateTimeInterface
            ? Carbon::instance($last)
            : Carbon::parse($last);

        return $lastAt->copy()->addHours(self::HOURS_BETWEEN_PROMPTS)->isPast();
    }
}
