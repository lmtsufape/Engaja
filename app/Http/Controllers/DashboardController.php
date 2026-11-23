<?php

namespace App\Http\Controllers;

use App\Exports\MatrizPresencaExport;
use App\Models\Atividade;
use App\Models\Avaliacao;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Municipio;
use App\Models\RespostaAvaliacao;
use App\Models\SubmissaoAvaliacao;
use App\Models\TemplateAvaliacao;
use App\Services\AvaliacaoRespostasDashboardService;
use App\Services\LimeSurvey\LimeSurveyClient;
use App\Services\LimeSurvey\LimeSurveyDashboardService;
use App\Word\MatrizPresencaWordBuilder;
use App\Word\WordDocument;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;

class DashboardController extends Controller
{
    use AuthorizesRequests;

    public function home()
    {
        $resumo = [
            'avaliacoesRespondidas' => SubmissaoAvaliacao::count(),
            'respostas' => RespostaAvaliacao::count(),
            'atividades' => Atividade::count(),
            'inscricoes' => Inscricao::count(),
            'ultimaAtualizacao' => optional(RespostaAvaliacao::latest('updated_at')->first())->updated_at,
        ];

        $templatesDisponiveis = TemplateAvaliacao::orderBy('nome')->limit(4)->get();
        $eventosRecentes = Evento::orderByDesc('created_at')->limit(4)->get();

        return view('dashboards.home', compact('resumo', 'templatesDisponiveis', 'eventosRecentes'));
    }

    public function bi()
    {
        return view('dashboards.bi');
    }

