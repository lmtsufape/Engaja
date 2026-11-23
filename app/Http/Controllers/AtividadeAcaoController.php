<?php

namespace App\Http\Controllers;

use App\Models\AtividadeAcao;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AtividadeAcaoController extends Controller
{
    public function index()
    {
        $atividadeAcoes = AtividadeAcao::query()
            ->withCount('agendamentos')
            ->orderBy('nome')
            ->get();

        return view('atividade-acoes.index', compact('atividadeAcoes'));
    }

    public function create()
    {
        return view('atividade-acoes.create');
    }

    public function store(Request $request)
    {
        $dados = $this->validarDados($request);

        AtividadeAcao::create($dados);

        return redirect()
            ->route('atividade-acoes.index')
            ->with('success', 'Atividade/Ação criada com sucesso.');
    }

    public function show(AtividadeAcao $atividadeAcao)
    {
        $atividadeAcao->load(['agendamentos.municipio']);

        return view('atividade-acoes.show', compact('atividadeAcao'));
    }

    public function edit(AtividadeAcao $atividadeAcao)
    {
        return view('atividade-acoes.edit', compact('atividadeAcao'));
    }

    public function update(Request $request, AtividadeAcao $atividadeAcao)
    {
        $dados = $this->validarDados($request);

        $atividadeAcao->update($dados);

        return redirect()
            ->route('atividade-acoes.index')
            ->with('success', 'Atividade/Ação atualizada com sucesso.');
    }

    public function destroy(AtividadeAcao $atividadeAcao)
    {
        $atividadeAcao->delete();

        return redirect()
            ->route('atividade-acoes.index')
            ->with('success', 'Atividade/Ação removida com sucesso.');
    }

    private function validarDados(Request $request): array
    {
        $dados = $request->validate([
            'nome' => 'required|string|max:255',
            'detalhe' => 'nullable|string|max:2000',
            'usa_turmas' => 'nullable|boolean',
            'turmas' => 'nullable|array',
            'turmas.*' => 'nullable|string|max:255',
        ]);

        $usaTurmas = $request->boolean('usa_turmas');

        $turmas = collect($request->input('turmas', []))
            ->map(fn ($turma) => trim((string) $turma))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($usaTurmas && count($turmas) === 0) {
            throw ValidationException::withMessages([
                'turmas' => 'Informe pelo menos uma turma ao habilitar turmas na atividade/ação.',
            ]);
        }

        $dados['usa_turmas'] = $usaTurmas;
        $dados['turmas'] = $usaTurmas ? $turmas : [];

        return $dados;
    }
}
