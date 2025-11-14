<?php

namespace App\Http\Controllers;

use App\Models\Avaliacao;
use App\Models\AvaliacaoQuestao;
use App\Models\RespostaAvaliacao;
use App\Models\Inscricao;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RespostaAvaliacaoController extends Controller
{
    public function index()
    {
        $respostas = RespostaAvaliacao::with(['avaliacao', 'avaliacaoQuestao', 'inscricao'])
            ->paginate(15);
        return view('respostas.index', compact('respostas'));
    }


    public function create()
    {
        $avaliacoes = Avaliacao::pluck('id', 'id');
        $questoes = AvaliacaoQuestao::with('avaliacao')
            ->get()
            ->mapWithKeys(fn ($questao) => [
                $questao->id => 'Avaliacao '.$questao->avaliacao_id.' - '.$questao->texto,
            ]);

        $inscricoes = Inscricao::with(['participante.user', 'evento'])
            ->orderByDesc('created_at')
            ->get()
            ->mapWithKeys(fn ($inscricao) => [
                $inscricao->id => ($inscricao->participante->user->name ?? 'Participante sem nome').' - '.($inscricao->evento->nome ?? 'Evento indefinido'),
            ]);

        return view('respostas.create', compact('avaliacoes', 'questoes', 'inscricoes'));
    }


    public function store(Request $request)
    {
        $dados = $request->validate([
            'avaliacao_id' => ['required', Rule::exists('avaliacaos', 'id')],
            'avaliacao_questao_id' => [
                'required',
                Rule::exists('avaliacao_questoes', 'id')
                    ->where('avaliacao_id', $request->input('avaliacao_id')),
            ],
            'inscricao_id' => ['nullable', Rule::exists('inscricaos', 'id')],
            'resposta' => ['nullable', 'string'],
        ]);

        RespostaAvaliacao::create($dados);


        return redirect()->route('respostas.index')
            ->with('success', 'Resposta registrada com sucesso!');
    }


    public function show(RespostaAvaliacao $resposta)
    {
        return view('respostas.show', compact('resposta'));
    }


    public function edit(RespostaAvaliacao $resposta)
    {
        $avaliacoes = Avaliacao::pluck('id', 'id');
        $questoes = AvaliacaoQuestao::with('avaliacao')
            ->get()
            ->mapWithKeys(fn ($questao) => [
                $questao->id => 'Avaliacao '.$questao->avaliacao_id.' - '.$questao->texto,
            ]);

        $inscricoes = Inscricao::with(['participante.user', 'evento'])
            ->orderByDesc('created_at')
            ->get()
            ->mapWithKeys(fn ($inscricao) => [
                $inscricao->id => ($inscricao->participante->user->name ?? 'Participante sem nome').' - '.($inscricao->evento->nome ?? 'Evento indefinido'),
            ]);

        return view('respostas.edit', compact('resposta', 'avaliacoes', 'questoes', 'inscricoes'));
    }


    public function update(Request $request, RespostaAvaliacao $resposta)
    {
        $dados = $request->validate([
            'avaliacao_id' => ['required', Rule::exists('avaliacaos', 'id')],
            'avaliacao_questao_id' => [
                'required',
                Rule::exists('avaliacao_questoes', 'id')
                    ->where('avaliacao_id', $request->input('avaliacao_id')),
            ],
            'inscricao_id' => ['nullable', Rule::exists('inscricaos', 'id')],
            'resposta' => ['nullable', 'string'],
        ]);

        $resposta->update($dados);


        return redirect()->route('respostas.index')
            ->with('success', 'Resposta atualizada com sucesso!');
    }


    public function destroy(RespostaAvaliacao $resposta)
    {
        $resposta->delete();
        return redirect()->route('respostas.index')
            ->with('success', 'Resposta removida com sucesso!');
    }
}
