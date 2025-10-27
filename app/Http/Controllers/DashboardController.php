<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Atividade;
use App\Models\Evento;
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
                'atividades.descricao',
                'atividades.dia',
                'atividades.hora_inicio',
                'eventos.nome as evento_nome',
            ])
            ->leftJoin('eventos', 'eventos.id', '=', 'atividades.evento_id')
            ->with(['evento:id,nome'])
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

        return view('dashboard', compact('atividades', 'eventos'));
    }

    public function export(Request $request)
    {
        $eventoId   = $request->integer('evento_id');
        $de         = $request->date('de');
        $ate        = $request->date('ate');
        $q          = trim((string)$request->get('q', ''));

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
                'atividades.descricao',
                'atividades.dia',
                'atividades.hora_inicio',
                'eventos.nome as evento_nome',
            ])
            ->leftJoin('eventos', 'eventos.id', '=', 'atividades.evento_id')
            ->with(['evento:id,nome'])
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
            ->when($eventoId, fn($q) => $q->where('atividades.evento_id', $eventoId))
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

        $pdf = PDF::loadView('dashboard_pdf', [
            'atividades' => $atividades,
            'filtros'    => $request->query(), // só se quiser exibir no cabeçalho do PDF
        ])->setPaper('a4', 'portrait');

        return $pdf->download('dashboard-presencas-'.now()->format('Ymd_His').'.pdf');
    }
}
