<?php

use App\Exceptions\TemplateEmUsoException;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckPerfilCompleto;
use App\Http\Middleware\EnsureCartasVerified;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureSistemaAccess;
use App\Http\Middleware\ProfilePhotoPromptMiddleware;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => Authenticate::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'cartas.verified' => EnsureCartasVerified::class,
        ]);

        $middleware->appendToGroup('web', [
            EnsureSistemaAccess::class,
            CheckPerfilCompleto::class,
            ProfilePhotoPromptMiddleware::class,
            EnsurePasswordChanged::class,
        ]);

        $middleware->prepend([TrustProxies::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (TemplateEmUsoException $exception) {
            return redirect()
                ->route('templates-avaliacao.index')
                ->with('error', $exception->getMessage());
        });
    })->create();
