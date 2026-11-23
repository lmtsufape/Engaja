<?php

namespace App\Http\Controllers;

use App\Exports\UsuariosSemVinculoExport;
use App\Services\UsuariosSemVinculoService;
use App\Word\WordDocument;
use App\Word\WordTableExport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class UsuariosSemVinculoController extends Controller
{
    public function index(Request $request, UsuariosSemVinculoService $service): View
    {
        $usuarios = $service
            ->query($request->user())
            ->get();

        return view('usuarios.sem-vinculo.index', [
            'usuarios' => $usuarios,
        ]);
    }

    public function exportar(Request $request)
    {
        $export = new UsuariosSemVinculoExport($request->user());
        $timestamp = now()->format('Ymd_His');

        if ($request->query('formato') === 'docx') {
            $doc = new WordDocument;
            $doc->addTitle('Usuários sem vínculo');
            WordTableExport::render($doc, $export);

            return $doc->download('usuarios-sem-vinculo-'.$timestamp.'.docx');
        }

        return Excel::download($export, 'usuarios-sem-vinculo-'.$timestamp.'.xlsx');
    }
}
