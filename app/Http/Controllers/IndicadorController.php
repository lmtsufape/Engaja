<?php

namespace App\Http\Controllers;

use App\Models\Indicador;
use App\Models\Dimensao;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IndicadorController extends Controller
{
    public function index(Request $request)
    {
        $query = Indicador::query()
            ->with('dimensao')
            ->withCount('questoes');

        $searchTerm = trim((string) $request->query('search', ''));
        if ($searchTerm !== '') {
            $query->where('descricao', 'like', '%' . $searchTerm . '%');
        }

        $dimensaoId = $request->query('dimensao_id');
        if ($dimensaoId) {
            $query->where('dimensao_id', $dimensaoId);
        }

        $hasQuestoes = $request->query('has_questoes');
        if ($hasQuestoes === 'with') {
            $query->whereHas('questoes');
        } elseif ($hasQuestoes === 'without') {
            $query->whereDoesntHave('questoes');
        }

        $sort = $request->query('sort', 'descricao');
        $directionParam = $request->query('dir', $request->query('direction', 'asc'));
        $direction = Str::lower((string) $directionParam) === 'desc' ? 'desc' : 'asc';

        if ($sort === 'questoes') {
            $query->orderBy('questoes_count', $direction);
        } elseif ($sort === 'dimensao') {
            $query->orderBy(Dimensao::select('descricao')->whereColumn('dimensaos.id', 'indicadors.dimensao_id'), $direction);
        } else {
            $query->orderBy('descricao', $direction);
        }

        $indicadors = $query->paginate(15)->appends($request->query());
        $dimensoes = Dimensao::orderBy('descricao')->pluck('descricao', 'id');

        return view('indicadors.index', compact('indicadors', 'dimensoes'));
    }

    public function create()
    {
        $dimensoes = Dimensao::orderBy('descricao')->pluck('descricao', 'id');
        return view('indicadors.create', compact('dimensoes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'dimensao_id' => 'required|exists:dimensaos,id',
            'descricao'   => 'required|string|max:255',
        ]);
        Indicador::create($request->only('dimensao_id', 'descricao'));
        return redirect()->route('indicadors.index')
            ->with('success', 'Indicador criado com sucesso!');
    }

    public function show(Indicador $indicador)
    {
        return view('indicadors.show', compact('indicador'));
    }

    public function edit(Indicador $indicador)
    {
        $dimensoes = Dimensao::orderBy('descricao')->pluck('descricao', 'id');
        return view('indicadors.edit', compact('indicador', 'dimensoes'));
    }

    public function update(Request $request, Indicador $indicador)
    {
        $request->validate([
            'dimensao_id' => 'required|exists:dimensaos,id',
            'descricao'   => 'required|string|max:255',
        ]);
        $indicador->update($request->only('dimensao_id', 'descricao'));
        return redirect()->route('indicadors.index')
            ->with('success', 'Indicador atualizado com sucesso!');
    }

    public function destroy(Indicador $indicador)
    {
        $indicador->delete();
        return redirect()->route('indicadors.index')
            ->with('success', 'Indicador removido com sucesso!');
    }
}
