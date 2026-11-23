<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Services\AvaliacaoConsolidacaoService;
use App\Word\AvaliacaoConsolidadaWordBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\Facades\Pdf;

class AvaliacaoConsolidadaController extends Controller
{
    public function index(Request $request, AvaliacaoConsolidacaoService $service)
    {
        $eventos = Evento::query()
            ->whereHas('atividades.avaliacoes.respostas')
            ->orderByDesc('data_inicio')
            ->orderBy('nome')
            ->get(['id', 'nome', 'data_inicio']);

        // Se nenhum evento foi selecionado, usa o primeiro da lista como padrão
        $eventoId = $request->integer('evento_id') ?: ($eventos->first()?->id ?? 0);
        $agrupamento = in_array($request->get('agrupamento'), ['regiao', 'municipio'], true)
            ? $request->get('agrupamento')
            : 'geral';

        $evento = $eventoId ? Evento::find($eventoId) : null;

        if ($evento) {
            abort_unless(auth()->user()->can('update', $evento), 403);
        }

        $grupos = $evento ? $service->build($evento, $agrupamento) : [];

        return view('avaliacoes.consolidadas', compact('eventos', 'evento', 'agrupamento', 'grupos'));
    }

    public function pdf(Request $request, AvaliacaoConsolidacaoService $service)
    {
        $eventos = Evento::query()
            ->whereHas('atividades.avaliacoes.respostas')
            ->orderByDesc('data_inicio')
            ->orderBy('nome')
            ->get(['id', 'nome', 'data_inicio']);

        $eventoId = $request->integer('evento_id') ?: ($eventos->first()?->id ?? 0);
        $agrupamento = in_array($request->get('agrupamento'), ['regiao', 'municipio'], true)
            ? $request->get('agrupamento')
            : 'geral';

        $evento = $eventoId ? Evento::find($eventoId) : null;
        abort_if(! $evento, 404);
        abort_unless(auth()->user()->can('update', $evento), 403);

        $grupos = $service->build($evento, $agrupamento);

        $nomeBase = 'consolidado-'.Str::slug($evento->nome).'-'.now()->format('Ymd');

        if ($request->get('formato') === 'docx') {
            return AvaliacaoConsolidadaWordBuilder::build($evento, $agrupamento, $grupos)
                ->download($nomeBase.'.docx');
        }

        $nomeArquivo = $nomeBase.'.pdf';

        return Pdf::view('avaliacoes.consolidadas_pdf', compact('evento', 'agrupamento', 'grupos'))
            ->withAlfaEjaBrand()
            ->format('a4')
            ->download($nomeArquivo);
    }
}
