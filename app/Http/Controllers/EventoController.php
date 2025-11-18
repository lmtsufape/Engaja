<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Eixo;
use App\Models\User;
use App\Models\Participante;
use App\Models\Atividade;
use App\Models\Inscricao;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Imports\ParticipantesImport;
use \Maatwebsite\Excel\Facades\Excel;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\CadastroParticipanteStoreRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EventoController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $r)
    {
        $q = Evento::with(['eixo', 'user'])
            ->when($r->q, function ($qq) use ($r) {
                $search = mb_strtolower($r->q);
                $qq->where(function ($w) use ($search) {
                    $w->whereRaw('LOWER(nome) LIKE ?', ['%' . $search . '%'])
                        ->orWhereRaw('LOWER(tipo) LIKE ?', ['%' . $search . '%'])
                        ->orWhereRaw('LOWER(objetivo) LIKE ?', ['%' . $search . '%']);
                });
            })
            ->when($r->eixo, fn($qq) => $qq->where('eixo_id', $r->eixo))
            ->when($r->de, fn($qq) => $qq->whereDate('data_inicio', '>=', $r->de))
            ->orderByDesc('id');

        $eventos = $q->paginate(10);
        $eixos   = Eixo::orderBy('nome')->get();

        return view('eventos.index', compact('eventos', 'eixos'));
    }

    public function create()
    {
        $this->authorize('create', Evento::class);

        $eixos = Eixo::orderBy('nome')->get();
        return view('eventos.create', compact('eixos'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Evento::class);

        $request->validate([
            'nome'        => 'required|string|max:255',
            'eixo_id'     => 'required|exists:eixos,id',
            'link'        => 'nullable|url',
            'data_inicio' => 'nullable|date',
            'data_fim'    => 'nullable|date|after_or_equal:data_inicio',
            'local'       => 'nullable|string|max:255',
            'imagem'      => 'nullable|mimes:jpg,jpeg,png,webp,avif,svg|max:2048',
        ]);

        $dados = $request->only([
            'eixo_id',
            'nome',
            'tipo',
            'data_inicio',
            'data_fim',
            'modalidade',
            'link',
            'objetivo',
            'resumo',
            'local'
        ]);
        $dados['user_id'] = Auth::id();

        if ($request->hasFile('imagem')) {
            $dados['imagem'] = $request->file('imagem')->store('eventos', 'public');
        }

        Evento::create($dados);

        return redirect()->route('eventos.index')->with('success', 'Evento criado com sucesso!');
    }

    public function show(Evento $evento)
    {
        $evento->load([
            'eixo',
            'user',
            'atividades' => fn($q) => $q
                ->with('municipio.estado')
                ->orderBy('dia')
                ->orderBy('hora_inicio'),
        ]);
        return view('eventos.show', compact('evento'));
    }

    public function edit(Evento $evento)
    {
        $this->authorize('update', $evento);

        $eixos = Eixo::orderBy('nome')->get();
        return view('eventos.edit', compact('evento', 'eixos'));
    }

    public function update(Request $request, Evento $evento)
    {
        $this->authorize('update', $evento);

        $request->validate([
            'nome'        => 'required|string|max:255',
            'eixo_id'     => 'required|exists:eixos,id',
            'link'        => 'nullable|url',
            'data_inicio' => 'nullable|date',
            'data_fim'    => 'nullable|date|after_or_equal:data_inicio',
            'local'       => 'nullable|string|max:255',
            'imagem'      => 'nullable|mimes:jpg,jpeg,png,webp,avif,svg|max:2048',
        ]);

        $evento->fill($request->only([
            'eixo_id',
            'nome',
            'tipo',
            'data_inicio',
            'data_fim',
            'modalidade',
            'link',
            'objetivo',
            'resumo',
            'local'
        ]));

        if ($request->hasFile('imagem')) {
            if ($evento->imagem) {
                Storage::disk('public')->delete($evento->imagem);
            }
            $evento->imagem = $request->file('imagem')->store('eventos', 'public');
        }

        $evento->save();

        return redirect()->route('eventos.index')->with('success', 'Evento atualizado com sucesso!');
    }

    public function destroy(Evento $evento)
    {
        $this->authorize('delete', $evento);

        if ($evento->imagem) {
            Storage::disk('public')->delete($evento->imagem);
        }

        $evento->delete();
        return redirect()->route('eventos.index')->with('success', 'Evento excluído.');
    }

    public function cadastro_inscricao($evento_id, $atividade_id)
    {
        $evento = Evento::findOrFail($evento_id);
        $atividade = Atividade::findOrFail($atividade_id);

        $municipios = \App\Models\Municipio::with('estado')
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id']);

        $participanteTags = config('engaja.participante_tags', Participante::TAGS);

        return view('auth.cadastro-participante', compact('evento', 'municipios', 'atividade', 'participanteTags'));
    }

    public function store_cadastro_inscricao(CadastroParticipanteStoreRequest $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . \App\Models\User::class],
            //'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $data = $request->validated();

        $evento = Evento::findOrFail($request->evento_id);
        $atividade = Atividade::findOrFail($request->atividade_id);

        try {
            DB::beginTransaction();

            $user = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(8)),
            ]);

            $user->assignRole('participante');

            $participanteData = [
                'cpf'              => $data['cpf']   ?? null,
                'telefone'         => $data['telefone']   ?? null,
                'municipio_id'     => $data['municipio_id']   ?? null,
                'escola_unidade'   => $data['escola_unidade'] ?? null,
                'tipo_organizacao' => $data['tipo_organizacao'] ?? null,
                'tag'              => $data['tag']            ?? null,
            ];

            $user->participante()->updateOrCreate(
                ['user_id' => $user->id],
                $participanteData
            );

            $inscricao = $this->inscricao($user, $evento, $atividade);
            $this->presenca($inscricao, $atividade);

            DB::commit();

            //Auth::login($user);

            return redirect()->route('eventos.show', $evento->id)->with('success', 'Cadastro, inscrição e presença realizados!');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Ocorreu um erro ao tentar realizar o cadastro!');
        }
    }

    public function inscricao(User $user, Evento $evento, Atividade $atividade)
    {
        $participante = $user->participante;

        $inscricao = Inscricao::withTrashed()
            ->where('participante_id', $participante->id)
            ->where('atividade_id', $atividade->id)
            ->first();

        if (!$inscricao) {
            $inscricao = Inscricao::withTrashed()
                ->where('participante_id', $participante->id)
                ->where('evento_id', $evento->id)
                ->whereNull('atividade_id')
                ->first();
        }

        if ($inscricao) {
            $inscricao->fill([
                'evento_id'       => $evento->id,
                'atividade_id'    => $atividade->id,
                'participante_id' => $participante->id,
            ]);
            $inscricao->deleted_at = null;
            $inscricao->save();
        } else {
            $inscricao = Inscricao::create([
                'evento_id'       => $evento->id,
                'atividade_id'    => $atividade->id,
                'participante_id' => $participante->id,
            ]);
        }

        return $inscricao;
    }

    public function presenca(Inscricao $inscricao, Atividade $atividade)
    {
        $atividade->presencas()->updateOrCreate(
            ['inscricao_id' => $inscricao->id, 'atividade_id' => $atividade->id],
            ['status' => 'presente']
        );
    }
}
