<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use App\Models\Regiao;
use Illuminate\Http\Request;

class EstadoController extends Controller
{
    public function index()
    {
        $estados = Estado::with('regiao')->orderBy('nome')->get();
        $regioes = Regiao::orderBy('nome')->get();
        return view('gerenciamento.estados.index', compact('estados', 'regioes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'regiao_id' => ['required', 'exists:regiaos,id'],
            'nome'      => ['required', 'string', 'max:255'],
            'sigla'     => ['required', 'string', 'max:5'],
        ]);
        Estado::create($data);
        return back()->with('success', 'Estado criado com sucesso.');
    }

    public function update(Request $request, Estado $estado)
    {
        $data = $request->validate([
            'regiao_id' => ['required', 'exists:regiaos,id'],
            'nome'      => ['required', 'string', 'max:255'],
            'sigla'     => ['required', 'string', 'max:5'],
        ]);
        $estado->update($data);
        return back()->with('success', 'Estado atualizado com sucesso.');
    }

    public function destroy(Estado $estado)
    {
        if ($estado->municipios()->exists()) {
            $lista = $estado->municipios()->pluck('nome')->implode(', ');
            return back()->with('error', "Não é possível excluir: há municípios vinculados ({$lista}).");
        }
        $estado->delete();
        return back()->with('success', 'Estado removido com sucesso.');
    }
}
