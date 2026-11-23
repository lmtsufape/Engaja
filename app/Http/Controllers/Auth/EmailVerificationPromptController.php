<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\SistemaContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        if ($request->user()->isCartasUser()) {
            return $request->user()->hasVerifiedEmail()
                ? redirect()->to(SistemaContext::homeRoute($request->user()))
                : view('cartas.auth.verify-email');
        }

        return $request->user()->hasVerifiedEmail()
                    ? redirect()->intended('/')
                    : view('auth.verify-email');
    }
}
