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

    public function completeDemographics(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identidade_genero'        => ['required', 'string'],
            'identidade_genero_outro'  => ['nullable', 'string', 'max:255', 'required_if:identidade_genero,Outro'],
            'raca_cor'                 => ['required', 'string'],
            'comunidade_tradicional'   => ['required', 'string'],
            'comunidade_tradicional_outro' => ['nullable', 'string', 'max:255', 'required_if:comunidade_tradicional,Outro'],
            'faixa_etaria'             => ['required', 'string'],
            'pcd'                      => ['required', 'string'],
            'orientacao_sexual'        => ['required', 'string'],
            'orientacao_sexual_outra'  => ['nullable', 'string', 'max:255', 'required_if:orientacao_sexual,Outra'],
        ], [
            'identidade_genero.required'       => 'Identidade de gênero é obrigatória.',
            'raca_cor.required'                => 'Raça/Cor é obrigatória.',
            'comunidade_tradicional.required'  => 'Pertencimento a comunidade é obrigatório.',
            'faixa_etaria.required'            => 'Faixa etária é obrigatória.',
            'pcd.required'                     => 'Campo PcD é obrigatório.',
            'orientacao_sexual.required'       => 'Orientação sexual é obrigatória.',
            'identidade_genero_outro.required_if'      => 'Especifique sua identidade de gênero.',
            'comunidade_tradicional_outro.required_if' => 'Especifique a comunidade tradicional.',
            'orientacao_sexual_outra.required_if'      => 'Especifique sua orientação sexual.',
        ]);

        $request->user()->update($data);

        return Redirect::back()->with('status', 'demograficos-salvos');
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
        $dataDe   = $request->input('data_de');
        $dataAte  = $request->input('data_ate');
        $busca    = $request->input('busca');

        $inscricoes = Inscricao::where('participante_id', $participante->id)
            ->whereNull('deleted_at')
            ->get()
            ->keyBy('atividade_id');

        $atividadeIds = $inscricoes->keys(); 

        $eventos = Inscricao::where('participante_id', $participante->id)
            ->whereNull('deleted_at')
            ->with('evento')
            ->get()
            ->pluck('evento')
            ->unique('id')
            ->filter()
            ->values();

        $atividadesQuery = Atividade::query()
            ->whereIn('id', $atividadeIds)           
            ->with([
                'evento',
                'presencas' => fn($q) => $q->whereIn('inscricao_id', $inscricoes->pluck('id')),
            ]);

        $atividadesQuery->when($eventoId, fn($q) => $q->where('evento_id', $eventoId));
        $atividadesQuery->when($dataDe,   fn($q) => $q->where('dia', '>=', $dataDe));
        $atividadesQuery->when($dataAte,  fn($q) => $q->where('dia', '<=', $dataAte));
        $atividadesQuery->when($busca, function($q) use ($busca) {
            $q->where('descricao', 'ilike', "%$busca%")
            ->orWhereHas('evento', fn($qe) => $qe->where('nome', 'ilike', "%$busca%"));
        });

        $atividades = $atividadesQuery
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        $dados = $atividades->map(function ($atividade) use ($inscricoes) {
            $inscricao = $inscricoes->get($atividade->id);
            $presente  = $atividade->presencas
                ->where('inscricao_id', optional($inscricao)->id)
                ->isNotEmpty();

            return [
                'data'    => $atividade->dia,
                'hora'    => $atividade->hora_inicio,
                'momento' => $atividade->descricao,
                'evento'  => $atividade->evento->nome ?? '',
                'status'  => $presente ? 'Presente' : 'Ausente',
            ];
        });

        return view('profile.presencas', [
            'atividades' => $dados,
            'eventos'    => $eventos,
            'filtros'    => [
                'evento_id' => $eventoId,
                'data_de'   => $dataDe,
                'data_ate'  => $dataAte,
                'busca'     => $busca,
            ],
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