    public function leituraMundo()
    {
        $surveys = collect();
        $erro = null;

        try {
            $client = app(LimeSurveyClient::class);
            $raw = $client->listSurveys();

            $surveys = collect($raw)
                ->filter(fn ($item) => is_array($item) && ! empty($item['sid']))
                ->map(function (array $item) {
                    $sid = (int) ($item['sid'] ?? 0);
                    $titulo = trim((string) ($item['surveyls_title'] ?? "Survey {$sid}"));
                    $ativo = strtoupper((string) ($item['active'] ?? 'N')) === 'Y';

                    $start = $this->formatSurveyDate($item['startdate'] ?? null);
                    $expires = $this->formatSurveyDate($item['expires'] ?? null);

                    $cachedAt = Cache::get("limesurvey:{$sid}:cached_at");

                    return [
                        'sid' => $sid,
                        'titulo' => $titulo !== '' ? $titulo : "Survey {$sid}",
                        'ativo' => $ativo,
                        'startdate' => $start,
                        'expires' => $expires,
                        'cached_at' => $cachedAt ? $cachedAt->format('d/m/Y H:i') : null,
                    ];
                })
                ->sortBy('titulo', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        } catch (\Throwable $exception) {
            $erro = $exception->getMessage();
        }

        return view('dashboards.leitura-mundo', compact('surveys', 'erro'));
    }

    public function index(Request $request)
    {
        $eventoId = $request->integer('evento_id');
        $de = $request->date('de');
        $ate = $request->date('ate');
        $q = trim((string) $request->get('q', ''));

        $sort = $request->get('sort', 'dia');
        $dir = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $sortable = [
            'dia' => 'atividades.dia',
            'hora' => 'atividades.hora_inicio',
            'momento' => 'atividades.descricao',
            'acao' => 'eventos.nome',
            'municipio' => 'municipios.nome',
            'inscritos' => 'inscritos_count',
            'presentes' => 'presentes_count',
            'ausentes' => 'ausentes_count',
        ];
        $orderByCol = $sortable[$sort] ?? 'atividades.dia';

        $query = Atividade::query()
            ->select([
                'atividades.id',
                'atividades.evento_id',
                'atividades.municipio_id',
                'atividades.descricao',
                'atividades.dia',
                'atividades.hora_inicio',
                'eventos.nome as evento_nome',
            ])
            ->leftJoin('eventos', 'eventos.id', '=', 'atividades.evento_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'atividades.municipio_id')
            ->with([
                'evento:id,nome',
                'municipio.estado:id,nome,sigla',
            ])
            ->withCount([
                'presencas as presentes_count' => fn ($q) => $q->where('status', 'presente'),
            ])
            ->selectRaw('(
                SELECT COUNT(*)
                FROM inscricaos
                WHERE inscricaos.atividade_id = atividades.id
                  AND inscricaos.deleted_at IS NULL
            ) as inscritos_count')
            ->selectRaw('(
                SELECT COUNT(*)
                FROM inscricaos
                WHERE inscricaos.atividade_id = atividades.id
                  AND inscricaos.deleted_at IS NULL
            ) - (
                SELECT COUNT(*)
                FROM presencas
                WHERE presencas.atividade_id = atividades.id
                  AND presencas.status = \'presente\'
                  AND presencas.deleted_at IS NULL
            ) as ausentes_count');

        $query->whereNull('atividades.deleted_at')
            ->whereNotNull('atividades.evento_id')
            ->whereNull('eventos.deleted_at');

        $query->when($eventoId, fn ($q) => $q->where('atividades.evento_id', $eventoId));
        $query->when($de && $ate, fn ($q) => $q->whereBetween('atividades.dia', [$de, $ate]));
        $query->when($de && ! $ate, fn ($q) => $q->where('atividades.dia', '>=', $de));
        $query->when(! $de && $ate, fn ($q) => $q->where('atividades.dia', '<=', $ate));

        $query->when($q !== '', function ($q2) use ($q) {
            $like = '%'.$q.'%';
            $q2->where(function ($w) use ($like) {
                $w->where('atividades.descricao', 'like', $like)
                    ->orWhere('eventos.nome', 'like', $like);
            });
        });

        $query->orderBy($orderByCol, $dir)->orderBy('atividades.id', 'desc');

        $atividades = $query->paginate($perPage)->appends($request->query());

        $eventos = Evento::query()->orderBy('nome')->pluck('nome', 'id');
        $municipioIds = Atividade::query()
            ->whereNotNull('municipio_id')
            ->distinct()
            ->pluck('municipio_id');
        $municipios = Municipio::query()
            ->with('estado:id,sigla')
            ->whereIn('id', $municipioIds)
            ->orderBy('nome')
            ->get();
        $momentos = Atividade::query()
            ->select('descricao')
            ->whereNotNull('descricao')
            ->where('descricao', '!=', '')
            ->distinct()
            ->orderBy('descricao')
            ->pluck('descricao');

        return view('dashboard', compact('atividades', 'eventos', 'municipios', 'momentos'));
    }

    public function presencasDetalhes(Atividade $atividade): View
    {
        if ($atividade->evento) {
            $this->authorize('update', $atividade->evento);
        }

        $presentes = $atividade->presencas()
            ->where('status', 'presente')
            ->with('inscricao.participante.user')
            ->get();

        $inscricoes = $atividade->inscricoes()
            ->whereNull('deleted_at')
            ->with('participante.user')
            ->get()
            ->sortBy(fn ($i) => strtolower($i->participante?->user?->name ?? ''))
            ->values();

        $presentesIds = $presentes->pluck('inscricao_id')->filter()->unique();
        $ausentes = $inscricoes->filter(fn ($i) => ! $presentesIds->contains($i->id))->values();
        $inscritosCount = $inscricoes->count();
        $presentesCount = $presentesIds->count();
        $ausentesCount = $ausentes->count();

        return view('dashboards._presencas_detalhes', compact(
            'presentes', 'ausentes', 'atividade',
            'inscritosCount', 'presentesCount', 'ausentesCount',
            'inscricoes', 'presentesIds'
        ));
    }

    public function avaliacoes(Request $request)
    {
        $templates = TemplateAvaliacao::orderBy('nome')->get(['id', 'nome']);
        $eventos = Evento::orderBy('nome')->get(['id', 'nome']);
        $atividades = Atividade::with('evento')
            ->orderByDesc('dia')
            ->orderByDesc('hora_inicio')
            ->get(['id', 'evento_id', 'descricao', 'dia', 'hora_inicio']);
        $avaliacoesUniversais = Avaliacao::query()
            ->with('templateAvaliacao:id,nome')
            ->whereNull('atividade_id')
            ->orderBy('descricao_universal')
            ->orderByDesc('created_at')
            ->get(['id', 'template_avaliacao_id', 'descricao_universal', 'created_at']);

        $cachedAt = null;
        if ($request->query('fonte') === 'limesurvey') {
            $surveyId = (int) ($request->integer('survey_id') ?: config('services.limesurvey.survey_id'));
            if ($surveyId > 0) {
                $ts = Cache::get("limesurvey:{$surveyId}:cached_at");
                $cachedAt = $ts ? $ts->format('d/m/Y H:i') : null;
            }
        }

        return view('dashboards.avaliacoes', compact('templates', 'eventos', 'atividades', 'avaliacoesUniversais', 'cachedAt'));
    }

    public function avaliacoesData(Request $request, AvaliacaoRespostasDashboardService $avaliacaoRespostas)
    {
        $this->authorizeAvaliacoesDashboardRequest($request);

        if ($request->query('fonte') === 'limesurvey') {
            return $this->avaliacoesDataLimeSurvey($request);
        }

        $this->validateAvaliacoesDashboardFilters($request);

        return response()->json($avaliacaoRespostas->buildDashboardPayload($request));
    }

    public function avaliacoesPdf(Request $request, AvaliacaoRespostasDashboardService $avaliacaoRespostas)
    {
        $this->validateAvaliacoesDashboardFilters($request);
        $this->authorizeAvaliacoesDashboardRequest($request);

        // Para o PDF, queremos todas as questões, sem paginação
        $request->merge(['per_page' => 1000]);

        $payload = $avaliacaoRespostas->buildDashboardPayload($request);

        $tipo = $request->query('tipo', 'momento');
        $eventoId = $request->integer('evento_id');
        $atividadeId = $request->integer('atividade_id');
        $avaliacaoId = $request->integer('avaliacao_id');

        $atividade = $atividadeId ? Atividade::with(['evento', 'municipios.estado'])->find($atividadeId) : null;
        $evento = $eventoId ? Evento::find($eventoId) : null;
        $avaliacaoUniversal = ($tipo === 'universal' && $avaliacaoId) ? Avaliacao::with('templateAvaliacao')->find($avaliacaoId) : null;

        $fileName = 'avaliacoes-'.now()->format('Ymd_His').'.pdf';

        return Pdf::view('dashboards.avaliacoes_pdf', [
            'totais' => $payload['totais'],
            'perguntas' => $payload['perguntas'],
            'atividade' => $atividade,
            'evento' => $evento,
            'avaliacaoUniversal' => $avaliacaoUniversal,
            'tipo' => $tipo,
            'filtros' => [
                'de' => $request->get('de'),
                'ate' => $request->get('ate'),
            ],
            'geradoEm' => now(),
        ])
            ->format('a4')
            ->withAlfaEjaBrand()
            ->download($fileName);
    }

    /**
     * Garante que filtros por momento ou ação pedagógica só devolvem dados se o utilizador puder editar esse evento.
     */
    private function validateAvaliacoesDashboardFilters(Request $request): array
    {
        $tipo = (string) $request->query('tipo', 'momento');

        $validator = Validator::make($request->all(), [
            'tipo' => ['required', 'string', Rule::in(['momento', 'transcricao', 'universal'])],
            'evento_id' => [
                Rule::requiredIf(fn () => in_array($tipo, ['momento', 'transcricao'], true)),
                'nullable',
                'integer',
                'exists:eventos,id',
            ],
            'atividade_id' => ['nullable', 'integer', 'exists:atividades,id'],
            'avaliacao_id' => [
                Rule::requiredIf(fn () => $tipo === 'universal'),
                'nullable',
                'integer',
                'exists:avaliacaos,id',
            ],
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $eventoId = $request->integer('evento_id');
            $atividadeId = $request->integer('atividade_id');

            if ($eventoId && $atividadeId) {
                $atividadePertenceAoEvento = Atividade::query()
                    ->whereKey($atividadeId)
                    ->where('evento_id', $eventoId)
                    ->exists();

                if (! $atividadePertenceAoEvento) {
                    $validator->errors()->add(
                        'atividade_id',
                        'O momento selecionado não pertence à ação pedagógica escolhida.'
                    );
                }
            }
        });

        return $validator->validate();
    }

    private function authorizeAvaliacoesDashboardRequest(Request $request): void
    {
        $atividadeId = $request->integer('atividade_id');
        if ($atividadeId) {
            $atividade = Atividade::query()->with('evento')->find($atividadeId);
            if ($atividade?->evento) {
                $this->authorize('update', $atividade->evento);
            }

            return;
        }

        $eventoId = $request->integer('evento_id');
        if ($eventoId) {
            $evento = Evento::query()->find($eventoId);
            if ($evento) {
                $this->authorize('update', $evento);
            }
        }
    }

    private function avaliacoesDataLimeSurvey(Request $request)
    {
        try {
            $surveyId = (int) ($request->integer('survey_id') ?: config('services.limesurvey.survey_id'));

            if ($request->query('debug_lime') === 'export_responses') {
                $client = app(LimeSurveyClient::class);

                return response()->json($client->exportResponses($surveyId));
            }

            if (! Cache::has("limesurvey:{$surveyId}:questions") || ! Cache::has("limesurvey:{$surveyId}:responses")) {
                return response()->json([
                    'sem_dados' => true,
                    'mensagem' => 'Este survey ainda não possui dados importados. Execute o importador de dados ou aguarde a atualização automática diária.',
                ]);
            }

            $service = app(LimeSurveyDashboardService::class);
            $payload = $service->buildPayload($request);

            if ($request->boolean('debug_lime')) {
                return response()->json([
                    'debug' => true,
                    'payload' => $payload,
                ]);
            }

            return response()->json($payload);
        } catch (\Throwable $exception) {
            return response()->json([
                'totais' => [
                    'submissoes' => 0,
                    'atividades' => 0,
                    'eventos' => 0,
                    'respostas' => 0,
                    'questoes' => 0,
                    'ultima' => null,
                ],
                'perguntas' => [],
                'recentes' => [],
                'erro' => $exception->getMessage(),
            ], 422);
        }
    }

    public function limesurveyListQuestions(Request $request)
    {
        try {
            $surveyId = (int) ($request->integer('survey_id') ?: config('services.limesurvey.survey_id'));
            $client = app(LimeSurveyClient::class);

            return response()->json($client->listQuestions($surveyId));
        } catch (\Throwable $exception) {
            return response()->json([
                'erro' => $exception->getMessage(),
            ], 422);
        }
    }

    public function limesurveyListParticipants(Request $request)
    {
        try {
            $surveyId = (int) ($request->integer('survey_id') ?: config('services.limesurvey.survey_id'));
            $start = max(0, (int) $request->integer('start', 0));
            $limit = max(1, min(10000, (int) $request->integer('limit', 1000)));
            $unused = $request->boolean('unused', false);

            $client = app(LimeSurveyClient::class);

            return response()->json($client->listParticipants($surveyId, $start, $limit, $unused));
        } catch (\Throwable $exception) {
            return response()->json([
                'erro' => $exception->getMessage(),
            ], 422);
        }
    }

    private function formatSurveyDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return null;
        }
    }

