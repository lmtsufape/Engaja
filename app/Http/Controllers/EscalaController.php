<?php

namespace App\Http\Controllers;

use App\Models\Escala;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EscalaController extends Controller
{
    public function index(Request $request)
    {
        $optionsCountExpression = '(CASE WHEN opcao1 IS NOT NULL THEN 1 ELSE 0 END'
            . ' + CASE WHEN opcao2 IS NOT NULL THEN 1 ELSE 0 END'
            . ' + CASE WHEN opcao3 IS NOT NULL THEN 1 ELSE 0 END'
            . ' + CASE WHEN opcao4 IS NOT NULL THEN 1 ELSE 0 END'
            . ' + CASE WHEN opcao5 IS NOT NULL THEN 1 ELSE 0 END)';

        $query = Escala::query();

        $searchTerm = trim((string) $request->query('search', ''));
        if ($searchTerm !== '') {
            $query->where('descricao', 'like', '%' . $searchTerm . '%');
        }

        $hasOptions = $request->query('has_options');
        if ($hasOptions === 'with') {
            $query->where(function ($nested) {
                $nested->whereNotNull('opcao1')
                    ->orWhereNotNull('opcao2')
                    ->orWhereNotNull('opcao3')
                    ->orWhereNotNull('opcao4')
                    ->orWhereNotNull('opcao5');
            });
        } elseif ($hasOptions === 'without') {
            $query->whereNull('opcao1')
                ->whereNull('opcao2')
                ->whereNull('opcao3')
                ->whereNull('opcao4')
                ->whereNull('opcao5');
        }

        $sort = $request->query('sort', 'descricao');
        $directionParam = $request->query('dir', $request->query('direction', 'asc'));
        $direction = Str::lower((string) $directionParam) === 'desc' ? 'desc' : 'asc';

        if ($sort === 'options') {
            $query->orderByRaw($optionsCountExpression . ' ' . $direction);
        } elseif ($sort === 'created_at') {
            $query->orderBy('created_at', $direction);
        } else {
            $query->orderBy('descricao', $direction);
        }

        $escalas = $query->paginate(15)->appends($request->query());

        return view('escalas.index', compact('escalas'));
    }

    public function create()
    {
        return view('escalas.create');
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'descricao' => 'required|string|max:255',
            'opcao1'    => 'nullable|string|max:255',
            'opcao2'    => 'nullable|string|max:255',
            'opcao3'    => 'nullable|string|max:255',
            'opcao4'    => 'nullable|string|max:255',
            'opcao5'    => 'nullable|string|max:255',
        ]);

        Escala::create($dados);

        return redirect()
            ->route('escalas.index')
            ->with('success', 'Escala criada com sucesso!');
    }

    public function show(Escala $escala)
    {
        return view('escalas.show', compact('escala'));
    }

    public function edit(Escala $escala)
    {
        return view('escalas.edit', compact('escala'));
    }

    public function update(Request $request, Escala $escala)
    {
        $dados = $request->validate([
            'descricao' => 'required|string|max:255',
            'opcao1'    => 'nullable|string|max:255',
            'opcao2'    => 'nullable|string|max:255',
            'opcao3'    => 'nullable|string|max:255',
            'opcao4'    => 'nullable|string|max:255',
            'opcao5'    => 'nullable|string|max:255',
        ]);

        $escala->update($dados);

        return redirect()
            ->route('escalas.index')
            ->with('success', 'Escala atualizada com sucesso!');
    }

    public function destroy(Escala $escala)
    {
        $escala->delete();

        return redirect()
            ->route('escalas.index')
            ->with('success', 'Escala removida com sucesso!');
    }
}
