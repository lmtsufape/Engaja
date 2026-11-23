<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CuradoriaDemograficoController extends Controller
{
    public function index()
    {
        // Require role 'administrador' or 'gerente'
        if (! auth()->user()->hasAnyRole(['administrador', 'gerente'])) {
            abort(403, 'Acesso negado.');
        }

        $curadorias = \App\Models\CuradoriaDemografico::with('user')
            ->where('vinculado', false)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('curadoria_demograficos.index', compact('curadorias'));
    }

    public function vincular(Request $request, \App\Models\CuradoriaDemografico $curadoria)
    {
        if (! auth()->user()->hasAnyRole(['administrador', 'gerente'])) {
            abort(403, 'Acesso negado.');
        }

        $usuario = $curadoria->user;
        if ($usuario) {
            if ($usuario->demograficosCompletos()) {
                $curadoria->delete();
                return redirect()->route('curadoria.index')->with('success', 'O usuário já possuía os dados completos');
            }

            $usuario->update([
                'identidade_genero' => $curadoria->identidade_genero,
                'identidade_genero_outro' => $curadoria->identidade_genero_outro,
                'raca_cor' => $curadoria->raca_cor,
                'comunidade_tradicional' => $curadoria->comunidade_tradicional,
                'comunidade_tradicional_outro' => $curadoria->comunidade_tradicional_outro,
                'faixa_etaria' => $curadoria->faixa_etaria,
                'pcd' => $curadoria->pcd,
                'orientacao_sexual' => $curadoria->orientacao_sexual,
                'orientacao_sexual_outra' => $curadoria->orientacao_sexual_outra,
            ]);

            $curadoria->update(['vinculado' => true]);

            return redirect()->route('curadoria.index')->with('success', 'Dados demográficos vinculados ao usuário com sucesso!');
        }

        return redirect()->route('curadoria.index')->with('error', 'Usuário não encontrado.');
    }

    public function destroy(\App\Models\CuradoriaDemografico $curadoria)
    {
        if (! auth()->user()->hasAnyRole(['administrador', 'gerente'])) {
            abort(403, 'Acesso negado.');
        }

        $curadoria->delete();

        return redirect()->route('curadoria.index')->with('success', 'Registro excluído com sucesso!');
    }
}
