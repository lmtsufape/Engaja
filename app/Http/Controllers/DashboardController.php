<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Municipio;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
class DashboardController extends Controller
{
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
}
