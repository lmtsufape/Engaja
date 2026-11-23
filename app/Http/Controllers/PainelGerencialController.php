<?php

namespace App\Http\Controllers;

use App\Exports\PainelGerencialExport;
use App\Http\Controllers\Concerns\ResolvesPdfBrandMargin;
use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Municipio;
use App\Models\Regiao;
use App\Services\PainelGerencialService;
use App\Word\WordDocument;
use App\Word\WordTableExport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\LaravelPdf\Facades\Pdf;

class PainelGerencialController extends Controller
{
    use ResolvesPdfBrandMargin;

    public function __construct(private PainelGerencialService $service) {}

    public function index(Request $request)
    {
        $payload = $this->service->buildPayload($request);

        $eventos = Evento::query()->orderBy('nome')->pluck('nome', 'id');
        $regioes = Regiao::query()->orderBy('nome')->get();
        $municipios = Municipio::query()->with('estado:id,sigla')->orderBy('nome')->get();
        $momentos = Atividade::query()
            ->select('descricao')
            ->whereNotNull('descricao')
            ->where('descricao', '!=', '')
            ->distinct()
            ->orderBy('descricao')
            ->pluck('descricao');

        return view('painel-gerencial.index', array_merge($payload, compact('eventos', 'regioes', 'municipios', 'momentos')));
    }

    public function dados(Request $request): JsonResponse
    {
        return response()->json($this->service->buildPayload($request));
    }

    public function exportar(Request $request)
    {
        $formato = $request->get('formato', 'xlsx');

        if ($formato === 'pdf') {
            ini_set('memory_limit', config('dashboard.pdf.memory_limit'));

            $payload = $this->service->buildPayload($request);

            // O header do macro é renderizado a width:100% (escala com a página),
            // então a margem superior precisa acompanhar a proporção da imagem.
            // A4 paisagem tem 297mm de largura. O rodapé tem largura fixa no macro,
            // logo a margem inferior padrão (25mm) já o acomoda.
            $marginTop = $this->brandImageMarginMm('images/Alfa-Eja Header.png', 297, 40);

            return Pdf::view('painel-gerencial.pdf', $payload)
                ->format('a4')
                ->landscape()
                ->withAlfaEjaBrand($marginTop, 10, 25, 10)
                ->download('painel-gerencial-'.now()->format('Ymd_His').'.pdf');
        }

        if ($formato === 'docx') {
            $doc = new WordDocument('landscape');
            $doc->addTitle('Painel Gerencial de Quantitativos');
            WordTableExport::render($doc, new PainelGerencialExport($request));

            return $doc->download('painel-gerencial-'.now()->format('Ymd_His').'.docx');
        }

        return Excel::download(
            new PainelGerencialExport($request),
            'painel-gerencial-'.now()->format('Ymd_His').'.xlsx'
        );
    }

    public function momentos(Request $request): JsonResponse
    {
        $eventoId = $request->integer('evento_id');

        $momentos = Atividade::query()
            ->select('descricao')
            ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
            ->whereNotNull('descricao')
            ->where('descricao', '!=', '')
            ->distinct()
            ->orderBy('descricao')
            ->pluck('descricao');

        $municipios = Municipio::query()
            ->with('estado:id,sigla')
            ->whereIn('id',
                Atividade::query()
                    ->select('municipio_id')
                    ->when($eventoId, fn ($q) => $q->where('evento_id', $eventoId))
                    ->whereNotNull('municipio_id')
                    ->distinct()
                    ->pluck('municipio_id')
            )
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id'])
            ->map(fn ($m) => ['id' => $m->id, 'nome' => $m->nome_com_estado]);

        return response()->json(['momentos' => $momentos, 'municipios' => $municipios]);
    }
}
