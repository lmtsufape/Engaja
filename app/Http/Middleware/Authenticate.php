<?php

namespace App\Http\Middleware;

use App\Support\SistemaContext;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        return SistemaContext::loginRoute($request);
    }
}
