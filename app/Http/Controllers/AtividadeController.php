<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Atividade;
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
            'carga_horaria' => 'required|integer|min:1',
        ]);

        $evento->atividades()->create($dados);

        return redirect()
            ->route('eventos.show', $evento)
            ->with('success', 'Atividade adicionada com sucesso!');
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
            'carga_horaria' => 'required|integer|min:1',
        ]);

        $atividade->update($dados);

        return redirect()
            ->route('eventos.show', $evento)
            ->with('success', 'Atividade atualizada com sucesso!');
    }

    public function destroy(Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('delete', $evento);

        $atividade->delete();

        return back()->with('success', 'Atividade removida.');
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
            $atividade->presenca_ativa ? 'Presença aberta para esta atividade.' : 'Presença fechada para esta atividade.'
        );
    }

    public function checkin(Atividade $atividade)
    {
        if (! $atividade->presenca_ativa) {
            return back()->withErrors(['checkin' => 'Presença não está aberta para esta atividade.']);
        }

        $user = auth()->user();

        // 1) Garante Participante para o usuário
        $participante = Participante::firstOrCreate(['user_id' => $user->id], []);

        // 2) Garante Inscrição no evento (cria/reativa)
        $evento = $atividade->evento;

        $inscricao = DB::table('inscricaos')
            ->where('evento_id', $evento->id)
            ->where('participante_id', $participante->id)
            ->first();

        if (! $inscricao) {
            DB::table('inscricaos')->insert([
                'evento_id'       => $evento->id,
                'participante_id' => $participante->id,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $inscricaoId = DB::table('inscricaos')
                ->where('evento_id', $evento->id)
                ->where('participante_id', $participante->id)
                ->value('id');
        } else {
            if ($inscricao->deleted_at !== null) {
                DB::table('inscricaos')->where('id', $inscricao->id)
                    ->update(['deleted_at' => null, 'updated_at' => now()]);
            }
            $inscricaoId = $inscricao->id;
        }

        // 3) Marca presença (idempotente)
        Presenca::updateOrCreate(
            ['inscricao_id' => $inscricaoId, 'atividade_id' => $atividade->id],
            ['status_participacao' => 'presente', 'justificativa' => null]
        );

        return back()->with('success', 'Presença confirmada com sucesso!');
    }
}
