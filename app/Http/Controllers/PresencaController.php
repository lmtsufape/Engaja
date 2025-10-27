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
                ->with('error', 'Seus dados não foram encontrados no sistema. Solicitamos, por gentileza, que registre sua presença no formulário impresso.')
                ->with('show_register_button', true);
        }
        if ($usuario && !$participante) {
            $participante = Participante::where('user_id', $usuario->id)->first();
        }
        $evento = $atividade->evento;

        $inscricao = Inscricao::withTrashed()
            ->where('participante_id', $participante->id)
            ->where('atividade_id', $atividade->id)
            ->first();

        if (!$inscricao) {
            $inscricao = Inscricao::withTrashed()
                ->where('participante_id', $participante->id)
                ->where('evento_id', $evento->id)
                ->whereNull('atividade_id')
                ->first();
        }

        if ($inscricao) {
            $inscricao->fill([
                'evento_id'       => $evento->id,
                'atividade_id'    => $atividade->id,
                'participante_id' => $participante->id,
            ]);
            $inscricao->deleted_at = null;
            $inscricao->save();
        } else {
            $inscricao = Inscricao::create([
                'evento_id'       => $evento->id,
                'atividade_id'    => $atividade->id,
                'participante_id' => $participante->id,
            ]);
        }

        $atividade->presencas()->updateOrCreate(
            ['inscricao_id' => $inscricao->id],
            ['status' => 'presente']
        );
        $dia = \Carbon\Carbon::parse($atividade->dia)
            ->locale('pt_BR')
            ->translatedFormat('l, d \\d\\e F \\d\\e Y');
        return redirect()->route('presenca.confirmar', $atividade->id)->with('success', "Presença confirmada com sucesso na ação pedagógica ".$evento->nome.", no momento ".$atividade->descricao." (".$dia.")!");
    }
}
