<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\AvaliacaoAtividade;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Participante;
use App\Word\AvaliacaoAtividadeWordBuilder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;

class AvaliacaoAtividadeController extends Controller
{
    use AuthorizesRequests;

    private const REPORT_EDIT_ROLES = ['administrador', 'gerente', 'eq_pedagogica'];

    private const REPORT_QUESTION_FIELDS = [
        'questao_unificada' => 'Relatório Geral do Momento (Logística, Acolhimento, Planejamento, Atuação da equipe, Recursos e Destaques).',
    ];

    private function parseIntArray(Request $request, string $key): array
    {
        return collect($request->input($key, []))
            ->map(fn ($value) => (int) $value)
            ->filter(fn ($value) => $value > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function getFilteredRelatoriosQuery(Request $request): array
    {
        $acaoIds = $this->parseIntArray($request, 'acao_ids');
        $municipioIds = $this->parseIntArray($request, 'municipio_ids');
        $momentoIds = empty($acaoIds)
            ? []
            : Atividade::query()
                ->whereIn('evento_id', $acaoIds)
                ->whereIn('id', $this->parseIntArray($request, 'momento_ids'))
                ->pluck('id')
                ->all();

        $query = AvaliacaoAtividade::query()
            ->with(['atividade.evento', 'atividade.municipios', 'user'])
            ->whereHas('atividade');

        $user = $request->user();

        if (! $user->hasAnyRole(self::REPORT_EDIT_ROLES)) {
            $query->where('user_id', $user->id);
        }

        $query->when(! empty($acaoIds), fn ($q) => $q->whereHas('atividade', fn ($a) => $a->whereIn('evento_id', $acaoIds)));
        $query->when(! empty($momentoIds), fn ($q) => $q->whereIn('atividade_id', $momentoIds));
        $query->when(! empty($municipioIds), function ($q) use ($municipioIds) {
            $q->whereHas('atividade', function ($a) use ($municipioIds) {
                $a->whereIn('municipio_id', $municipioIds)
                    ->orWhereHas('municipios', fn ($m) => $m->whereIn('municipios.id', $municipioIds));
            });
        });

        return [
            'query' => $query,
            'acaoIds' => $acaoIds,
            'momentoIds' => $momentoIds,
            'municipioIds' => $municipioIds,
        ];
    }

    public function index(Request $request)
    {
        $filterData = $this->getFilteredRelatoriosQuery($request);
        $query = $filterData['query'];
        $acaoIds = $filterData['acaoIds'];
        $momentoIds = $filterData['momentoIds'];
        $municipioIds = $filterData['municipioIds'];

        $atividadesDisponiveis = Atividade::query()
            ->with('evento')
            ->whereNotNull('evento_id')
            ->orderBy('descricao')
            ->get(['id', 'descricao', 'dia', 'evento_id', 'municipio_id']);

        $acoesDisponiveis = Evento::query()
            ->whereIn('id', $atividadesDisponiveis->pluck('evento_id')->filter()->unique()->values())
            ->orderBy('nome')
            ->get(['id', 'nome']);

        $municipiosDisponiveis = Municipio::query()
            ->with('estado:id,sigla')
            ->orderBy('nome')
            ->get();

        $relatorios = $query->orderByDesc('updated_at')->get();

        $acoesAgrupadas = $relatorios
            ->groupBy(fn (AvaliacaoAtividade $relatorio) => $relatorio->atividade?->evento?->nome ?? 'Ação pedagógica não informada')
            ->map(function ($relatoriosDaAcao) {
                return $relatoriosDaAcao
                    ->groupBy(fn (AvaliacaoAtividade $relatorio) => $relatorio->atividade_id)
                    ->map(function ($relatoriosDoMomento) {
                        return [
                            'atividade' => $relatoriosDoMomento->first()->atividade,
                            'relatorios' => $relatoriosDoMomento->values(),
                        ];
                    })
                    ->values();
            });

        $camposPerguntas = self::REPORT_QUESTION_FIELDS;

        return view('avaliacao-atividade.index', compact(
            'acoesAgrupadas',
            'camposPerguntas',
            'acoesDisponiveis',
            'atividadesDisponiveis',
            'municipiosDisponiveis',
            'acaoIds',
            'momentoIds'
        ));
    }

    private function rules(): array
    {
        return [
            'nome_educador' => ['nullable', 'string', 'max:255'],
            'qtd_participantes_prefeitura' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'qtd_participantes_movimentos_sociais' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'questao_unificada' => ['nullable', 'string'],
            'checklist_pos_acao' => ['nullable', 'array'],
            'checklist_pos_acao.*' => ['string', 'max:100'],
        ];
    }

    private function calcularResumoPublico(Atividade $atividade, AvaliacaoAtividade $avaliacao): array
    {
        $inscricoesQuery = $atividade->inscricoes()->whereNull('deleted_at');

        return [
            'prevista' => $atividade->publico_esperado ?? 0,
            'inscritos' => (clone $inscricoesQuery)->distinct('participante_id')->count('participante_id'),
            'presentes' => $atividade->presencas()
                ->where('status', 'presente')
                ->whereNull('deleted_at')
                ->distinct('inscricao_id')
                ->count('inscricao_id'),
            'movimentos' => (clone $inscricoesQuery)
                ->whereHas('participante', fn ($q) => $q->where('tag', Participante::TAG_MOVIMENTO_SOCIAL))
                ->distinct('participante_id')
                ->count('participante_id'),
            'prefeitura' => (clone $inscricoesQuery)
                ->whereHas('participante', fn ($q) => $q->where('tag', Participante::TAG_REDE_ENSINO))
                ->distinct('participante_id')
                ->count('participante_id'),
            'sem_vinculo' => (clone $inscricoesQuery)
                ->whereHas('participante', function ($q) {
                    $q->whereNull('tag')
                        ->orWhere('tag', '');
                })
                ->distinct('participante_id')
                ->count('participante_id'),
        ];
    }

    private function authorizeReport(Atividade $atividade): void
    {
        abort_unless(auth()->check(), 403, 'Sem permissão para aceder a este relatório.');
    }

    private function getUserReport(Atividade $atividade): ?AvaliacaoAtividade
    {
        $userId = auth()->id();

        return $atividade->avaliacaoAtividades()
            ->where('user_id', $userId)
            ->first();
    }

    public function create(Atividade $atividade)
    {
        $this->authorizeReport($atividade);

        if ($this->getUserReport($atividade)) {
            return redirect()->route('avaliacao-atividade.edit', $atividade);
        }

        $atividade->load(['evento', 'municipios', 'avaliacoes']);
        $avaliacao = new AvaliacaoAtividade([
            'user_id' => auth()->id(),
            'nome_educador' => auth()->user()?->name,
        ]);
        $resumoPublico = $this->calcularResumoPublico($atividade, $avaliacao);

        return view('avaliacao-atividade.create', compact('atividade', 'avaliacao', 'resumoPublico'));
    }

    public function store(Request $request, Atividade $atividade)
    {
        $this->authorizeReport($atividade);

        $dados = $request->validate($this->rules());

        if (isset($dados['questao_unificada'])) {
            $dados['questao_unificada'] = clean($dados['questao_unificada']);
        }

        $atividade->avaliacaoAtividades()->updateOrCreate(
            [
                'atividade_id' => $atividade->id,
                'user_id' => auth()->id(),
            ],
            $dados + ['user_id' => auth()->id()]
        );

        return redirect()
            ->route('eventos.show', $atividade->evento_id)
            ->with('success', 'Relatório de avaliação salvo com sucesso!');
    }

    public function edit(Atividade $atividade)
    {
        $this->authorizeReport($atividade);

        $avaliacao = $this->getUserReport($atividade)
            ?? new AvaliacaoAtividade([
                'atividade_id' => $atividade->id,
                'user_id' => auth()->id(),
                'nome_educador' => auth()->user()?->name,
            ]);

        $atividade->load(['evento', 'municipios', 'avaliacoes']);
        $resumoPublico = $this->calcularResumoPublico($atividade, $avaliacao);

        return view('avaliacao-atividade.edit', compact('atividade', 'avaliacao', 'resumoPublico'));
    }

    public function update(Request $request, Atividade $atividade)
    {
        $this->authorizeReport($atividade);

        $dados = $request->validate($this->rules());

        if (isset($dados['questao_unificada'])) {
            $dados['questao_unificada'] = clean($dados['questao_unificada']);
        }

        $atividade->avaliacaoAtividades()->updateOrCreate(
            [
                'atividade_id' => $atividade->id,
                'user_id' => auth()->id(),
            ],
            $dados + ['user_id' => auth()->id()]
        );

        return redirect()
            ->route('eventos.show', $atividade->evento_id)
            ->with('success', 'Relatório de avaliação atualizado com sucesso!');
    }

    public function show(AvaliacaoAtividade $relatorio)
    {
        $this->authorizeRelatorio($relatorio);
        $this->loadRelatorioRelations($relatorio);

        return view('avaliacao-atividade.show', [
            'relatorio' => $relatorio,
            'resumoPublico' => $this->buildResumoPublicoForRelatorio($relatorio),
            'camposPerguntas' => self::REPORT_QUESTION_FIELDS,
        ]);
    }

    public function download(AvaliacaoAtividade $relatorio, Request $request)
    {
        $this->authorizeRelatorio($relatorio);
        $this->loadRelatorioRelations($relatorio);

        $resumoPublico = $this->buildResumoPublicoForRelatorio($relatorio);

        if ($request->get('formato') === 'docx') {
            return AvaliacaoAtividadeWordBuilder::single($relatorio, $resumoPublico, self::REPORT_QUESTION_FIELDS)
                ->download('relatorio-acao-'.$relatorio->id.'.docx');
        }

        return Pdf::view('avaliacao-atividade.pdf', [
            'relatorio' => $relatorio,
            'resumoPublico' => $resumoPublico,
            'camposPerguntas' => self::REPORT_QUESTION_FIELDS,
        ])
            ->format('a4')
            ->withAlfaEjaBrand()
            ->download('relatorio-acao-'.$relatorio->id.'.pdf');
    }

    public function downloadOwn(Atividade $atividade, Request $request)
    {
        $this->authorizeReport($atividade);

        $relatorio = $this->getUserReport($atividade);
        abort_if(! $relatorio, 404, 'Relatório não encontrado para este momento.');

        return $this->download($relatorio, $request);
    }

    /**
     * Gera PDF consolidado com todos os relatórios de um momento (atividade).
     */
    public function baixarTodosPorAtividade(Atividade $atividade, Request $request)
    {
        abort_unless(
            auth()->user()?->hasAnyRole(self::REPORT_EDIT_ROLES),
            403,
            'Sem permissão para baixar relatórios consolidados.'
        );

        $atividade->load(['evento', 'municipios']);

        $relatorios = $atividade->avaliacaoAtividades()
            ->with('user')
            ->orderBy('nome_educador')
            ->get();

        abort_if($relatorios->isEmpty(), 404, 'Nenhum relatório encontrado para este momento.');

        $resumoPublico = $this->calcularResumoPublico($atividade, $relatorios->first());

        $respostasPorPergunta = collect(self::REPORT_QUESTION_FIELDS)->map(function ($pergunta, $campo) use ($relatorios) {
            return [
                'pergunta' => $pergunta,
                'respostas' => $relatorios
                    ->map(function (AvaliacaoAtividade $relatorio) use ($campo) {
                        $resposta = trim((string) ($relatorio->{$campo} ?? ''));

                        if ($resposta === '') {
                            return null;
                        }

                        $nomeResponsavel = $relatorio->user->name ?? $relatorio->nome_educador ?? 'Usuário não identificado';

                        return [
                            'responsavel_id' => $relatorio->user_id ?? '—',
                            'responsavel_nome' => $nomeResponsavel,
                            'resposta' => $resposta,
                            'atualizado_em' => $relatorio->updated_at,
                        ];
                    })
                    ->filter()
                    ->values(),
            ];
        })->values();

        if ($request->get('formato') === 'docx') {
            return AvaliacaoAtividadeWordBuilder::consolidado($atividade, $relatorios, $resumoPublico, $respostasPorPergunta)
                ->download('relatorios-consolidado-'.Str::slug($atividade->descricao ?? 'momento').'.docx');
        }

        $nomeArquivo = 'relatorios-consolidado-'.Str::slug($atividade->descricao ?? 'momento').'.pdf';

        return Pdf::view('avaliacao-atividade.pdf-consolidado', [
            'atividade' => $atividade,
            'relatorios' => $relatorios,
            'resumoPublico' => $resumoPublico,
            'camposPerguntas' => self::REPORT_QUESTION_FIELDS,
            'respostasPorPergunta' => $respostasPorPergunta,
        ])
            ->format('a4')
            ->withAlfaEjaBrand()
            ->download($nomeArquivo);
    }

    public function baixarConsolidadoFiltro(Request $request)
    {
        abort_unless(
            auth()->user()?->hasAnyRole(self::REPORT_EDIT_ROLES),
            403,
            'Sem permissão para baixar relatórios consolidados.'
        );

        $filterData = $this->getFilteredRelatoriosQuery($request);
        $query = $filterData['query'];
        $relatorios = $query->orderBy('nome_educador')->get();

        abort_if($relatorios->isEmpty(), 404, 'Nenhum relatório encontrado para este filtro.');

        $atividadesRelatorios = $relatorios->groupBy('atividade_id')->map(function ($relatoriosDoMomento) {
            $atividade = $relatoriosDoMomento->first()->atividade;
            $resumoPublico = $this->calcularResumoPublico($atividade, $relatoriosDoMomento->first());

            $respostasPorPergunta = collect(self::REPORT_QUESTION_FIELDS)->map(function ($pergunta, $campo) use ($relatoriosDoMomento) {
                return [
                    'pergunta' => $pergunta,
                    'respostas' => $relatoriosDoMomento
                        ->map(function (AvaliacaoAtividade $relatorio) use ($campo) {
                            $resposta = trim((string) ($relatorio->{$campo} ?? ''));
                            if ($resposta === '') {
                                return null;
                            }
                            $nomeResponsavel = $relatorio->user->name ?? $relatorio->nome_educador ?? 'Usuário não identificado';

                            return [
                                'responsavel_id' => $relatorio->user_id ?? '—',
                                'responsavel_nome' => $nomeResponsavel,
                                'resposta' => $resposta,
                                'atualizado_em' => $relatorio->updated_at,
                            ];
                        })
                        ->filter()
                        ->values(),
                ];
            })->values();

            return [
                'atividade' => $atividade,
                'resumoPublico' => $resumoPublico,
                'respostasPorPergunta' => $respostasPorPergunta,
            ];
        })->values();

        $nomeArquivo = 'relatorios-consolidado-filtro-'.now()->format('Ymd_His').'.pdf';

        return Pdf::view('avaliacao-atividade.pdf-consolidado-geral', [
            'dadosPorAtividade' => $atividadesRelatorios,
            'camposPerguntas' => self::REPORT_QUESTION_FIELDS,
        ])
            ->format('a4')
            ->withAlfaEjaBrand()
            ->download($nomeArquivo);
    }

    private function authorizeRelatorio(AvaliacaoAtividade $relatorio): void
    {
        abort_unless(
            auth()->user()?->hasAnyRole(self::REPORT_EDIT_ROLES) || $relatorio->user_id === auth()->id(),
            403,
            'Sem permissão para ver este relatório.'
        );
    }

    private function loadRelatorioRelations(AvaliacaoAtividade $relatorio): void
    {
        $relatorio->load('user');

        if ($relatorio->atividade) {
            $relatorio->load(['atividade.evento', 'atividade.municipios']);

            return;
        }

        if ($relatorio->atividade_id) {
            $atividade = Atividade::withTrashed()
                ->with(['evento', 'municipios'])
                ->find($relatorio->atividade_id);

            if ($atividade) {
                $relatorio->setRelation('atividade', $atividade);
            }
        }
    }

    private function buildResumoPublicoForRelatorio(AvaliacaoAtividade $relatorio): array
    {
        $atividade = $relatorio->atividade;

        if ($atividade) {
            return $this->calcularResumoPublico($atividade, $relatorio);
        }

        return [
            'prevista' => 0,
            'inscritos' => 0,
            'presentes' => 0,
            'movimentos' => $relatorio->qtd_participantes_movimentos_sociais ?? 0,
            'prefeitura' => $relatorio->qtd_participantes_prefeitura ?? 0,
            'sem_vinculo' => 0,
        ];
    }
}
