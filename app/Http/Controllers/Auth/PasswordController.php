<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], $this->passwordValidationMessages(), $this->passwordValidationAttributes());

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_password_change' => false,
        ]);

        return back()->with('status', 'password-updated');
    }

    public function forceEdit()
    {
        return view('auth.force-password-change');
    }

    public function forceUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], $this->passwordValidationMessages(), $this->passwordValidationAttributes());

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'force_password_change' => false,
        ]);

        return redirect()
            ->intended('/')
            ->with('success', 'Senha atualizada com sucesso.');
    }

    private function passwordValidationMessages(): array
    {
        return [
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'current_password.required' => 'Informe a senha atual.',
            'current_password.current_password' => 'A senha atual está incorreta.',
        ];
    }

    private function passwordValidationAttributes(): array
    {
        return [
            'password' => 'senha',
            'password_confirmation' => 'confirmação da senha',
            'current_password' => 'senha atual',
        ];
    }
}
