<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Avaliacao;
use App\Models\AvaliacaoQuestao;
use App\Models\Evidencia;
use App\Models\Escala;
use App\Models\Inscricao;
use App\Models\Presenca;
use App\Models\RespostaAvaliacao;
use App\Models\SubmissaoAvaliacao;
use App\Models\TemplateAvaliacao;
use App\ViewModels\Avaliacao\QuestoesFormViewModel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Throwable;

class AvaliacaoController extends Controller
{
    public function index(Request $request)
    {
        $avaliacaoTable = (new Avaliacao())->getTable();

        $query = Avaliacao::query()->with([
            'inscricao.participante.user',
            'inscricao.evento',
            'atividade.evento',
            'templateAvaliacao',
            'respostas.submissaoAvaliacao',
        ]);

        $searchTerm = trim((string) $request->query('search', ''));
        if ($searchTerm !== '') {
            $query->where(function ($nested) use ($searchTerm) {
                $nested->whereHas('atividade', function ($atividade) use ($searchTerm) {
                    $atividade->where('descricao', 'like', '%' . $searchTerm . '%')
                        ->orWhereHas('evento', function ($evento) use ($searchTerm) {
                            $evento->where('nome', 'like', '%' . $searchTerm . '%');
                        });
                })
                    ->orWhereHas('templateAvaliacao', function ($template) use ($searchTerm) {
                        $template->where('nome', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('inscricao.participante.user', function ($usuario) use ($searchTerm) {
                        $usuario->where('name', 'like', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('inscricao.evento', function ($evento) use ($searchTerm) {
                        $evento->where('nome', 'like', '%' . $searchTerm . '%');
                    });

                if (ctype_digit($searchTerm)) {
                    $nested->orWhere('id', (int) $searchTerm);
                }
            });
        }

        $templateId = $request->query('template_id');
        if ($templateId) {
            $query->where('template_avaliacao_id', $templateId);
        }

        $from = $request->query('de');
        if ($from) {
            $query->whereDate("{$avaliacaoTable}.created_at", '>=', $from);
        }

        $to = $request->query('ate');
        if ($to) {
            $query->whereDate("{$avaliacaoTable}.created_at", '<=', $to);
        }

        $hasRespostas = $request->query('has_respostas');
        if ($hasRespostas === 'with') {
            $query->whereHas('respostas');
        } elseif ($hasRespostas === 'without') {
            $query->whereDoesntHave('respostas');
        }

        $sort = $request->query('sort', 'created_at');
        $directionParam = $request->query('dir', $request->query('direction', 'desc'));
        $direction = Str::lower((string) $directionParam) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'momento') {
            $query->orderBy(
                Atividade::select('dia')
                    ->whereColumn('atividades.id', "{$avaliacaoTable}.atividade_id"),
                $direction
            )->orderBy(
                Atividade::select('hora_inicio')
                    ->whereColumn('atividades.id', "{$avaliacaoTable}.atividade_id"),
                $direction
            );
        } elseif ($sort === 'template') {
            $query->orderBy(
                TemplateAvaliacao::select('nome')
                    ->whereColumn('template_avaliacaos.id', "{$avaliacaoTable}.template_avaliacao_id"),
                $direction
            );
        } elseif ($sort === 'created_at') {
            $query->orderBy("{$avaliacaoTable}.created_at", $direction);
        } else {
            $query->orderBy("{$avaliacaoTable}.created_at", 'desc');
        }

        if ($sort !== 'created_at') {
            $query->orderBy("{$avaliacaoTable}.created_at", 'desc');
        }

        $avaliacoes = $query->paginate(15)->appends($request->query());
        $templatesDisponiveis = TemplateAvaliacao::orderBy('nome')->pluck('nome', 'id');

        return view('avaliacoes.index', compact('avaliacoes', 'templatesDisponiveis'));
    }

    public function create(Request $request)
    {
        $atividades = Atividade::with('evento')
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        $templates = TemplateAvaliacao::with(['questoes.escala', 'questoes.indicador.dimensao', 'questoes.evidencia.indicador'])
            ->orderBy('nome')
            ->get();

        $evidencias = Evidencia::with('indicador.dimensao')
            ->orderBy('descricao')
            ->get();

        $escalas = Escala::orderBy('descricao')->get();

        $tiposQuestao = $this->tiposQuestao();

        $selectedTemplateId = $request->old('template_avaliacao_id', $templates->first()->id ?? null);
        $oldInput = $request->old();
        if (! is_array($oldInput)) {
            $oldInput = [];
        }

        $questoesAdicionaisInput = $request->old('questoes_adicionais', []);
        if (! is_array($questoesAdicionaisInput)) {
            $questoesAdicionaisInput = [];
        }

        $evidenciasOptions = $evidencias->mapWithKeys(fn ($evidencia) => [
            $evidencia->id => ($evidencia->indicador && $evidencia->indicador->dimensao
                    ? $evidencia->indicador->dimensao->descricao . ' - '
                    : '') . ($evidencia->indicador->descricao ?? '') . ' | ' . $evidencia->descricao,
        ])->toArray();

        $escalasOptions = $escalas->pluck('descricao', 'id')->toArray();

        $questoesForm = QuestoesFormViewModel::make(
            $templates,
            $selectedTemplateId !== null ? (string) $selectedTemplateId : null,
            [],
            $questoesAdicionaisInput,
            $tiposQuestao,
            $evidenciasOptions,
            $evidencias->keyBy('id'),
            $escalasOptions,
            $escalas->keyBy('id'),
            [],
            false,
            $request->session()->get('errors'),
            $oldInput
        )->toArray();

        return view('avaliacoes.create', [
            'atividades'      => $atividades,
            'templates'       => $templates,
            'selectedTemplateId' => $selectedTemplateId,
            'questoesForm'    => $questoesForm,
        ]);
    }

    public function store(Request $request)
    {
        $dados = $this->validateAvaliacao($request);

        $template = TemplateAvaliacao::with([
            'questoes.indicador',
            'questoes.escala',
            'questoes.evidencia',
        ])
            ->findOrFail($dados['template_avaliacao_id']);

        $customizacoes = $this->validarQuestoesPersonalizadas($request, $template->questoes);
        [$questoesAdicionais, $questoesAdicionaisRemovidas] = $this->processaQuestoesAdicionais($request);

        $duplicadaQuery = Avaliacao::where('atividade_id', $dados['atividade_id']);

        if ($dados['inscricao_id'] !== null) {
            $duplicadaQuery->where('inscricao_id', $dados['inscricao_id']);
        } else {
            $duplicadaQuery->whereNull('inscricao_id');
        }

        $duplicada = $duplicadaQuery->exists();

        if ($duplicada) {
            return back()
                ->withInput()
                ->withErrors(['atividade_id' => 'Ja existe uma avaliacao para esta inscricao nesta atividade.']);
        }

        DB::transaction(function () use ($dados, $template, $customizacoes, $questoesAdicionais) {
            $avaliacao = Avaliacao::create($dados);

            $this->sincronizaQuestoesPersonalizadas(
                $avaliacao,
                $template->questoes,
                $customizacoes,
                true
            );

            $this->sincronizaQuestoesAdicionais($avaliacao, $questoesAdicionais);
        });

        return redirect()
            ->route('avaliacoes.index')
            ->with('success', 'Avaliação registrada com sucesso!');
    }

    public function show(Avaliacao $avaliacao)
    {
        $avaliacao->load([
            'inscricao.participante.user',
            'inscricao.evento',
            'atividade.evento',
            'templateAvaliacao',
            'respostas.avaliacaoQuestao',
            'avaliacaoQuestoes.indicador.dimensao',
            'avaliacaoQuestoes.evidencia',
            'avaliacaoQuestoes.escala',
        ]);

        return view('avaliacoes.show', [
            'avaliacao' => $avaliacao,
            'tiposQuestao' => $this->tiposQuestao(),
        ]);
    }

    public function edit(Request $request, Avaliacao $avaliacao)
    {
        $avaliacao->load([
            'templateAvaliacao',
            'avaliacaoQuestoes.indicador.dimensao',
            'avaliacaoQuestoes.evidencia',
            'avaliacaoQuestoes.escala',
            'inscricao.participante.user',
            'inscricao.evento',
            'atividade.evento',
        ]);

        $atividades = Atividade::with('evento')
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        $templates = TemplateAvaliacao::with(['questoes.escala', 'questoes.indicador.dimensao', 'questoes.evidencia.indicador'])
            ->orderBy('nome')
            ->get();

        $evidencias = Evidencia::with('indicador.dimensao')
            ->orderBy('descricao')
            ->get();

        $escalas = Escala::orderBy('descricao')->get();

        $personalizacoes = $avaliacao->avaliacaoQuestoes
            ->mapWithKeys(fn ($questao) => [
                $questao->questao_id ?? $questao->id => [
                    'texto'        => $questao->texto,
                    'tipo'         => $questao->tipo,
                    'evidencia_id' => $questao->evidencia_id,
                    'escala_id'    => $questao->escala_id,
                ],
            ])
            ->all();

        $questoesAdicionais = $avaliacao->avaliacaoQuestoes
            ->whereNull('questao_id')
            ->map(fn ($questao) => [
                'id'          => $questao->id,
                'texto'       => $questao->texto,
                'tipo'        => $questao->tipo,
                'evidencia_id'=> $questao->evidencia_id,
                'escala_id'   => $questao->escala_id,
                'ordem'       => $questao->ordem,
            ])
            ->values()
            ->all();

        $tiposQuestao = $this->tiposQuestao();

        $selectedTemplateId = $request->old('template_avaliacao_id', $avaliacao->template_avaliacao_id);
        $oldInput = $request->old();
        if (! is_array($oldInput)) {
            $oldInput = [];
        }

        $questoesAdicionaisInput = $request->old('questoes_adicionais', $questoesAdicionais);
        if (! is_array($questoesAdicionaisInput)) {
            $questoesAdicionaisInput = $questoesAdicionais;
        }

        $evidenciasOptions = $evidencias->mapWithKeys(fn ($evidencia) => [
            $evidencia->id => ($evidencia->indicador && $evidencia->indicador->dimensao
                ? $evidencia->indicador->dimensao->descricao . ' - '
                : '') . ($evidencia->indicador->descricao ?? '') . ' | ' . $evidencia->descricao,
        ])->toArray();

        $escalasOptions = $escalas->pluck('descricao', 'id')->toArray();

        $questoesForm = QuestoesFormViewModel::make(
            $templates,
            $selectedTemplateId !== null ? (string) $selectedTemplateId : null,
            $personalizacoes,
            $questoesAdicionaisInput,
            $tiposQuestao,
            $evidenciasOptions,
            $evidencias->keyBy('id'),
            $escalasOptions,
            $escalas->keyBy('id'),
            [],
            false,
            $request->session()->get('errors'),
            $oldInput
        )->toArray();

        return view('avaliacoes.edit', [
            'avaliacao'           => $avaliacao,
            'atividades'          => $atividades,
            'templates'           => $templates,
            'templateSelecionado' => $avaliacao->templateAvaliacao,
            'selectedTemplateId'  => $selectedTemplateId,
            'questoesForm'        => $questoesForm,
        ]);
    }

    public function update(Request $request, Avaliacao $avaliacao)
    {
        $dados = $this->validateAvaliacao($request, $avaliacao->id);

        $template = TemplateAvaliacao::with([
            'questoes.indicador',
            'questoes.escala',
            'questoes.evidencia',
        ])
            ->findOrFail($dados['template_avaliacao_id']);

        $customizacoes = $this->validarQuestoesPersonalizadas($request, $template->questoes);
        $respostas = $request->input('respostas');
        [$questoesAdicionais, $questoesAdicionaisRemovidas] = $this->processaQuestoesAdicionais($request, $avaliacao);

        $duplicadaQuery = Avaliacao::where('atividade_id', $dados['atividade_id'])
            ->where('id', '<>', $avaliacao->id);

        if ($dados['inscricao_id'] !== null) {
            $duplicadaQuery->where('inscricao_id', $dados['inscricao_id']);
        } else {
            $duplicadaQuery->whereNull('inscricao_id');
        }

        $duplicada = $duplicadaQuery->exists();

        if ($duplicada) {
            return back()
                ->withInput()
                ->withErrors(['atividade_id' => 'Ja existe outra avaliacao para esta inscricao nesta atividade.']);
        }

        DB::transaction(function () use ($avaliacao, $dados, $template, $customizacoes, $respostas, $questoesAdicionais, $questoesAdicionaisRemovidas) {
            $templateAlterado = $avaliacao->template_avaliacao_id !== $dados['template_avaliacao_id'];

            $avaliacao->update($dados);
            $avaliacao->refresh();

            $questoesSincronizadas = $this->sincronizaQuestoesPersonalizadas(
                $avaliacao,
                $template->questoes,
                $customizacoes,
                $templateAlterado
            );

            $this->sincronizaQuestoesAdicionais($avaliacao, $questoesAdicionais, $questoesAdicionaisRemovidas);

            if (is_array($respostas)) {
                $this->sincronizaRespostas($avaliacao, $respostas, $questoesSincronizadas);
            }
        });

        return redirect()
            ->route('avaliacoes.index')
            ->with('success', 'Avaliacao atualizada com sucesso!');
    }

    public function destroy(Avaliacao $avaliacao)
    {
        $avaliacao->delete();

        return redirect()
            ->route('avaliacoes.index')
            ->with('success', 'Avaliacao removida com sucesso!');
    }

    private function sincronizaRespostas(
        Avaliacao $avaliacao,
        array $respostas,
        ?Collection $questoesAtualizadas = null
    ): void {
        $avaliacao->respostas()->delete();

        $questoes = $questoesAtualizadas ?? $avaliacao->avaliacaoQuestoes()->get();
        $inscricaoParaResposta = $avaliacao->inscricao_id;

        foreach ($questoes as $questao) {
            $chaveResposta = $questao->questao_id ?? $questao->id;
            $valor = $respostas[$chaveResposta] ?? null;

            if ($valor === null || $valor === '') {
                continue;
            }

            $avaliacao->respostas()->create([
                'avaliacao_questao_id' => $questao->id,
                'inscricao_id'        => $inscricaoParaResposta,
                'resposta'             => is_array($valor) ? json_encode($valor) : $valor,
            ]);
        }
    }

    /**
     * @return array{0: \Illuminate\Support\Collection, 1: array<int>}
     */
    private function processaQuestoesAdicionais(Request $request, ?Avaliacao $avaliacao = null): array
    {
        $questoesInput = collect($request->input('questoes_adicionais', []));

        if ($questoesInput->isEmpty()) {
            return [collect(), []];
        }

        $questoesAtivas = $questoesInput
            ->filter(fn ($questao) => empty($questao['_delete']))
            ->values();

        $idsRemovidos = $questoesInput
            ->filter(fn ($questao) => ! empty($questao['_delete']) && ! empty($questao['id']))
            ->pluck('id')
            ->all();

        $tipos = array_keys($this->tiposQuestao());

        $questoesValidadas = $questoesAtivas->map(function ($questao, int $index) use ($avaliacao, $tipos) {
            $validator = Validator::make(
                $questao,
                [
                    'id'           => $avaliacao
                        ? ['nullable', 'integer', Rule::exists('avaliacao_questoes', 'id')
                            ->where('avaliacao_id', $avaliacao->id)
                            ->whereNull('questao_id')]
                        : ['prohibited'],
                    'texto'        => ['required', 'string', 'max:1000'],
                    'tipo'         => ['required', 'string', Rule::in($tipos)],
                    'evidencia_id' => ['nullable', 'integer', Rule::exists('evidencias', 'id')],
                    'escala_id'    => ['nullable', 'integer', Rule::exists('escalas', 'id')],
                    'ordem'        => ['nullable', 'integer', 'min:1', 'max:999'],
                ],
                [],
                [
                    'id'           => "questoes_adicionais.$index.id",
                    'texto'        => "questoes_adicionais.$index.texto",
                    'tipo'         => "questoes_adicionais.$index.tipo",
                    'evidencia_id' => "questoes_adicionais.$index.evidencia_id",
                    'escala_id'    => "questoes_adicionais.$index.escala_id",
                    'ordem'        => "questoes_adicionais.$index.ordem",
                ]
            );

            $validator->after(function ($validator) use ($questao, $index) {
                if (($questao['tipo'] ?? null) === 'escala' && empty($questao['escala_id'])) {
                    $validator->errors()->add("questoes_adicionais.$index.escala_id", 'Selecione uma escala quando o tipo da questao for Escala.');
                }
            });

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $dados = $validator->validated();
            $dados['texto'] = trim($dados['texto']);

            if (array_key_exists('ordem', $dados) && $dados['ordem'] !== null) {
                $dados['ordem'] = (int) $dados['ordem'];
            }

            if ($avaliacao === null) {
                unset($dados['id']);
            }

            if (($dados['tipo'] ?? null) !== 'escala') {
                $dados['escala_id'] = null;
            }

            $dados['ordem'] = $dados['ordem'] ?? null;

            return $dados;
        });

        return [$questoesValidadas, $idsRemovidos];
    }

    private function sincronizaQuestoesAdicionais(
        Avaliacao $avaliacao,
        Collection $questoes,
        array $removidas = []
    ): void {
        if (! empty($removidas)) {
            $avaliacao->avaliacaoQuestoes()
                ->whereNull('questao_id')
                ->whereIn('id', $removidas)
                ->delete();
        }

        $existentes = $avaliacao->avaliacaoQuestoes()
            ->whereNull('questao_id')
            ->get()
            ->keyBy('id');

        if ($questoes->isEmpty()) {
            return;
        }

        $evidenciaIds = $questoes->pluck('evidencia_id')->filter()->unique();
        $evidencias = Evidencia::select('id', 'indicador_id')
            ->whereIn('id', $evidenciaIds)
            ->get()
            ->keyBy('id');

        $idsMantidos = [];

        foreach ($questoes as $dados) {
            $questaoId = $dados['id'] ?? null;
            unset($dados['id']);

            $indicadorId = null;
            if (! empty($dados['evidencia_id']) && $evidencias->has($dados['evidencia_id'])) {
                $indicadorId = $evidencias[$dados['evidencia_id']]->indicador_id;
            }

            $payload = [
                'questao_id'   => null,
                'indicador_id' => $indicadorId,
                'escala_id'    => $dados['escala_id'] ?? null,
                'evidencia_id' => $dados['evidencia_id'] ?? null,
                'texto'        => $dados['texto'],
                'tipo'         => $dados['tipo'],
                'ordem'        => $dados['ordem'] ?? null,
                'fixa'         => false,
            ];

            if ($questaoId && $existentes->has($questaoId)) {
                $existentes[$questaoId]->update($payload);
                $idsMantidos[] = $questaoId;
            } else {
                $novo = $avaliacao->avaliacaoQuestoes()->create($payload);
                $idsMantidos[] = $novo->id;
            }
        }

        if (! empty($idsMantidos)) {
            $avaliacao->avaliacaoQuestoes()
                ->whereNull('questao_id')
                ->whereNotIn('id', $idsMantidos)
                ->delete();
        }
    }

    private function validateAvaliacao(Request $request, ?int $avaliacaoId = null): array
    {
        $dados = $request->validate([
            'inscricao_id'          => ['nullable', Rule::exists('inscricaos', 'id')],
            'atividade_id'          => ['required', Rule::exists('atividades', 'id')],
            'template_avaliacao_id' => ['required', Rule::exists('template_avaliacaos', 'id')],
            'respostas'             => ['nullable', 'array'],
        ]);

        return [
            'inscricao_id'          => $dados['inscricao_id'] ?? null,
            'atividade_id'          => $dados['atividade_id'],
            'template_avaliacao_id' => $dados['template_avaliacao_id'],
        ];
    }

    /**
     * @param \Illuminate\Support\Collection<int,\App\Models\Questao> $questoesTemplate
     * @return array<int, array{texto?: string|null}>
     */
    private function validarQuestoesPersonalizadas(Request $request, Collection $questoesTemplate): array
    {
        $rules = [];

        $tipos = array_keys($this->tiposQuestao());

        foreach ($questoesTemplate as $questao) {
            if (! $questao->fixa) {
                $rules["questoes.{$questao->id}.texto"] = ['nullable', 'string', 'max:1000'];
                $rules["questoes.{$questao->id}.tipo"] = ['nullable', 'string', Rule::in($tipos)];
                $rules["questoes.{$questao->id}.evidencia_id"] = ['nullable', 'integer', Rule::exists('evidencias', 'id')];
                $rules["questoes.{$questao->id}.escala_id"] = ['nullable', 'integer', Rule::exists('escalas', 'id')];
            }
        }

        if (empty($rules)) {
            return [];
        }

        $dados = $request->validate($rules);

        $resultado = [];

        foreach ($dados['questoes'] ?? [] as $questaoId => $config) {
            $resultado[$questaoId] = [
                'texto'        => $config['texto'] ?? null,
                'tipo'         => isset($config['tipo']) && $config['tipo'] !== '' ? $config['tipo'] : null,
                'evidencia_id' => array_key_exists('evidencia_id', $config) && $config['evidencia_id'] !== ''
                    ? (int) $config['evidencia_id']
                    : null,
                'escala_id'    => array_key_exists('escala_id', $config) && $config['escala_id'] !== ''
                    ? (int) $config['escala_id']
                    : null,
            ];
        }

        return $resultado;
    }

    /**
     * @param \Illuminate\Support\Collection<int,\App\Models\Questao> $questoesTemplate
     * @return \Illuminate\Support\Collection<int,\App\Models\AvaliacaoQuestao>
     */
    private function sincronizaQuestoesPersonalizadas(
        Avaliacao $avaliacao,
        Collection $questoesTemplate,
        array $customizacoes,
        bool $recriar = false
    ): Collection {
        if ($recriar) {
            $avaliacao->avaliacaoQuestoes()->delete();
        }

        $existentes = $avaliacao->avaliacaoQuestoes()
            ->get()
            ->keyBy(fn ($questao) => $questao->questao_id ?? $questao->id);

        $sincronizadas = collect();

        $customizacoesCollection = collect($customizacoes);
        $evidenciaIds = $customizacoesCollection
            ->pluck('evidencia_id')
            ->filter()
            ->unique()
            ->all();

        $evidencias = Evidencia::with('indicador')
            ->whereIn('id', $evidenciaIds)
            ->get()
            ->keyBy('id');

        $tiposValidos = array_keys($this->tiposQuestao());

        foreach ($questoesTemplate as $questao) {
            $personalizacao = $customizacoesCollection->get($questao->id, []);

            $textoOriginal = $questao->texto;
            $textoPersonalizado = is_string($personalizacao['texto'] ?? null)
                ? trim($personalizacao['texto'])
                : '';
            $texto = $questao->fixa || $textoPersonalizado === ''
                ? $textoOriginal
                : $textoPersonalizado;

            $tipoPersonalizado = $personalizacao['tipo'] ?? null;
            if (! $questao->fixa && $tipoPersonalizado && in_array($tipoPersonalizado, $tiposValidos, true)) {
                $tipo = $tipoPersonalizado;
            } else {
                $tipo = $questao->tipo;
            }

            $evidenciaId = $questao->evidencia_id;
            $indicadorId = $questao->indicador_id;
            $escalaId = $questao->escala_id;

            if (! $questao->fixa) {
                $evidenciaId = $personalizacao['evidencia_id'] ?? $evidenciaId;
                $evidenciaId = $evidenciaId ? (int) $evidenciaId : null;
                $escalaId = $personalizacao['escala_id'] ?? $escalaId;
                $escalaId = $escalaId ? (int) $escalaId : null;

                if ($evidenciaId && $evidencias->has($evidenciaId)) {
                    $indicadorId = $evidencias[$evidenciaId]->indicador_id;
                } else {
                    $indicadorId = $questao->indicador_id;
                }
            }

            if ($tipo !== 'escala') {
                $escalaId = $questao->fixa ? $questao->escala_id : null;
            }

            if ($tipo === 'escala' && ! $escalaId) {
                throw ValidationException::withMessages([
                    "questoes.{$questao->id}.escala_id" => 'Selecione uma escala quando o tipo da questao for Escala.',
                ]);
            }

            $payload = [
                'questao_id'   => $questao->id,
                'indicador_id' => $indicadorId,
                'escala_id'    => $escalaId,
                'evidencia_id' => $questao->fixa ? $questao->evidencia_id : $evidenciaId,
                'texto'        => $texto,
                'tipo'         => $tipo,
                'ordem'        => $questao->ordem,
                'fixa'         => (bool) $questao->fixa,
            ];

            if ($recriar || ! $existentes->has($questao->id)) {
                $avaliacaoQuestao = $avaliacao->avaliacaoQuestoes()->create($payload);
            } else {
                $avaliacaoQuestao = $existentes[$questao->id];
                $avaliacaoQuestao->fill($payload);
                $avaliacaoQuestao->save();
            }

            $sincronizadas->push($avaliacaoQuestao);
        }

        if (! $recriar) {
            $manterIds = $sincronizadas->pluck('id')->all();
            $avaliacao->avaliacaoQuestoes()
                ->whereNotIn('id', $manterIds)
                ->delete();
        }

        return $sincronizadas;
    }

    public function tiposQuestao(): array
    {
        return [
            'texto'  => 'Texto aberto',
            'escala' => 'Escala',
            'numero' => 'Numero',
            'boolean'=> 'Sim/Nao',
        ];
    }

    public function formularioAvaliacao(Request $request, Avaliacao $avaliacao)
    {
        $atividade = Atividade::find($avaliacao->atividade_id);

        $avaliacao->load([
            'inscricao.participante.user',
            'inscricao.evento',
            'atividade.evento',
            'templateAvaliacao',
            'avaliacaoQuestoes.indicador.dimensao',
            'avaliacaoQuestoes.evidencia',
            'avaliacaoQuestoes.escala',
            'respostas.avaliacaoQuestao',
        ]);

        $token = $request->query('token', $request->input('token'));
        $presencaRespondente = $this->resolverPresencaPorToken($token, $avaliacao);
        $inscricaoRespondente = $presencaRespondente?->inscricao;
        $formBloqueado = $presencaRespondente?->avaliacao_respondida ?? false;
        $respostasExistentes = collect();

        return view('avaliacoes._form', [
            'avaliacao' => $avaliacao,
            'atividade' => $atividade,
            'tiposQuestao' => $this->tiposQuestao(),
            'inscricaoRespondente' => $inscricaoRespondente,
            'token' => $token,
            'respostasExistentes' => $respostasExistentes,
            'jaRespondeu' => $formBloqueado,
        ]);
    }

    public function responderFormulario(Request $request, Avaliacao $avaliacao)
    {
        $avaliacao->load(['avaliacaoQuestoes.escala', 'atividade']);

        $token = $request->input('token', $request->query('token'));
        $presenca = $this->resolverPresencaPorToken($token, $avaliacao);

        if (! $presenca) {
            return redirect()
                ->route('avaliacao.formulario', ['avaliacao' => $avaliacao->id, 'token' => $token])
                ->withErrors(['token' => 'Nao encontramos sua inscricao para esta avaliacao. Confirme a presenca novamente.']);
        }

        if ($presenca->avaliacao_respondida) {
            return redirect()
                ->route('avaliacao.formulario', ['avaliacao' => $avaliacao->id, 'token' => $token])
                ->withErrors(['avaliacao' => 'Voce ja respondeu este formulario.']);
        }

        $rules = [];
        foreach ($avaliacao->avaliacaoQuestoes as $questao) {
            $rules["respostas.{$questao->id}"] = $this->regraRespostaParaQuestao($questao);
        }

        $dados = $request->validate($rules);
        $respostas = $dados['respostas'] ?? [];

        DB::transaction(function () use ($avaliacao, $presenca, $respostas) {
            $submissao = SubmissaoAvaliacao::create([
                'codigo' => (string) Str::ulid(),
                'atividade_id' => $avaliacao->atividade_id,
                'avaliacao_id' => $avaliacao->id,
            ]);

            foreach ($avaliacao->avaliacaoQuestoes as $questao) {
                $valor = $respostas[$questao->id] ?? null;

                if ($valor === null || $valor === '') {
                    continue;
                }

                RespostaAvaliacao::create([
                    'avaliacao_id' => $avaliacao->id,
                    'avaliacao_questao_id' => $questao->id,
                    'submissao_avaliacao_id' => $submissao->id,
                    'resposta' => $valor,
                ]);
            }

            $presenca->avaliacao_respondida = true;
            $presenca->save();
        });

        return redirect()
            ->route('presenca.confirmar', $presenca->atividade_id)
            ->with([
                'success' => 'Avaliação registrada com sucesso!',
                'avaliacao_token' => null,
                'avaliacao_disponivel' => false,
            ]);
    }

    private function regraRespostaParaQuestao(AvaliacaoQuestao $questao): array
    {
        if ($questao->tipo === 'escala') {
            $valores = $questao->escala?->valores ?? [];
            return empty($valores) ? ['nullable', 'string'] : ['nullable', Rule::in($valores)];
        }

        return match ($questao->tipo) {
            'numero'  => ['nullable', 'numeric'],
            'boolean' => ['nullable', Rule::in(['0', '1'])],
            default   => ['nullable', 'string', 'max:2000'],
        };
    }

    private function resolverPresencaPorToken(?string $token, Avaliacao $avaliacao): ?Presenca
    {
        if (! $token) {
            return null;
        }

        try {
            $presencaId = decrypt($token);
        } catch (Throwable $exception) {
            return null;
        }

        $presenca = Presenca::with(['inscricao.participante.user', 'inscricao.evento'])->find($presencaId);
        if (! $presenca) {
            return null;
        }

        if ($avaliacao->atividade_id && $presenca->atividade_id !== $avaliacao->atividade_id) {
            return null;
        }

        return $presenca;
    }
}
