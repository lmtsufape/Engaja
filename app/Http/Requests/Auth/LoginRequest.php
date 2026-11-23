<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login = (string) $this->input('login');
        $password = (string) $this->input('password');

        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL) !== false;
        $cpfDigits = preg_replace('/\D+/', '', $login);

        $user = null;
        $errorKey = 'login';

        if ($isEmail) {
            $user = \App\Models\User::where('email', $login)
                ->where('sistema_origem', \App\Models\User::SISTEMA_ENGAJA)
                ->first();
        } elseif (strlen($cpfDigits) === 11) {
            $usuarios = \App\Models\User::where('sistema_origem', \App\Models\User::SISTEMA_ENGAJA)
                ->whereHas('participante', function ($q) use ($cpfDigits) {
                    $q->whereRaw("regexp_replace(cpf, '[^0-9]', '', 'g') = ?", [$cpfDigits]);
                })->get();

            if ($usuarios->count() > 1) {
                RateLimiter::hit($this->throttleKey());
                throw ValidationException::withMessages([
                    'login' => 'CPF duplicado no cadastro. Contate o suporte para regularizar.',
                ]);
            }

            $user = $usuarios->first();
        } else {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'login' => 'Informe um e-mail válido ou um CPF com 11 dígitos.',
            ]);
        }

        if (! $user) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                $errorKey => 'E-mail/CPF ou senha inválidos.',
            ]);
        }

        if ($user->isCartasUser()) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                $errorKey => 'Use a tela de acesso do Cartas para Esperançar.',
            ]);
        }

        if (! Auth::attempt([
            'email' => $user->email,
            'password' => $password,
            'sistema_origem' => \App\Models\User::SISTEMA_ENGAJA,
        ], $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                $errorKey => 'E-mail/CPF ou senha inválidos.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }
}
