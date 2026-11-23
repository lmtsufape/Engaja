<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::where('email', $request->email)
            ->where('sistema_origem', User::SISTEMA_ENGAJA)
            ->first();

        if (! $user || ! Password::broker()->tokenExists($user, $request->token)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => __(Password::INVALID_TOKEN)]);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'force_password_change' => false,
            'remember_token' => Str::random(60),
        ])->save();

        Password::broker()->deleteToken($user);

        event(new PasswordReset($user));

        return redirect()->route('login')->with('status', __(Password::PASSWORD_RESET));
    }
}
