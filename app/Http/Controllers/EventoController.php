<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Eixo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class EventoController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $r)
    {
        $q = Evento::with(['eixo','user'])
            ->when($r->q, function ($qq) use ($r) {
                $qq->where(function ($w) use ($r) {
                    $w->where('nome', 'ilike', '%'.$r->q.'%')
                      ->orWhere('tipo', 'ilike', '%'.$r->q.'%')
                      ->orWhere('objetivo', 'ilike', '%'.$r->q.'%');
                });
            })
            ->when($r->eixo, fn($qq) => $qq->where('eixo_id', $r->eixo))
            ->when($r->de, fn($qq) => $qq->whereDate('data_horario','>=',$r->de))
            ->orderByDesc('id');

        $eventos = $q->paginate(10);
        $eixos   = Eixo::orderBy('nome')->get();

        return view('eventos.index', compact('eventos','eixos'));
    }

    public function create()
    {
        $eixos = Eixo::orderBy('nome')->get();
        return view('eventos.create', compact('eixos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nome'       => 'required|string|max:255',
            'eixo_id'    => 'required|exists:eixos,id',
            'duracao'    => 'nullable|integer|min:0',
            'link'       => 'nullable|url',
            'data_horario' => 'nullable|date',
            'local'      => 'nullable|string|max:255',
            'imagem'     => 'nullable|mimes:jpg,jpeg,png,webp,avif,svg|max:2048',
        ]);

        $dados = $request->only([
            'eixo_id','nome','tipo','data_horario','duracao','modalidade','link','objetivo','resumo','local'
        ]);
        $dados['user_id'] = Auth::id();

        if ($request->hasFile('imagem')) {
            $dados['imagem'] = $request->file('imagem')->store('eventos', 'public');
        }

        Evento::create($dados);

        return redirect()->route('eventos.index')->with('success','Evento criado com sucesso!');
    }

    public function show(Evento $evento)
    {
        $evento->load(['eixo','user']);
        return view('eventos.show', compact('evento'));
    }

    public function edit(Evento $evento)
    {
        $this->authorize('update', $evento);

        $eixos = Eixo::orderBy('nome')->get();
        return view('eventos.edit', compact('evento','eixos'));
    }

    public function update(Request $request, Evento $evento)
    {
        $this->authorize('update', $evento);

        $request->validate([
            'nome'       => 'required|string|max:255',
            'eixo_id'    => 'required|exists:eixos,id',
            'duracao'    => 'nullable|integer|min:0',
            'link'       => 'nullable|url',
            'data_horario' => 'nullable|date',
            'local'      => 'nullable|string|max:255',
            'imagem'     => 'nullable|mimes:jpg,jpeg,png,webp,avif,svg|max:2048',
        ]);

        $evento->fill($request->only([
            'eixo_id','nome','tipo','data_horario','duracao','modalidade','link','objetivo','resumo','local'
        ]));

        if ($request->hasFile('imagem')) {
            
            if ($evento->imagem) {
                Storage::disk('public')->delete($evento->imagem);
            }
            $evento->imagem = $request->file('imagem')->store('eventos', 'public');
        }

        $evento->save();

        return redirect()->route('eventos.index')->with('success','Evento atualizado com sucesso!');
    }

    public function destroy(Evento $evento)
    {
        $this->authorize('delete', $evento);

        if ($evento->imagem) {
            Storage::disk('public')->delete($evento->imagem);
        }

        $evento->delete();
        return back()->with('success','Evento excluído.');
    }
}
