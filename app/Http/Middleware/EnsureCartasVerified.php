<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCartasVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isCartasUser()) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('cartas.verification.notice');
        }

        return $next($request);
    }
}
