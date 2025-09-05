<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Inscricao;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Http\Request;

class PresencaController extends Controller
{
    public function confirmarPresenca(Atividade $atividade)
    {
        return view('atividades.confirmar-presenca', compact('atividade'));
    }

    public function store(Request $request, Atividade $atividade)
    {
        $request->validate([
            'campo' => 'required|string',
        ]);

        $campo = trim(mb_strtolower($request->campo));

        $usuario = User::whereRaw('LOWER(email) = ?', [$campo])->first();
        $participante = Participante::where('cpf', $campo)
            ->orWhere('telefone', $campo)
            ->first();

        //TODO redirecionar para a tela de cadastro de participante
        if (!$usuario && !$participante) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Usuário não encontrado. Verifique o campo e tente novamente.');
        }
        if ($usuario && !$participante) {
            $participante = Participante::where('user_id', $usuario->id)->first();
        }
        $evento = $atividade->evento;
        if (Inscricao::where('evento_id', $evento->id)->where('participante_id', $participante->id)->doesntExist()) {
            $evento->participantes()->attach($participante->id);
        }
        $atividade->presencas()->updateOrCreate(
            ['inscricao_id' => $participante->id],
            ['status' => 'presente']
        );
        return redirect()->route('atividades.show', $atividade)->with('success', 'Presença confirmada com sucesso!');
    }
}
