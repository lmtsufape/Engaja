<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Municipio;
use App\Models\RespostaAvaliacao;
use App\Models\SubmissaoAvaliacao;
use App\Models\TemplateAvaliacao;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class DashboardController extends Controller
{
    public function home()
    {
        $resumo = [
            'avaliacoesRespondidas' => SubmissaoAvaliacao::count(),
            'respostas'             => RespostaAvaliacao::count(),
            'atividades'            => Atividade::count(),
            'inscricoes'            => Inscricao::count(),
            'ultimaAtualizacao'     => optional(RespostaAvaliacao::latest('updated_at')->first())->updated_at,
        ];

        $templatesDisponiveis = TemplateAvaliacao::orderBy('nome')->limit(4)->get();
        $eventosRecentes = Evento::orderByDesc('created_at')->limit(4)->get();

        return view('dashboards.home', compact('resumo', 'templatesDisponiveis', 'eventosRecentes'));
    }

    public function index(Request $request)
    {
        $eventoId   = $request->integer('evento_id');
        $de         = $request->date('de');
        $ate        = $request->date('ate');
        $q          = trim((string)$request->get('q', ''));

        $sort = $request->get('sort', 'dia');
        $dir  = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $perPage = (int) $request->query('per_page', 25);
        if (!in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $sortable = [
            'dia'       => 'atividades.dia',
            'hora'      => 'atividades.hora_inicio',
            'momento'   => 'atividades.descricao',
            'acao'      => 'eventos.nome',
            'municipio' => 'municipios.nome',
            'inscritos' => 'inscritos_count',
            'presentes' => 'presentes_count',
            'ausentes'  => 'ausentes_count',
            'total'     => 'presencas_total',
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
            ->with([
                'presencas' => fn ($q) => $q
                    ->where('status', 'presente')
                    ->with('inscricao.participante.user'),
            ])
            ->with([
                'inscricoes' => fn($q) => $q
                    ->whereNull('deleted_at')
                    ->with('participante.user'),
            ])
            ->withCount([
                'presencas as presencas_total',
                'presencas as presentes_count' => fn($q) => $q->where('status', 'presente'),
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
              ->whereHas('evento');

        $query->when($eventoId, fn($q) => $q->where('atividades.evento_id', $eventoId));
        $query->when($de && $ate, fn($q) => $q->whereBetween('atividades.dia', [$de, $ate]));
        $query->when($de && !$ate, fn($q) => $q->where('atividades.dia', '>=', $de));
        $query->when(!$de && $ate, fn($q) => $q->where('atividades.dia', '<=', $ate));

        $query->when($q !== '', function ($q2) use ($q) {
            $like = '%'.$q.'%';
            $q2->where(function ($w) use ($like) {
                $w->where('atividades.descricao', 'like', $like)
                  ->orWhere('eventos.nome', 'like', $like);
            });
        });

        $query->orderBy($orderByCol, $dir)->orderBy('atividades.id', 'desc');

        $atividades = $query->paginate($perPage)->appends($request->query());
        $atividades->getCollection()->transform(function ($atividade) {
            $inscricoes = collect($atividade->inscricoes ?? []);
            $presentes = collect($atividade->presencas ?? []);
            $presentesIds = $presentes->pluck('inscricao_id')->filter()->unique();

            $atividade->inscritos_count = $inscricoes->count();
            $atividade->presentes_count = $presentesIds->count();
            $atividade->ausentes_count = max($atividade->inscritos_count - $atividade->presentes_count, 0);

            return $atividade;
        });

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

    public function avaliacoes(Request $request)
    {
        $templates = TemplateAvaliacao::orderBy('nome')->get(['id', 'nome']);
        $eventos = Evento::orderBy('nome')->get(['id', 'nome']);
        $atividades = Atividade::with('evento')
            ->orderByDesc('dia')
            ->orderByDesc('hora_inicio')
            ->limit(80)
            ->get(['id', 'evento_id', 'descricao', 'dia', 'hora_inicio']);

        return view('dashboards.avaliacoes', compact('templates', 'eventos', 'atividades'));
    }

    public function avaliacoesData(Request $request)
    {
        $respostas = $this->filtrarRespostas($request);
        $perguntas = $this->montarPerguntas($respostas);

        $submissoesBase = $this->filtrarSubmissoes($request);
        $submissoesTable = (new SubmissaoAvaliacao())->getTable();
        $totais = [
            'submissoes' => (clone $submissoesBase)->count(),
            'atividades' => (clone $submissoesBase)->distinct('atividade_id')->count('atividade_id'),
            'eventos'    => (clone $submissoesBase)
                ->leftJoin('atividades', 'atividades.id', '=', "{$submissoesTable}.atividade_id")
                ->distinct('atividades.evento_id')
                ->count('atividades.evento_id'),
            'respostas'  => $respostas->count(),
            'questoes'   => $perguntas->count(),
            'ultima'     => optional($respostas->sortByDesc('created_at')->first())->created_at?->format('d/m/Y H:i'),
        ];

        $recentes = $respostas
            ->sortByDesc('created_at')
            ->take(8)
            ->map(function ($resposta) {
                $questao = $resposta->avaliacaoQuestao;
                return [
                    'questao' => $questao?->texto ?? 'Questao',
                    'valor'   => $this->respostaParaTexto($resposta->resposta),
                    'quando'  => optional($resposta->created_at)->format('d/m H:i'),
                ];
            })
            ->values();

        return response()->json([
            'totais'    => $totais,
            'perguntas' => $perguntas->values(),
            'recentes'  => $recentes,
        ]);
    }

    public function export(Request $request)
    {
        $pdfEventoId   = $request->integer('pdf_evento_id');
        $eventoId      = $pdfEventoId ?? $request->integer('evento_id');
        $municipioId   = $request->integer('pdf_municipio_id');
        $momento       = trim((string)$request->get('pdf_momento', ''));
        $de            = $request->date('pdf_de') ?? $request->date('de');
        $ate           = $request->date('pdf_ate') ?? $request->date('ate');
        $q             = trim((string)$request->get('q', ''));

        $sort = $request->get('sort', 'dia');
        $dir  = $request->get('dir', 'desc') === 'asc' ? 'asc' : 'desc';

        $sortable = [
            'dia'       => 'atividades.dia',
            'hora'      => 'atividades.hora_inicio',
            'momento'   => 'atividades.descricao',
            'acao'      => 'eventos.nome',
            'municipio' => 'municipios.nome',
            'inscritos' => 'inscritos_count',
            'presentes' => 'presentes_count',
            'ausentes'  => 'ausentes_count',
            'total'     => 'presencas_total',
        ];
        $orderByCol = $sortable[$sort] ?? 'atividades.dia';

        // mesma query do index, mas sem paginate() e com eager até user
        $atividades = Atividade::query()
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
                'presencas' => fn($q) => $q
                    ->where('status', 'presente')
                    ->with('inscricao.participante.user'),
            ])
            ->with([
                'inscricoes' => fn($q) => $q
                    ->whereNull('deleted_at')
                    ->with('participante.user'),
            ])
            ->withCount([
                'presencas as presencas_total',
                'presencas as presentes_count' => fn($q) => $q->where('status', 'presente'),
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
            ->whereHas('evento')
            ->when($eventoId, fn($q) => $q->where('atividades.evento_id', $eventoId))
            ->when($municipioId, fn($q) => $q->where('atividades.municipio_id', $municipioId))
            ->when($momento !== '', fn($q) => $q->where('atividades.descricao', $momento))
            ->when($de && $ate, fn($q) => $q->whereBetween('atividades.dia', [$de, $ate]))
            ->when($de && !$ate, fn($q) => $q->where('atividades.dia', '>=', $de))
            ->when(!$de && $ate, fn($q) => $q->where('atividades.dia', '<=', $ate))
            ->when($q !== '', function ($q2) use ($q) {
                $like = '%'.$q.'%';
                $q2->where(function ($w) use ($like) {
                    $w->where('atividades.descricao', 'like', $like)
                      ->orWhere('eventos.nome', 'like', $like);
                });
            })
            ->orderBy($orderByCol, $dir)
            ->orderBy('atividades.id', 'desc')
            ->get();

        $atividades->transform(function ($atividade) {
            $inscricoes = collect($atividade->inscricoes ?? []);
            $presentes = collect($atividade->presencas ?? []);
            $presentesIds = $presentes->pluck('inscricao_id')->filter()->unique();

            $atividade->inscritos_count = $inscricoes->count();
            $atividade->presentes_count = $presentesIds->count();
            $atividade->ausentes_count = max($atividade->inscritos_count - $atividade->presentes_count, 0);

            return $atividade;
        });

        $eventoSelecionado = $eventoId ? Evento::find($eventoId) : null;
        $municipioSelecionado = $municipioId
            ? Municipio::with('estado:id,sigla')->find($municipioId)
            : null;
        $periodo = null;
        if ($de && $ate) {
            $periodo = $de->format('d/m/Y') . ' - ' . $ate->format('d/m/Y');
        } elseif ($de) {
            $periodo = 'A partir de ' . $de->format('d/m/Y');
        } elseif ($ate) {
            $periodo = 'Até ' . $ate->format('d/m/Y');
        }

        $filtroResumo = array_filter([
            'Ação pedagógica' => $eventoSelecionado?->nome,
            'Município'       => $municipioSelecionado?->nome_com_estado,
            'Momento'         => $momento ?: null,
            'Período'         => $periodo,
        ]);

        $pdf = PDF::loadView('dashboard_pdf', [
            'atividades' => $atividades,
            'filtroResumo' => $filtroResumo,
            'filtros'    => $request->query(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('dashboard-presencas-'.now()->format('Ymd_His').'.pdf');
    }

    private function filtrarRespostas(Request $request)
    {
        $respostasTable = (new RespostaAvaliacao())->getTable();
        $templateId = $request->integer('template_id');
        $eventoId = $request->integer('evento_id');
        $atividadeId = $request->integer('atividade_id');
        $de = $request->date('de');
        $ate = $request->date('ate');

        return RespostaAvaliacao::query()
            ->with(['avaliacaoQuestao.escala', 'avaliacao.atividade.evento'])
            ->when($templateId, fn($q) => $q->whereHas('avaliacao', fn($aq) => $aq->where('template_avaliacao_id', $templateId)))
            ->when($atividadeId, fn($q) => $q->whereHas('avaliacao', fn($aq) => $aq->where('atividade_id', $atividadeId)))
            ->when($eventoId, fn($q) => $q->whereHas('avaliacao.atividade', fn($aq) => $aq->where('evento_id', $eventoId)))
            ->when($de, fn($q) => $q->whereDate("{$respostasTable}.created_at", '>=', $de))
            ->when($ate, fn($q) => $q->whereDate("{$respostasTable}.created_at", '<=', $ate))
            ->get();
    }

    private function filtrarSubmissoes(Request $request)
    {
        $submissoesTable = (new SubmissaoAvaliacao())->getTable();
        $templateId = $request->integer('template_id');
        $eventoId = $request->integer('evento_id');
        $atividadeId = $request->integer('atividade_id');
        $de = $request->date('de');
        $ate = $request->date('ate');

        return SubmissaoAvaliacao::query()
            ->when($templateId, fn($q) => $q->whereHas('avaliacao', fn($aq) => $aq->where('template_avaliacao_id', $templateId)))
            ->when($atividadeId, fn($q) => $q->where('atividade_id', $atividadeId))
            ->when($eventoId, fn($q) => $q->whereHas('atividade', fn($aq) => $aq->where('evento_id', $eventoId)))
            ->when($de, fn($q) => $q->whereDate("{$submissoesTable}.created_at", '>=', $de))
            ->when($ate, fn($q) => $q->whereDate("{$submissoesTable}.created_at", '<=', $ate));
    }

    private function montarPerguntas($respostas)
    {
        return $respostas
            ->groupBy('avaliacao_questao_id')
            ->map(function ($items) {
                $questao = $items->first()->avaliacaoQuestao;
                $tipo = $questao?->tipo ?? 'texto';

                $bloco = [
                    'id'      => $questao?->id ?? $items->first()->avaliacao_questao_id,
                    'texto'   => $questao?->texto ?? 'Questao',
                    'tipo'    => $tipo,
                    'total'   => $items->count(),
                    'labels'  => [],
                    'values'  => [],
                    'media'   => null,
                    'resumo'  => null,
                    'exemplos'=> [],
                    'respostas' => [],
                ];

                if ($tipo === 'boolean') {
                    $sim = $items->filter(fn($r) => $this->respostaParaBool($r->resposta) === true)->count();
                    $nao = $items->filter(fn($r) => $this->respostaParaBool($r->resposta) === false)->count();
                    $total = max($sim + $nao, 1);

                    $bloco['labels'] = ['Sim', 'Nao'];
                    $bloco['values'] = [$sim, $nao];
                    $bloco['resumo'] = $total > 0 ? round(($sim / $total) * 100) . '% de sim' : 'Sem respostas';
                    return $bloco;
                }

                if ($tipo === 'escala') {
                    $opcoes = $questao?->escala?->valores ?? [];
                    if (empty($opcoes)) {
                        $opcoes = $items->map(fn($r) => $this->respostaParaTexto($r->resposta))
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();
                    }

                    $contagem = [];
                    foreach ($opcoes as $opcao) {
                        $contagem[$opcao] = 0;
                    }

                    foreach ($items as $resposta) {
                        $valor = $this->respostaParaTexto($resposta->resposta);
                        if ($valor === '') {
                            continue;
                        }
                        $contagem[$valor] = ($contagem[$valor] ?? 0) + 1;
                    }

                    $bloco['labels'] = array_keys($contagem);
                    $bloco['values'] = array_values($contagem);

                    $media = $this->calcularMediaNumerica($items);
                    $bloco['media'] = $media;
                    $bloco['resumo'] = $media !== null ? 'Media ' . number_format($media, 1, ',', '.') : null;
                    return $bloco;
                }

                if ($tipo === 'numero') {
                    $numeros = $items->map(fn($r) => is_numeric($r->resposta) ? (float) $r->resposta : null)
                        ->filter();
                    $bloco['labels'] = $numeros->groupBy(fn($v) => (string) $v)->keys()->values();
                    $bloco['values'] = $numeros->groupBy(fn($v) => (string) $v)->map->count()->values();
                    $media = $numeros->avg();
                    $bloco['media'] = $media ? round($media, 2) : null;
                    $bloco['resumo'] = $media ? 'Media ' . number_format($media, 2, ',', '.') : null;
                    return $bloco;
                }

                $respostasTexto = $items
                    ->sortByDesc('created_at')
                    ->map(fn($r) => $this->respostaParaTexto($r->resposta))
                    ->filter()
                    ->values();

                $bloco['respostas'] = $respostasTexto->all();
                $bloco['exemplos'] = $respostasTexto->take(5)->values()->all();

                return $bloco;
            });
    }

    private function respostaParaTexto($valor): string
    {
        if ($valor === null) {
            return '';
        }

        if (is_array($valor)) {
            return implode(', ', $valor);
        }

        $texto = (string) $valor;

        if ($texto === '') {
            return '';
        }

        if (in_array(substr($texto, 0, 1), ['[', '{'], true)) {
            $decoded = json_decode($texto, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return implode(', ', $decoded);
            }
        }

        return $texto;
    }

    private function respostaParaBool($valor): ?bool
    {
        $texto = strtolower(trim($this->respostaParaTexto($valor)));

        if ($texto === '') {
            return null;
        }

        if (in_array($texto, ['1', 'true', 'sim', 's', 'yes'], true)) {
            return true;
        }

        if (in_array($texto, ['0', 'false', 'nao', 'n', 'no'], true)) {
            return false;
        }

        return null;
    }

    private function calcularMediaNumerica($items): ?float
    {
        $numeros = $items->map(function ($resposta) {
            $valor = $this->respostaParaTexto($resposta->resposta);
            return is_numeric($valor) ? (float) $valor : null;
        })->filter();

        $media = $numeros->avg();

        return $media ? round($media, 1) : null;
    }
}
