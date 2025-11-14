<?php

namespace App\Http\Controllers;

use App\Models\Dimensao;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DimensaoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Dimensao::query()->withCount('indicadores');

        $searchTerm = trim((string) $request->query('search', ''));
        if ($searchTerm !== '') {
            $query->where('descricao', 'like', '%' . $searchTerm . '%');
        }

        $hasIndicators = $request->query('has_indicators');
        if ($hasIndicators === 'with') {
            $query->whereHas('indicadores');
        } elseif ($hasIndicators === 'without') {
            $query->whereDoesntHave('indicadores');
        }

        $sort = $request->query('sort', 'descricao');
        $directionParam = $request->query('dir', $request->query('direction', 'asc'));
        $direction = Str::lower((string) $directionParam) === 'desc' ? 'desc' : 'asc';

        if ($sort === 'indicadores') {
            $query->orderBy('indicadores_count', $direction);
        } else {
            $query->orderBy('descricao', $direction);
        }

        $dimensaos = $query->paginate(15)->appends($request->query());

        return view('dimensaos.index', compact('dimensaos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('dimensaos.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'descricao' => 'required|string|max:255',
        ]);
        Dimensao::create($request->only('descricao'));

        return redirect()->route('dimensaos.index')
            ->with('success', 'Dimensão criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Dimensao $dimensao)
    {
        return view('dimensaos.show', compact('dimensao'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Dimensao $dimensao)
    {
        return view('dimensaos.edit', compact('dimensao'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Dimensao $dimensao)
    {
        $request->validate([
            'descricao' => 'required|string|max:255',
        ]);
        $dimensao->update($request->only('descricao'));

        return redirect()->route('dimensaos.index')
            ->with('success', 'Dimensão atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Dimensao $dimensao)
    {
        $dimensao->delete();

        return redirect()->route('dimensaos.index')
            ->with('success', 'Dimensão removida com sucesso!');
    }
}
