<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Inscricao;
use App\Models\Participante;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Throwable;

class PresencaController extends Controller
{
    public function confirmarPresenca(Atividade $atividade)
    {
        $atividade->load(['municipios.estado']);
        $presencaAberta = $atividade->presencaEstaAberta();

        return view('atividades.confirmar-presenca', compact('atividade', 'presencaAberta'));
    }

    public function store(Request $request, Atividade $atividade)
    {
        if (! $atividade->presencaEstaAberta()) {
            return redirect()
                ->route('presenca.confirmar', $atividade)
                ->with('error', 'A confirmação de presença deste momento está encerrada.');
        }

        $request->validate([
            'campo' => 'required|string',
        ]);

        $campo = trim(mb_strtolower($request->campo));

        $usuario = User::whereRaw('LOWER(email) = ?', [$campo])->first();
        $participante = Participante::where('cpf', $campo)
            ->orWhere('telefone', $campo)
            ->first();

        if (! $usuario && $participante) {
            $usuario = User::find($participante->user_id);
        }

        // TODO redirecionar para a tela de cadastro de participante
        if (! $usuario && ! $participante) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Seus dados não foram encontrados no sistema. Solicitamos, por gentileza, que registre sua presença no formulário impresso.')
                ->with('show_register_button', true);
        }

        if ($usuario && ! $participante) {
            $participante = Participante::where('user_id', $usuario->id)->first();
        }

        // Só abre o modal se os dados demográficos estiverem incompletos na tabela users
        if ($usuario && ! $usuario->demograficosCompletos()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('demograficos_pendentes', true)
                ->with('demograficos_user_token', encrypt($usuario->id))
                ->with('demograficos_user_nome', $usuario->name)
                ->with('demograficos_campo_input', $request->campo);
        }

        return $this->confirmarPresencaParaUsuario($atividade, $usuario, $participante);
    }

    /**
     * Salva os dados demográficos do usuário identificado pelo token criptografado
     * e em seguida confirma a presença na atividade.
     */
    public function salvarDemograficosEConfirmar(Request $request, Atividade $atividade)
    {
        if (! $atividade->presencaEstaAberta()) {
            return redirect()
                ->route('presenca.confirmar', $atividade)
                ->with('error', 'A confirmação de presença deste momento está encerrada.');
        }

        $request->validate([
            'user_token' => 'required|string',
            'identidade_genero' => 'required|string',
            'identidade_genero_outro' => 'nullable|string|max:255|required_if:identidade_genero,Outro',
            'raca_cor' => 'required|string',
            'comunidade_tradicional' => 'required|string',
            'comunidade_tradicional_outro' => 'nullable|string|max:255|required_if:comunidade_tradicional,Outro',
            'faixa_etaria' => 'required|string',
            'pcd' => 'required|string',
            'orientacao_sexual' => 'required|string',
            'orientacao_sexual_outra' => 'nullable|string|max:255|required_if:orientacao_sexual,Outra',
        ], [
            'identidade_genero.required' => 'Identidade de gênero é obrigatória.',
            'raca_cor.required' => 'Raça/Cor é obrigatória.',
            'comunidade_tradicional.required' => 'Pertencimento a comunidade é obrigatório.',
            'faixa_etaria.required' => 'Faixa etária é obrigatória.',
            'pcd.required' => 'Campo PcD é obrigatório.',
            'orientacao_sexual.required' => 'Orientação sexual é obrigatória.',
        ]);

        // Resolve o usuário de forma segura via token criptografado
        try {
            $userId = decrypt($request->user_token);
        } catch (Throwable) {
            return redirect()
                ->route('presenca.confirmar', $atividade)
                ->with('error', 'Token inválido. Por favor, reinicie o processo de confirmação de presença.');
        }

        $usuario = User::find($userId);
        if (! $usuario) {
            return redirect()
                ->route('presenca.confirmar', $atividade)
                ->with('error', 'Usuário não encontrado. Por favor, reinicie o processo.');
        }

        // Salva os dados demográficos diretamente na tabela users
        $usuario->update([
            'identidade_genero' => $request->identidade_genero,
            'identidade_genero_outro' => $request->identidade_genero_outro,
            'raca_cor' => $request->raca_cor,
            'comunidade_tradicional' => $request->comunidade_tradicional,
            'comunidade_tradicional_outro' => $request->comunidade_tradicional_outro,
            'faixa_etaria' => $request->faixa_etaria,
            'pcd' => $request->pcd,
            'orientacao_sexual' => $request->orientacao_sexual,
            'orientacao_sexual_outra' => $request->orientacao_sexual_outra,
        ]);

        $participante = Participante::where('user_id', $usuario->id)->first();
        if (! $participante) {
            $participante = Participante::firstOrCreate(['user_id' => $usuario->id]);
        }

        return $this->confirmarPresencaParaUsuario($atividade, $usuario, $participante);
    }

    /**
     * Lógica compartilhada de confirmação de presença para um usuário/participante já resolvido.
     */
    private function confirmarPresencaParaUsuario(Atividade $atividade, User $usuario, ?Participante $participante)
    {
        if (! $atividade->presencaEstaAberta()) {
            return redirect()
                ->route('presenca.confirmar', $atividade)
                ->with('error', 'A confirmação de presença deste momento está encerrada.');
        }

        $evento = $atividade->evento;

        $inscricao = Inscricao::withTrashed()
            ->where('participante_id', $participante->id)
            ->where('atividade_id', $atividade->id)
            ->first();

        if (! $inscricao) {
            $inscricao = Inscricao::withTrashed()
                ->where('participante_id', $participante->id)
                ->where('evento_id', $evento->id)
                ->whereNull('atividade_id')
                ->first();
        }

        if ($inscricao) {
            $inscricao->fill([
                'evento_id' => $evento->id,
                'atividade_id' => $atividade->id,
                'participante_id' => $participante->id,
                'ouvinte' => $inscricao->atividade_id === $atividade->id ? $inscricao->ouvinte : true,
            ]);
            $inscricao->deleted_at = null;
            $inscricao->save();
        } else {
            $inscricao = Inscricao::create([
                'evento_id' => $evento->id,
                'atividade_id' => $atividade->id,
                'participante_id' => $participante->id,
                'ouvinte' => true,
            ]);
        }

        $presenca = $atividade->presencas()->updateOrCreate(
            ['inscricao_id' => $inscricao->id],
            ['status' => 'presente']
        );
        if (is_null($presenca->avaliacao_respondida)) {
            $presenca->avaliacao_respondida = false;
            $presenca->save();
        }

        $dia = Carbon::parse($atividade->dia)
            ->locale('pt_BR')
            ->translatedFormat('l, d \d\e F \d\e Y');

        return redirect()
            ->route('presenca.confirmar', $atividade->id)
            ->with([
                'usuario_nome' => $usuario->name,
                'evento_nome' => $evento->nome,
                'atividade_nome' => $atividade->descricao,
                'dia' => $dia,
                'success-presenca' => 'Presença confirmada com sucesso!',
                'status_presenca_label' => 'Sua presença foi confirmada!',
                'artigo_status_presenca' => 'sua presença',
                'avaliacao_token' => $presenca->avaliacao_respondida ? null : encrypt($presenca->id),
                'avaliacao_disponivel' => ! $presenca->avaliacao_respondida,
            ]);
    }
}
