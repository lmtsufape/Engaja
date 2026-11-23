<?php

namespace App\Http\Controllers\Cartas;

use App\Http\Controllers\Controller;
use App\Services\Cartas\CartaViewerLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Recebe as duas últimas etapas da visualização (download e renderização pelo
 * pdf.js) executadas no navegador e as grava no mesmo log do servidor — sem
 * isso, a falha só apareceria no console do usuário.
 */
class CartaViewerDiagnosticController extends Controller
{
    private const ETAPAS = [
        'download' => CartaViewerLogger::ETAPA_DOWNLOAD,
        'render' => CartaViewerLogger::ETAPA_RENDER,
    ];

    public function __construct(private CartaViewerLogger $viewerLog) {}

    public function store(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'etapa' => ['required', 'string', 'in:download,render'],
            'sucesso' => ['required', 'boolean'],
            'detalhe' => ['nullable', 'string', 'max:500'],
            'mensagem_id' => ['nullable', 'integer'],
        ]);

        $this->viewerLog->passo(
            self::ETAPAS[$dados['etapa']],
            $dados['sucesso'],
            Str::limit((string) ($dados['detalhe'] ?? ''), 300, ''),
            $dados['mensagem_id'] ?? null
        );

        return response()->json(['registrado' => true]);
    }
}
