<?php

namespace App\Http\Controllers;

use App\Exceptions\TemplateEmUsoException;
use App\Models\Evidencia;
use App\Models\Escala;
use App\Models\TemplateAvaliacao;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class TemplateAvaliacaoController extends Controller
{
    public function index(Request $request)
    {
        $query = TemplateAvaliacao::query()->withCount('questoes');

        $searchTerm = trim((string) $request->query('search', ''));
        if ($searchTerm !== '') {
            $query->where(function ($nested) use ($searchTerm) {
                $nested->where('nome', 'like', '%' . $searchTerm . '%')
                    ->orWhere('descricao', 'like', '%' . $searchTerm . '%');
            });
        }

        $hasQuestions = $request->query('has_questions');
        if ($hasQuestions === 'with') {
            $query->whereHas('questoes');
        } elseif ($hasQuestions === 'without') {
            $query->whereDoesntHave('questoes');
        }

        $sort = $request->query('sort', 'nome');
        $directionParam = $request->query('dir', $request->query('direction', 'asc'));
        $direction = Str::lower((string) $directionParam) === 'desc' ? 'desc' : 'asc';

        if ($sort === 'questoes') {
            $query->orderBy('questoes_count', $direction);
        } elseif ($sort === 'descricao') {
            $query->orderByRaw('COALESCE(descricao, \'\') ' . $direction);
        } elseif ($sort === 'created_at') {
            $query->orderBy('created_at', $direction);
        } else {
            $query->orderBy('nome', $direction);
        }

        $templates = $query->paginate(15)->appends($request->query());

        return view('templates-avaliacao.index', compact('templates'));
    }

    public function create()
    {
        return view('templates-avaliacao.create', $this->formDependencies());
    }

    public function store(Request $request)
    {
        $dadosTemplate = $this->validateTemplate($request);
        [$questoes] = $this->processaQuestoes($request, false);

        DB::transaction(function () use ($dadosTemplate, $questoes) {
            $template = TemplateAvaliacao::create($dadosTemplate);
            $this->persistQuestoes($template, $questoes);
        });

        return redirect()
            ->route('templates-avaliacao.index')
            ->with('success', 'Template de avaliação criado com sucesso!');
    }

    public function show(TemplateAvaliacao $template)
    {
        $template->load(['questoes.indicador.dimensao', 'questoes.evidencia', 'questoes.escala']);

        return view('templates-avaliacao.show', compact('template'));
    }

    public function edit(TemplateAvaliacao $template)
    {
        $template->load(['questoes.indicador.dimensao', 'questoes.evidencia', 'questoes.escala']);

        return view('templates-avaliacao.edit',
            array_merge($this->formDependencies(), compact('template'))
        );
    }

    public function update(Request $request, TemplateAvaliacao $template)
    {
        $dadosTemplate = $this->validateTemplate($request);
        [$questoes, $removidas] = $this->processaQuestoes($request, true);

        DB::transaction(function () use ($template, $dadosTemplate, $questoes, $removidas) {
            $template->update($dadosTemplate);
            $this->persistQuestoes($template, $questoes, $removidas);
        });

        return redirect()
            ->route('templates-avaliacao.index')
            ->with('success', 'Template de avaliação atualizado com sucesso!');
    }

    public function destroy(TemplateAvaliacao $template)
    {
        try {
            $template->delete();
        } catch (QueryException $exception) {
            if ($this->isForeignKeyConstraintViolation($exception)) {
                throw new TemplateEmUsoException($template, previous: $exception);
            }

            throw $exception;
        }

        return redirect()
            ->route('templates-avaliacao.index')
            ->with('success', 'Template de avaliação removido com sucesso!');
    }

    private function isForeignKeyConstraintViolation(QueryException $exception): bool
    {
        return (string) $exception->getCode() === '23503';
    }

    private function validateTemplate(Request $request): array
    {
        return $request->validate([
            'nome'      => ['required', 'string', 'max:255'],
            'descricao' => ['nullable', 'string'],
        ]);
    }

    private function formDependencies(): array
    {
        $evidencias = Evidencia::with('indicador.dimensao')
            ->orderBy('descricao')
            ->get()
            ->mapWithKeys(function ($evidencia) {
                $descricaoIndicador = $evidencia->indicador
                    ? ($evidencia->indicador->dimensao
                        ? $evidencia->indicador->dimensao->descricao . ' - ' . ($evidencia->indicador->descricao ?? '')
                        : ($evidencia->indicador->descricao ?? ''))
                    : null;

                return [
                    $evidencia->id => trim(
                        $descricaoIndicador
                            ? $descricaoIndicador . ' | ' . $evidencia->descricao
                            : $evidencia->descricao
                    ),
                ];
            });

        $escalas = Escala::orderBy('descricao')->pluck('descricao', 'id');

        $tiposQuestao = [
            'texto'  => 'Texto aberto',
            'escala' => 'Escala',
            'numero' => 'Numerica',
            'boolean'=> 'Sim/Nao',
        ];

        return compact('evidencias', 'escalas', 'tiposQuestao');
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: array}
     */
    private function processaQuestoes(Request $request, bool $permitirIds): array
    {
        $questoesInput = collect($request->input('questoes', []));
        $questoesAtivas = $questoesInput
            ->filter(fn ($questao) => empty($questao['_delete']))
            ->values();

        if ($questoesAtivas->isEmpty()) {
            throw ValidationException::withMessages([
                'questoes' => 'Informe pelo menos uma questao para o template.',
            ]);
        }

        $questoesValidadas = $questoesAtivas->map(function ($questao, int $index) use ($permitirIds) {
            // Indicador derivado da evidencia selecionada.
            $validator = Validator::make(
                $questao,
                [
                    'id'           => $permitirIds
                        ? ['nullable', 'integer', Rule::exists('questaos', 'id')->whereNull('deleted_at')]
                        : ['prohibited'],
                    'evidencia_id' => ['nullable', 'integer', Rule::exists('evidencias', 'id')],
                    'escala_id'    => ['nullable', 'integer', Rule::exists('escalas', 'id')],
                    'texto'        => ['required', 'string', 'max:1000'],
                    'tipo'         => ['required', 'string', Rule::in(['texto', 'escala', 'numero', 'boolean'])],
                    'ordem'        => ['nullable', 'integer', 'min:1', 'max:999'],
                    'fixa'         => ['nullable', 'boolean'],
                ],
                [],
                [
                    'id'           => "questoes.$index.id",
                    'evidencia_id' => "questoes.$index.evidencia_id",
                    'escala_id'    => "questoes.$index.escala_id",
                    'texto'        => "questoes.$index.texto",
                    'tipo'         => "questoes.$index.tipo",
                    'ordem'        => "questoes.$index.ordem",
                    'fixa'         => "questoes.$index.fixa",
                ]
            );

            $validator->after(function ($validator) use ($questao) {
                // Escala must be selected for 'escala' type
                if (($questao['tipo'] ?? null) === 'escala' && empty($questao['escala_id'])) {
                    $validator->errors()->add('escala_id', 'Selecione uma escala para questoes do tipo "Escala".');
                }

                // If question is fixed, evidence selection becomes mandatory
                $isFixa = ! empty($questao['fixa']);
                if ($isFixa && empty($questao['evidencia_id'])) {
                    $validator->errors()->add('evidencia_id', 'Selecione uma evidencia para questoes fixas.');
                }
            });

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $dados = $validator->validated();
            $dados['fixa'] = ! empty($questao['fixa']);

            if (! empty($dados['evidencia_id'])) {
                $evidencia = Evidencia::select('id', 'indicador_id')->find($dados['evidencia_id']);
                $dados['indicador_id'] = $evidencia?->indicador_id;
            } else {
                $dados['indicador_id'] = null;
            }

            if (($dados['tipo'] ?? null) !== 'escala') {
                $dados['escala_id'] = null;
            }

            $dados['ordem'] = $dados['ordem'] ?? ($index + 1);

            return $dados;
        });

        $questoesOrdenadas = $questoesValidadas
            ->sortBy(fn ($questao, $idx) => $questao['ordem'] ?? ($idx + 1))
            ->values();

        $questoesNormalizadas = $questoesOrdenadas->map(function ($questao, int $idx) {
            $questao['ordem'] = $questao['ordem'] ?? ($idx + 1);

            return $questao;
        });

        $idsRemovidos = $questoesInput
            ->filter(fn ($questao) => ! empty($questao['_delete']) && ! empty($questao['id']))
            ->pluck('id')
            ->all();

        return [$questoesNormalizadas, $idsRemovidos];
    }

    private function persistQuestoes(TemplateAvaliacao $template, Collection $questoes, array $removidas = []): void
    {
        if (! empty($removidas)) {
            $template->questoes()->whereIn('id', $removidas)->delete();
        }

        $idsMantidos = [];

        foreach ($questoes as $questao) {
            $dados = $questao;
            $questaoId = $dados['id'] ?? null;
            unset($dados['id']);

            if ($questaoId) {
                $modelo = $template->questoes()->whereKey($questaoId)->firstOrFail();
                $modelo->update($dados);
                $idsMantidos[] = $modelo->id;

                continue;
            }

            $dados['template_avaliacao_id'] = $template->id;
            $modelo = $template->questoes()->create($dados);
            $idsMantidos[] = $modelo->id;
        }

        if (! empty($idsMantidos)) {
            $template->questoes()->whereNotIn('id', $idsMantidos)->delete();
        } else {
            $template->questoes()->delete();
        }
    }
}
