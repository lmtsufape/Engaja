<?php

namespace App\Http\Controllers;

use App\Exports\ParticipantesExclusivosExport;
use App\Models\Evento;
use App\Services\ParticipantesExclusivosService;
use App\Word\WordDocument;
use App\Word\WordTableExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class ParticipantesExclusivosController extends Controller
{
    public function index(Request $request): View
    {
        $eventos = Evento::query()
            ->withCount('atividades')
            ->orderBy('nome')
            ->get(['id', 'nome', 'data_inicio', 'data_fim', 'data_horario']);

        return view('usuarios.participantes-exclusivos.index', [
            'eventos' => $eventos,
            'selecionados' => collect($request->query('eventos', []))
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->all(),
        ]);
    }

    public function resultado(Request $request, ParticipantesExclusivosService $service): View|RedirectResponse
    {
        $eventoIds = $service->normalizarIds((array) $request->query('eventos', []));

        if ($eventoIds === []) {
            return redirect()
                ->route('usuarios.participantes-exclusivos.index')
                ->withErrors(['eventos' => 'Selecione ao menos uma ação pedagógica.']);
        }

        $eventosSelecionados = Evento::query()
            ->whereIn('id', $eventoIds)
            ->orderBy('nome')
            ->get(['id', 'nome']);

        if ($eventosSelecionados->count() !== count($eventoIds)) {
            return redirect()
                ->route('usuarios.participantes-exclusivos.index')
                ->withErrors(['eventos' => 'Uma ou mais ações pedagógicas selecionadas não foram encontradas.']);
        }

        $participantes = $service
            ->query($eventoIds)
            ->paginate(50)
            ->appends(['eventos' => $eventoIds]);

        return view('usuarios.participantes-exclusivos.resultado', [
            'eventoIds' => $eventoIds,
            'eventosSelecionados' => $eventosSelecionados,
            'participantes' => $participantes,
        ]);
    }

    public function exportar(Request $request, ParticipantesExclusivosService $service)
    {
        $eventoIds = $service->normalizarIds((array) $request->query('eventos', []));

        if ($eventoIds === []) {
            return redirect()
                ->route('usuarios.participantes-exclusivos.index')
                ->withErrors(['eventos' => 'Selecione ao menos uma ação pedagógica antes de exportar.']);
        }

        $existentes = Evento::query()
            ->whereIn('id', $eventoIds)
            ->count();

        if ($existentes !== count($eventoIds)) {
            return redirect()
                ->route('usuarios.participantes-exclusivos.index')
                ->withErrors(['eventos' => 'Uma ou mais ações pedagógicas selecionadas não foram encontradas.']);
        }

        $export = new ParticipantesExclusivosExport($eventoIds);
        $timestamp = now()->format('Ymd_His');

        if ($request->query('formato') === 'docx') {
            $doc = new WordDocument;
            $doc->addTitle('Participantes exclusivos');
            WordTableExport::render($doc, $export);

            return $doc->download('participantes-exclusivos-'.$timestamp.'.docx');
        }

        return Excel::download($export, 'participantes-exclusivos-'.$timestamp.'.xlsx');
    }
}
