<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Atividade;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AtividadeController extends Controller
{
    use AuthorizesRequests;
    public function index(Evento $evento)
    {
        $this->authorize('update', $evento);
        $atividades = $evento->atividades()->orderBy('dia')->orderBy('hora_inicio')->paginate(12);
        return view('atividades.index', compact('evento','atividades'));
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
            'dia'           => 'required|date',
            'hora_inicio'   => 'required|date_format:H:i',
            'carga_horaria' => 'required|integer|min:1',
        ]);

        $evento->atividades()->create($dados);

        return redirect()
            ->route('eventos.show', $evento)
            ->with('success','Atividade adicionada com sucesso!');
    }

    public function edit(Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('update', $evento);

        return view('atividades.edit', compact('evento','atividade'));
    }

    public function update(Request $request, Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('update', $evento);

        $dados = $request->validate([
            'dia'           => 'required|date',
            'hora_inicio'   => 'required|date_format:H:i',
            'carga_horaria' => 'required|integer|min:1',
        ]);

        $atividade->update($dados);

        return redirect()
            ->route('eventos.show', $evento)
            ->with('success','Atividade atualizada com sucesso!');
    }

    public function destroy(Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('delete', $evento);

        $atividade->delete();

        return back()->with('success','Atividade removida.');
    }

    public function show(Atividade $atividade)
    {
        $atividade->load('evento.participantes.user','presencas.inscricao.participante.user');

        return view('atividades.show', compact('atividade'));
    }
}
