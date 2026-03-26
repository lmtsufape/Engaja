<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\AvaliacaoAtividade;
use App\Models\Participante;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AvaliacaoAtividadeController extends Controller
{
    use AuthorizesRequests;

    private const REPORT_EDIT_ROLES = ['administrador', 'gerente'];
    private const REPORT_QUESTION_FIELDS = [
        'avaliacao_logistica' => 'Quais melhorias você sugere para a logística do evento?',
        'avaliacao_acolhimento_sme' => 'Como você avalia o acolhimento e apoio da SME?',
        'avaliacao_atuacao_equipe' => 'Como você avalia a atuação da equipe do IPF?',
        'avaliacao_planejamento' => 'O planejamento desta ação foi suficiente e adequado?',
        'avaliacao_recursos_materiais' => 'Os recursos materiais atenderam aos objetivos da ação?',
        'avaliacao_links_presenca' => 'Os links e QR codes funcionaram corretamente?',
        'avaliacao_destaques' => 'Que destaques sobre esta ação você considera importantes?',
    ];

    public function index(Request $request)
    {
        $query = AvaliacaoAtividade::with(['atividade.evento', 'atividade.municipios', 'user'])
            ->whereHas('atividade');

        $user = $request->user();

        if (! $user->hasAnyRole(self::REPORT_EDIT_ROLES)) {
            $query->where('user_id', $user->id);
        }

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('atividade', fn($a) => $a->where('descricao', 'like', "%{$search}%"))
                  ->orWhereHas('atividade.evento', fn($e) => $e->where('nome', 'like', "%{$search}%"))
                  ->orWhere('nome_educador', 'like', "%{$search}%");
            });
        }

        $relatorios = $query->orderByDesc('updated_at')->get();

        $acoesAgrupadas = $relatorios
            ->groupBy(fn(AvaliacaoAtividade $relatorio) => $relatorio->atividade?->evento?->nome ?? 'Ação pedagógica não informada')
            ->map(function ($relatoriosDaAcao) {
                return $relatoriosDaAcao
                    ->groupBy(fn(AvaliacaoAtividade $relatorio) => $relatorio->atividade_id)
                    ->map(function ($relatoriosDoMomento) {
                        return [
                            'atividade' => $relatoriosDoMomento->first()->atividade,
                            'relatorios' => $relatoriosDoMomento->values(),
                        ];
                    })
                    ->values();
            });

        $camposPerguntas = self::REPORT_QUESTION_FIELDS;

        return view('avaliacao-atividade.index', compact('acoesAgrupadas', 'camposPerguntas', 'search'));
    }

    private function rules(): array
    {
        return [
            'nome_educador'                        => ['nullable', 'string', 'max:255'],
            'qtd_participantes_prefeitura'         => ['nullable', 'integer', 'min:0', 'max:9999'],
            'qtd_participantes_movimentos_sociais' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'avaliacao_logistica'                  => ['nullable', 'string'],
            'avaliacao_acolhimento_sme'            => ['nullable', 'string'],
            'avaliacao_recursos_materiais'         => ['nullable', 'string'],
            'avaliacao_planejamento'               => ['nullable', 'string'],
            'avaliacao_links_presenca'             => ['nullable', 'string'],
            'avaliacao_destaques'                  => ['nullable', 'string'],
            'avaliacao_atuacao_equipe'             => ['nullable', 'string'],
            'checklist_pos_acao'                   => ['nullable', 'array'],
            'checklist_pos_acao.*'                 => ['string', 'max:100'],
        ];
    }

    private function calcularResumoPublico(Atividade $atividade, AvaliacaoAtividade $avaliacao): array
    {
        $inscricoesQuery = $atividade->inscricoes()->whereNull('deleted_at');

        return [
            'prevista'   => $atividade->publico_esperado ?? 0,
            'inscritos'  => (clone $inscricoesQuery)->distinct('participante_id')->count('participante_id'),
            'presentes'  => $atividade->presencas()
                ->where('status', 'presente')
                ->whereNull('deleted_at')
                ->distinct('inscricao_id')
                ->count('inscricao_id'),
            'movimentos' => (clone $inscricoesQuery)
                ->whereHas('participante', fn($q) => $q->where('tag', Participante::TAG_MOVIMENTO_SOCIAL))
                ->distinct('participante_id')
                ->count('participante_id'),
            'prefeitura' => (clone $inscricoesQuery)
                ->whereHas('participante', fn($q) => $q->where('tag', Participante::TAG_REDE_ENSINO))
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
        ]);
    }

    public function download(AvaliacaoAtividade $relatorio)
    {
        $this->authorizeRelatorio($relatorio);
        $this->loadRelatorioRelations($relatorio);

        $resumoPublico = $this->buildResumoPublicoForRelatorio($relatorio);
        $pdf = Pdf::loadView('avaliacao-atividade.pdf', [
            'relatorio' => $relatorio,
            'resumoPublico' => $resumoPublico,
            'camposPerguntas' => self::REPORT_QUESTION_FIELDS,
        ]);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('relatorio-acao-' . $relatorio->id . '.pdf');
    }

    public function downloadOwn(Atividade $atividade)
    {
        $this->authorizeReport($atividade);

        $relatorio = $this->getUserReport($atividade);
        abort_if(!$relatorio, 404, 'Relatório não encontrado para este momento.');

        return $this->download($relatorio);
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
        ];
    }
}