<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Atividade;
use App\Models\Evento;

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

        $sortable = [
            'dia'           => 'atividades.dia',
            'hora'          => 'atividades.hora_inicio',
            'momento'       => 'atividades.descricao',
            'acao'          => 'eventos.nome',
            'presentes'     => 'presentes_count',
            'ausentes'      => 'ausentes_count',
            'total'         => 'presencas_total',
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
            ->withCount([
                'presencas as presencas_total',
                'presencas as presentes_count'    => fn($q) => $q->where('status', 'presente'),
                'presencas as ausentes_count'     => fn($q) => $q->where('status', 'ausente'),
            ]);

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

        $atividades = $query->paginate(25)->appends($request->query());

        $eventos = Evento::query()->orderBy('nome')->pluck('nome', 'id');

        return view('dashboard', compact('atividades', 'eventos'));
    }
}
