<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Atividade;
use App\Models\Inscricao;
use App\Models\Presenca;
use App\Models\Participante;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AtividadeController extends Controller
{
    use AuthorizesRequests;
    public function index(Evento $evento)
    {
        $atividades = $evento->atividades()->orderBy('dia')->orderBy('hora_inicio')->paginate(12);
        return view('atividades.index', compact('evento', 'atividades'));
    }

    public function create(Evento $evento)
    {
        $this->authorize('update', $evento);
        return view('atividades.create', compact('evento'));
    }

    public function store(Request $request, Evento $evento)
    {
        $this->authorize('update', $evento);

        $dados = $request->validate([
            'descricao'     => 'required|string',
            'dia'           => 'required|date',
            'hora_inicio'   => 'required|date_format:H:i',
            'hora_fim'      => 'required|date_format:H:i|after:hora_inicio',
        ]);

        $evento->atividades()->create($dados);

        return redirect()
            ->route('eventos.show', $evento)
            ->with('success', 'Momento adicionado com sucesso!');
    }

    public function edit(Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('update', $evento);

        return view('atividades.edit', compact('evento', 'atividade'));
    }

    public function update(Request $request, Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('update', $evento);

        $dados = $request->validate([
            'descricao'     => 'required|string',
            'dia'           => 'required|date',
            'hora_inicio'   => 'required|date_format:H:i',
            'hora_fim'      => 'required|date_format:H:i|after:hora_inicio',
        ]);

        $atividade->update($dados);

        return redirect()
            ->route('eventos.show', $evento)
            ->with('success', 'Momento atualizado com sucesso!');
    }

    public function destroy(Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('delete', $evento);

        $atividade->delete();

        return back()->with('success', 'Momento removida.');
    }

    public function show(\App\Models\Atividade $atividade)
    {
        $atividade->load('evento');

        $presencas = $atividade->presencas()
            ->with([
                'inscricao.participante.user:id,name,email',
                'inscricao.participante.municipio.estado:id,nome,sigla',
            ])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
        $user = auth()->user();
        $podeImportar = $user?->can('presenca.import') ?? false;
        $podeAbrir    = $user?->can('presenca.abrir')   ?? false;

        return view('atividades.show', compact('atividade', 'presencas', 'podeImportar', 'podeAbrir'));
    }

    public function togglePresenca(Atividade $atividade)
    {
        // Permissão já garantida pela middleware 'permission:presenca.abrir'
        $atividade->presenca_ativa = ! $atividade->presenca_ativa;
        $atividade->save();

        return back()->with(
            'success',
            $atividade->presenca_ativa ? 'Presença aberta para este momento.' : 'Presença fechada para este momento.'
        );
    }

    public function checkin(Atividade $atividade)
    {
        if (! $atividade->presenca_ativa) {
            return back()->withErrors(['checkin' => 'Presença não está aberta para este momento.']);
        }

        $user = auth()->user();

        // 1) Garante Participante para o usuário
        $participante = Participante::firstOrCreate(['user_id' => $user->id], []);

        // 2) Garante Inscrição no evento (cria/reativa)
        $evento = $atividade->evento;

        $inscricao = Inscricao::withTrashed()
            ->where('participante_id', $participante->id)
            ->where('atividade_id', $atividade->id)
            ->first();

        if (! $inscricao) {
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

        // 3) Marca presenca (idempotente)
        Presenca::updateOrCreate(
            ['inscricao_id' => $inscricao->id, 'atividade_id' => $atividade->id],
            ['status' => 'presente', 'justificativa' => null]
        );

        return back()->with('success', 'Presença confirmada com sucesso!');
    }
}
