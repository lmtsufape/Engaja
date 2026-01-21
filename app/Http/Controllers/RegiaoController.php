<?php

namespace App\Http\Controllers;

use App\Models\Regiao;
use Illuminate\Http\Request;

class RegiaoController extends Controller
{
    public function index()
    {
        $regioes = Regiao::orderBy('nome')->get();
        return view('gerenciamento.regioes.index', compact('regioes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
        ]);
        Regiao::create($data);
        return back()->with('success', 'Região criada com sucesso.');
    }

    public function update(Request $request, Regiao $regiao)
    {
        $data = $request->validate([
            'nome' => ['required', 'string', 'max:255'],
        ]);
        $regiao->update($data);
        return back()->with('success', 'Região atualizada com sucesso.');
    }

    public function destroy(Regiao $regiao)
    {
        if ($regiao->estados()->exists()) {
            $lista = $regiao->estados()->pluck('nome')->implode(', ');
            return back()->with('error', "Não é possível excluir: há estados vinculados ({$lista}).");
        }
        $regiao->delete();
        return back()->with('success', 'Região removida com sucesso.');
    }
}