    public function export(Request $request)
    {
        /*
         * Relatórios extensos hidratam muitos models com eager loading profundo
         * (presencas.inscricao.participante.user e inscricoes.participante.user).
         * Eleva o limite de memória da requisição e impõe um teto de atividades
         * para evitar estouro de memória e timeout do Browsershot/Chromium.
         */
        ini_set('memory_limit', config('dashboard.pdf.memory_limit'));
        $maxAtividades = (int) config('dashboard.pdf.max_atividades');

        $pdfEventoId = $request->integer('pdf_evento_id');
        $eventoId = $pdfEventoId ?? $request->integer('evento_id');
        $municipioId = $request->integer('pdf_municipio_id');
        $momento = trim((string) $request->get('pdf_momento', ''));
        $de = $request->date('pdf_de') ?? $request->date('de');
        $ate = $request->date('pdf_ate') ?? $request->date('ate');
        $q = trim((string) $request->get('q', ''));

        $sort = $request->get('sort', 'dia');
        $dir = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'dia' => 'atividades.dia',
            'hora' => 'atividades.hora_inicio',
            'momento' => 'atividades.descricao',
            'acao' => 'eventos.nome',
            'municipio' => 'municipios.nome',
            'inscritos' => 'inscritos_count',
            'presentes' => 'presentes_count',
            'ausentes' => 'ausentes_count',
        ];
        $orderByCol = $sortable[$sort] ?? 'atividades.dia';

