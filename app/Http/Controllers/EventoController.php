<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Eixo; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EventoController extends Controller
{
    use AuthorizesRequests;

    public function create()
    {
        $eixos = \App\Models\Eixo::orderBy('nome')->get();
        return view('eventos.create', compact('eixos'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'nome'    => 'required|string|max:255',
            'eixo_id' => 'required|exists:eixos,id',
            'duracao' => 'nullable|integer|min:0',
            'link'    => 'nullable|url',
        ]);


        Evento::create([
            'user_id' => Auth::id(),
            'eixo_id' => $request->eixo_id,
            'nome' => $request->nome,
            'tipo' => $request->tipo,
            'data_horario' => $request->data_horario,
            'duracao' => $request->duracao,
            'modalidade' => $request->modalidade,
            'link' => $request->link,
            'objetivo' => $request->objetivo,
            'resumo' => $request->resumo,
        ]);

        return redirect()->route('eventos.index')->with('success','Evento criado com sucesso!');
    }

    public function index(Request $r)
    {
        $q = Evento::with('eixo','user')
            ->when($r->q, fn($qq) =>
                $qq->where(function($w) use ($r){
                    $w->where('nome','ilike','%'.$r->q.'%')
                    ->orWhere('tipo','ilike','%'.$r->q.'%')
                    ->orWhere('objetivo','ilike','%'.$r->q.'%');
                })
            )
            ->when($r->eixo, fn($qq) => $qq->where('eixo_id', $r->eixo))
            ->when($r->de, fn($qq) => $qq->whereDate('data_horario','>=',$r->de))
            ->orderByDesc('id');

        $eventos = $q->paginate(10);
        $eixos = Eixo::orderBy('nome')->get();

        return view('eventos.index', compact('eventos','eixos'));
    }

    public function edit(Evento $evento)
    {
        $this->authorize('update', $evento);

        $eixos = \App\Models\Eixo::orderBy('nome')->get();
        return view('eventos.edit', compact('evento','eixos'));
    }

    public function update(Request $request, Evento $evento)
    {
        $request->validate([
            'nome'    => 'required|string|max:255',
            'eixo_id' => 'required|exists:eixos,id',
            'duracao' => 'nullable|integer|min:0',
            'link'    => 'nullable|url',
        ]);

        $evento->update($request->only([
            'eixo_id','nome','tipo','data_horario','duracao','modalidade','link','objetivo','resumo'
        ]));

        return redirect()->route('eventos.index')->with('success','Evento atualizado com sucesso!');
    }

    public function destroy(Evento $evento)
    {
        $this->authorize('delete', $evento);
        $evento->delete();
        return back()->with('success','Evento excluído.');
    }
}
