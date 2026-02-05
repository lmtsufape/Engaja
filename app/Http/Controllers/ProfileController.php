<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Participante;
use App\Models\Certificado;
use App\Models\Inscricao;
use App\Models\Atividade;
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

    public function certificados(Request $request): View
    {
        $user = $request->user();
        $participanteId = $user->participante?->id;
        $certificados = Certificado::with(['modelo'])
            ->where('participante_id', $participanteId)
            ->orderByDesc('created_at')
            ->get();

        return view('profile.certificados', compact('certificados'));
    }

    public function presencas(Request $request)
    {
        $user = auth()->user();
        $participante = Participante::where('user_id', $user->id)->first();
        if (!$participante) {
            return view('profile.presencas', ['atividades' => collect(), 'eventos' => collect()]);
        }

        $eventoId = $request->input('evento_id');
        $dataDe = $request->input('data_de');
        $dataAte = $request->input('data_ate');
        $busca = $request->input('busca');

        $inscricoes = Inscricao::where('participante_id', $participante->id)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('atividade_id');

        $eventos = $inscricoes->pluck('evento')->unique('id')->filter();

        $atividadesQuery = Atividade::query()
            ->whereIn('evento_id', $eventos->pluck('id'))
            ->with([
                'evento',
                'presencas' => fn($q) => $q->whereIn('inscricao_id', $inscricoes->pluck('id'))
            ]);

        $atividadesQuery->when($eventoId, fn($q) => $q->where('evento_id', $eventoId));
        $atividadesQuery->when($dataDe, fn($q) => $q->where('dia', '>=', $dataDe));
        $atividadesQuery->when($dataAte, fn($q) => $q->where('dia', '<=', $dataAte));
        $atividadesQuery->when($busca, function($q) use ($busca) {
            $q->where('descricao', 'ilike', "%$busca%")
            ->orWhereHas('evento', fn($queryEvento) => $queryEvento->where('nome', 'ilike', "%$busca%"));
        });

        $atividades = $atividadesQuery
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        $dados = $atividades->map(function($atividade) use ($inscricoes) {
            $inscricao = $inscricoes->get($atividade->id);
            $presente = $atividade->presencas->where('inscricao_id', optional($inscricao)->id)->isNotEmpty();
            return [
                'data' => $atividade->dia,
                'hora' => $atividade->hora_inicio,
                'momento' => $atividade->nome ?? $atividade->descricao,
                'evento' => $atividade->evento->nome ?? '',
                'status' => $presente ? 'Presente' : 'Ausente',
            ];
        });

        return view('profile.presencas', [
            'atividades' => $dados,
            'eventos' => $eventos,
            'filtros' => [
                'evento_id' => $eventoId,
                'data_de' => $dataDe,
                'data_ate' => $dataAte,
                'busca' => $busca,
            ]
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
            'cpf'              => $data['cpf']              ?? null,
            'telefone'         => $data['telefone']         ?? null,
            'municipio_id'     => $data['municipio_id']     ?? null,
            'escola_unidade'   => $data['escola_unidade']   ?? null,
            'tipo_organizacao' => $data['tipo_organizacao'] ?? null,
            'tag'              => $data['tag']              ?? null,
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
