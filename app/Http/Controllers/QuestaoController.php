<?php

namespace App\Http\Controllers;

use App\Models\Escala;
use App\Models\Indicador;
use App\Models\Evidencia;
use App\Models\Questao;
use App\Models\TemplateAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuestaoController extends Controller
{
    public function index()
    {
        $questaos = Questao::with(['indicador.dimensao', 'evidencia.indicador', 'escala', 'template'])
            ->orderBy('texto')
            ->paginate(15);

        return view('questaos.index', compact('questaos'));
    }

    public function create()
    {
        [$evidencias, $escalas, $templates] = $this->formSelections();

        return view('questaos.create', compact('evidencias', 'escalas', 'templates'));
    }

    public function store(Request $request)
    {
        $dados = $this->validateQuestao($request);

        $dados['ordem'] = $dados['ordem'] ?? $this->proximaOrdem($dados['template_avaliacao_id']);

        Questao::create($dados);

        return redirect()
            ->route('questaos.index')
            ->with('success', 'Questão criada com sucesso!');
    }

    public function show(Questao $questao)
    {
        $questao->load(['indicador.dimensao', 'evidencia.indicador', 'escala', 'template']);

        return view('questaos.show', compact('questao'));
    }

    public function edit(Questao $questao)
    {
        [$evidencias, $escalas, $templates] = $this->formSelections();

        return view('questaos.edit', compact('questao', 'evidencias', 'escalas', 'templates'));
    }

    public function update(Request $request, Questao $questao)
    {
        $dados = $this->validateQuestao($request);

        $dados['ordem'] = $dados['ordem'] ?? $questao->ordem ?? $this->proximaOrdem($dados['template_avaliacao_id']);

        $questao->update($dados);

        return redirect()
            ->route('questaos.index')
            ->with('success', 'Questão atualizada com sucesso!');
    }

    public function destroy(Questao $questao)
    {
        $questao->delete();

        return redirect()
            ->route('questaos.index')
            ->with('success', 'Questão removida com sucesso!');
    }

    private function validateQuestao(Request $request): array
    {
        $dados = $request->validate([
            'template_avaliacao_id' => ['required', Rule::exists('template_avaliacaos', 'id')],
            'evidencia_id'          => ['required', Rule::exists('evidencias', 'id')],
            'escala_id'             => ['nullable', Rule::exists('escalas', 'id')],
            'texto'                 => ['required', 'string', 'max:1000'],
            'tipo'                  => ['required', 'string', Rule::in(['texto', 'escala', 'numero', 'boolean'])],
            'ordem'                 => ['nullable', 'integer', 'min:1', 'max:999'],
            'fixa'                  => ['nullable', 'boolean'],
        ]);

        $dados['fixa'] = $request->boolean('fixa');

        if ($dados['tipo'] === 'escala' && empty($dados['escala_id'])) {
            $request->validate([
                'escala_id' => ['required', Rule::exists('escalas', 'id')],
            ]);
        }

        if ($dados['tipo'] !== 'escala') {
            $dados['escala_id'] = null;
        }

        // Derive indicador_id from evidencia for consistency/back-compat
        if (!empty($dados['evidencia_id'])) {
            $evidencia = Evidencia::find($dados['evidencia_id']);
            if ($evidencia) {
                $dados['indicador_id'] = $evidencia->indicador_id;
            }
        }

        return $dados;
    }

    private function proximaOrdem(int $templateId): int
    {
        $maiorOrdem = Questao::where('template_avaliacao_id', $templateId)->max('ordem');

        return $maiorOrdem ? $maiorOrdem + 1 : 1;
    }

    private function formSelections(): array
    {
        $evidencias = Evidencia::with('indicador.dimensao')
            ->orderBy('descricao')
            ->get()
            ->mapWithKeys(fn ($evidencia) => [
                $evidencia->id => ($evidencia->indicador && $evidencia->indicador->dimensao
                    ? $evidencia->indicador->dimensao->descricao . ' - '
                    : '') . ($evidencia->indicador->descricao ?? '') . ' | ' . $evidencia->descricao,
            ]);

        $escalas = Escala::orderBy('descricao')->pluck('descricao', 'id');
        $templates = TemplateAvaliacao::orderBy('nome')->pluck('nome', 'id');

        return [$evidencias, $escalas, $templates];
    }
}
