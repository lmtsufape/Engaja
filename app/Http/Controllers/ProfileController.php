<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Participante;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user()->load(['participante.municipio.estado']);

        // Se preferir carregar na view, pode remover isso:
        $municipios = \App\Models\Municipio::with('estado')
            ->orderBy('nome')
            ->get(['id','nome','estado_id']);

        $organizacoes = config('engaja.organizacoes', []);
        $participanteTags = config('engaja.participante_tags', Participante::TAGS);

        return view('profile.edit', [
            'user'             => $user,
            'municipios'       => $municipios,
            'organizacoes'     => $organizacoes,
            'participanteTags' => $participanteTags,
        ]);
    }

    /**
     * Update the user's profile information + participante.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validated();

        $oldEmail = $user->email;
        $user->fill([
            'name'  => $data['name'],
            'email' => $data['email'],
        ]);

        if ($oldEmail !== $data['email']) {
            $user->email_verified_at = null;
        }

        $user->save();

        $participanteData = [
            'cpf'            => $data['cpf']            ?? null,
            'telefone'       => $data['telefone']       ?? null,
            'municipio_id'   => $data['municipio_id']   ?? null,
            'escola_unidade' => $data['escola_unidade'] ?? null,
            'tag'            => $data['tag']            ?? null,
            // 'data_entrada'   => $data['data_entrada']   ?? null, // já 'Y-m-d' de <input type="date">
        ];

        $user->participante()->updateOrCreate(
            ['user_id' => $user->id],
            $participanteData
        );

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