        // mesma query do index, mas sem paginate() e com eager até user
        $atividadesQuery = Atividade::query()
            ->select([
                'atividades.id',
                'atividades.evento_id',
                'atividades.municipio_id',
                'atividades.descricao',
                'atividades.dia',
                'atividades.hora_inicio',
                'eventos.nome as evento_nome',
            ])
            ->leftJoin('eventos', 'eventos.id', '=', 'atividades.evento_id')
            ->leftJoin('municipios', 'municipios.id', '=', 'atividades.municipio_id')
            ->with([
                'evento:id,nome',
                'municipio.estado:id,nome,sigla',
            ])
            ->with([
                'presencas' => fn ($q) => $q
                    ->where('status', 'presente')
                    ->with('inscricao.participante.user'),
            ])
            ->with([
                'inscricoes' => fn ($q) => $q
                    ->whereNull('deleted_at')
                    ->with('participante.user'),
            ])
            ->withCount([
                'presencas as presentes_count' => fn ($q) => $q->where('status', 'presente'),
            ])
            ->selectRaw('(
                SELECT COUNT(*)
                FROM inscricaos
                WHERE inscricaos.atividade_id = atividades.id
                  AND inscricaos.deleted_at IS NULL
            ) as inscritos_count')
            ->selectRaw('(
                SELECT COUNT(*)
                FROM inscricaos
                WHERE inscricaos.atividade_id = atividades.id
                  AND inscricaos.deleted_at IS NULL
            ) - (
                SELECT COUNT(*)
                FROM presencas
                WHERE presencas.atividade_id = atividades.id
                  AND presencas.status = \'presente\'
                  AND presencas.deleted_at IS NULL
            ) as ausentes_count')
            ->whereNull('atividades.deleted_at')
            ->whereNotNull('atividades.evento_id')
            ->whereNull('eventos.deleted_at')
            ->when($eventoId, fn ($q) => $q->where('atividades.evento_id', $eventoId))
            ->when($municipioId, fn ($q) => $q->where('atividades.municipio_id', $municipioId))
            ->when($momento !== '', fn ($q) => $q->where('atividades.descricao', $momento))
            ->when($de && $ate, fn ($q) => $q->whereBetween('atividades.dia', [$de, $ate]))
            ->when($de && ! $ate, fn ($q) => $q->where('atividades.dia', '>=', $de))
            ->when(! $de && $ate, fn ($q) => $q->where('atividades.dia', '<=', $ate))
            ->when($q !== '', function ($q2) use ($q) {
                $like = '%'.$q.'%';
                $q2->where(function ($w) use ($like) {
                    $w->where('atividades.descricao', 'like', $like)
                        ->orWhere('eventos.nome', 'like', $like);
                });
            });

