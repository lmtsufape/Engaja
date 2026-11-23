<?php

namespace App\Http\Controllers;

use App\Models\Dimensao;
use App\Models\Evidencia;
use App\Models\Indicador;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EvidenciaController extends Controller
{
    public function index(Request $request)
    {
        $query = Evidencia::query()->with('indicador.dimensao');

        $searchTerm = trim((string) $request->query('search', ''));
        if ($searchTerm !== '') {
            $query->where('descricao', 'like', '%'.$searchTerm.'%');
        }

        $dimensaoId = $request->query('dimensao_id');
        if ($dimensaoId) {
            $query->whereHas('indicador.dimensao', function ($relation) use ($dimensaoId) {
                $relation->where('dimensaos.id', $dimensaoId);
            });
        }

        $indicadorId = $request->query('indicador_id');
        if ($indicadorId) {
            $query->where('indicador_id', $indicadorId);
        }

        $sort = $request->query('sort', 'descricao');
        $directionParam = $request->query('dir', $request->query('direction', 'asc'));
        $direction = Str::lower((string) $directionParam) === 'desc' ? 'desc' : 'asc';

        if ($sort === 'indicador') {
            $query->orderBy(
                Indicador::select('descricao')
                    ->whereColumn('indicadors.id', 'evidencias.indicador_id'),
                $direction
            );
        } elseif ($sort === 'dimensao') {
            $query->orderBy(
                Dimensao::select('descricao')
                    ->join('indicadors', 'indicadors.dimensao_id', '=', 'dimensaos.id')
                    ->whereColumn('indicadors.id', 'evidencias.indicador_id')
                    ->limit(1),
                $direction
            );
        } else {
            $query->orderBy('descricao', $direction);
        }

        $evidencias = $query->get();

        $dimensoes = Dimensao::orderBy('descricao')
            ->pluck('descricao', 'id')
            ->sort(SORT_NATURAL | SORT_FLAG_CASE);
        $indicadores = Indicador::with('dimensao')
            ->orderBy('descricao')
            ->get()
            ->mapWithKeys(function ($indicador) {
                $prefixo = $indicador->dimensao?->descricao;
                $label = $prefixo ? $prefixo.' - '.$indicador->descricao : $indicador->descricao;

                return [$indicador->id => $label];
            })
            ->sort(SORT_NATURAL | SORT_FLAG_CASE);

        return view('evidencias.index', compact('evidencias', 'dimensoes', 'indicadores'));
    }

    public function create()
    {
        $indicadores = Indicador::with('dimensao')
            ->orderBy('descricao')
            ->get()
            ->mapWithKeys(fn ($indicador) => [
                $indicador->id => $indicador->dimensao
                    ? $indicador->dimensao->descricao.' - '.$indicador->descricao
                    : $indicador->descricao,
            ]);

        return view('evidencias.create', compact('indicadores'));
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'indicador_id' => ['required', Rule::exists('indicadors', 'id')],
            'descricao' => ['required', 'string', 'max:255'],
        ]);

        Evidencia::create($dados);

        return redirect()->route('evidencias.index')->with('success', 'Evidência criada com sucesso!');
    }

    public function show(Evidencia $evidencia)
    {
        $evidencia->load('indicador.dimensao');

        return view('evidencias.show', compact('evidencia'));
    }

    public function edit(Evidencia $evidencia)
    {
        $dimensoes = Dimensao::orderBy('descricao')->pluck('descricao', 'id');

        $indicadores = Indicador::orderBy('descricao')->get(['id', 'descricao', 'dimensao_id']);

        $dimensaoAtualId = $evidencia->indicador ? $evidencia->indicador->dimensao_id : null;

        return view('evidencias.edit', compact('evidencia', 'dimensoes', 'indicadores', 'dimensaoAtualId'));
    }

    public function update(Request $request, Evidencia $evidencia)
    {
        $dados = $request->validate([
            'indicador_id' => ['required', Rule::exists('indicadors', 'id')],
            'descricao' => ['required', 'string', 'max:255'],
        ]);

        $evidencia->update($dados);

        return redirect()->route('evidencias.index')->with('success', 'Evidência atualizada com sucesso!');
    }

    public function destroy(Evidencia $evidencia)
    {
        $evidencia->delete();

        return redirect()->route('evidencias.index')->with('success', 'Evidência removida com sucesso!');
    }

    public function duplicar(Evidencia $evidencia)
    {
        $novaEvidencia = $evidencia->replicate();
        $novaEvidencia->descricao = $evidencia->descricao.' (Cópia)';
        $novaEvidencia->save();

        return redirect()->route('evidencias.edit', $novaEvidencia)
            ->with('success', 'Evidência duplicada com sucesso! Altere a dimensão ou indicador abaixo se necessário.');
    }
}
