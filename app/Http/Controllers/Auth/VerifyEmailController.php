<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\SistemaContext;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Confirma o e-mail a partir da URL assinada, atendendo tanto a rota do Cartas
 * (cartas.verification.verify) quanto a legada do Engaja (verification.verify).
 *
 * Não usa EmailVerificationRequest porque a rota é aberta a visitantes: o link
 * chega por e-mail e costuma ser aberto sem sessão. A identidade é provada pela
 * assinatura da URL (middleware 'signed', com expiração) somada ao hash do
 * e-mail — os mesmos fatores que o Laravel valida no fluxo autenticado.
 */
class VerifyEmailController extends Controller
{
    public function __invoke(string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        abort_unless(
            hash_equals(sha1($user->getEmailForVerification()), $hash),
            403
        );

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if (Auth::id() === $user->getKey()) {
            return redirect()->intended(SistemaContext::verifiedRoute($user));
        }

        return redirect()
            ->to(SistemaContext::loginRouteFor($user))
            ->with('status', 'E-mail confirmado! Faça login para continuar.');
    }
}