        // Conta o universo filtrado antes de aplicar o teto, para sinalizar truncamento na view.
        $totalAtividades = (clone $atividadesQuery)->count('atividades.id');

        $atividades = $atividadesQuery
            ->orderBy($orderByCol, $dir)
            ->orderBy('atividades.id', 'desc')
            ->limit($maxAtividades)
            ->get();

        $truncado = $totalAtividades > $maxAtividades;

        $eventoSelecionado = $eventoId ? Evento::find($eventoId) : null;
        $municipioSelecionado = $municipioId
            ? Municipio::with('estado:id,sigla')->find($municipioId)
            : null;
        $periodo = null;
        if ($de && $ate) {
            $periodo = $de->format('d/m/Y').' - '.$ate->format('d/m/Y');
        } elseif ($de) {
            $periodo = 'A partir de '.$de->format('d/m/Y');
        } elseif ($ate) {
            $periodo = 'Até '.$ate->format('d/m/Y');
        }

        $filtroResumo = array_filter([
            'Ação pedagógica' => $eventoSelecionado?->nome,
            'Município' => $municipioSelecionado?->nome_com_estado,
            'Momento' => $momento ?: null,
            'Período' => $periodo,
        ]);

        if ($request->get('formato') === 'docx') {
            $doc = new WordDocument('landscape');
            $doc->addTitle('Relatório de Presenças');
            $doc->addFiltersSummary(array_map(
                fn ($chave, $valor) => $chave.': '.$valor,
                array_keys($filtroResumo),
                array_values($filtroResumo),
            ));

            if ($truncado) {
                $doc->addParagraph(
                    "Resultado parcial: exibindo {$maxAtividades} de {$totalAtividades} momentos.",
                    ['italic' => true, 'color' => '856404']
                );
            }

            $rows = $atividades->map(fn ($a) => [
                $a->dia ? Carbon::parse($a->dia)->format('d/m/Y') : '—',
                substr((string) $a->hora_inicio, 0, 5) ?: '—',
                $a->descricao ?? '—',
                $a->evento_nome ?? '—',
                $a->municipio?->nome_com_estado ?? '—',
                (int) $a->inscritos_count,
                (int) $a->presentes_count,
                (int) $a->ausentes_count,
            ])->all();

            $doc->addTable(
                ['Data', 'Hora', 'Momento', 'Ação', 'Município', 'Inscritos', 'Presentes', 'Ausentes'],
                $rows
            );

            return $doc->download('dashboard-presencas-'.now()->format('Ymd_His').'.docx');
        }

        return Pdf::view('dashboard_pdf', [
            'atividades' => $atividades,
            'filtroResumo' => $filtroResumo,
            'filtros' => $request->query(),
            'truncado' => $truncado,
            'totalAtividades' => $totalAtividades,
            'maxAtividades' => $maxAtividades,
        ])
            ->format('a4')
            ->withAlfaEjaBrand()
            ->download('dashboard-presencas-'.now()->format('Ymd_His').'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $request->validate([
            'evento_id' => 'required|exists:eventos,id',
        ]);

        $eventoId = $request->integer('evento_id');
        $evento = Evento::findOrFail($eventoId);

        if ($request->get('formato') === 'docx') {
            $doc = MatrizPresencaWordBuilder::build($eventoId);

            return $doc->download('Matriz_Presenca_'.Str::slug($evento->nome).'_'.now()->format('Ymd_Hi').'.docx');
        }

        $fileName = 'Matriz_Presenca_'.Str::slug($evento->nome).'_'.now()->format('Ymd_Hi').'.xlsx';

        return Excel::download(new MatrizPresencaExport($eventoId), $fileName);
    }
}
