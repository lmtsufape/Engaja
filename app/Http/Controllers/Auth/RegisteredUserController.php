<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'identidade_genero'            => ['required', 'string'],
            'identidade_genero_outro'      => ['nullable', 'string', 'max:255', 'required_if:identidade_genero,Outro'],
            'raca_cor'                     => ['required', 'string'],
            'comunidade_tradicional'       => ['required', 'string'],
            'comunidade_tradicional_outro' => ['nullable', 'string', 'max:255', 'required_if:comunidade_tradicional,Outro'],
            'faixa_etaria'                 => ['required', 'string'],
            'pcd'                          => ['required', 'string'],
            'orientacao_sexual'            => ['required', 'string'],
            'orientacao_sexual_outra'      => ['nullable', 'string', 'max:255', 'required_if:orientacao_sexual,Outra'],
    ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'identidade_genero'            => $request->identidade_genero,
            'identidade_genero_outro'      => $request->identidade_genero_outro,
            'raca_cor'                     => $request->raca_cor,
            'comunidade_tradicional'       => $request->comunidade_tradicional,
            'comunidade_tradicional_outro' => $request->comunidade_tradicional_outro,
            'faixa_etaria'                 => $request->faixa_etaria,
            'pcd'                          => $request->pcd,
            'orientacao_sexual'            => $request->orientacao_sexual,
            'orientacao_sexual_outra'      => $request->orientacao_sexual_outra,
        ]);

        $user->assignRole('participante');

        event(new Registered($user));

        Auth::login($user);

        return redirect('/');
    }
}
