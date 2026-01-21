<?php

namespace App\Http\Controllers;

use App\Models\Municipio;
use App\Models\Estado;
use Illuminate\Http\Request;

class MunicipioController extends Controller
{
    public function index()
    {
        $municipios = Municipio::with(['estado.regiao'])->orderBy('nome')->get();
        $estados = Estado::with('regiao')->orderBy('nome')->get();
        return view('gerenciamento.municipios.index', compact('municipios', 'estados'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'estado_id' => ['required', 'exists:estados,id'],
            'nome'      => ['required', 'string', 'max:255'],
        ]);
        Municipio::create($data);
        return back()->with('success', 'Município criado com sucesso.');
    }

    public function update(Request $request, Municipio $municipio)
    {
        $data = $request->validate([
            'estado_id' => ['required', 'exists:estados,id'],
            'nome'      => ['required', 'string', 'max:255'],
        ]);
        $municipio->update($data);
        return back()->with('success', 'Município atualizado com sucesso.');
    }

    public function destroy(Municipio $municipio)
    {
        $municipio->delete();
        return back()->with('success', 'Município removido com sucesso.');
    }
}
