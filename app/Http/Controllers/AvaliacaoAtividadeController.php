<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\AvaliacaoAtividade;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AvaliacaoAtividadeController extends Controller
{
    use AuthorizesRequests;

    private function rules(): array
    {
        return [
            'nome_educador'                        => ['nullable', 'string', 'max:255'],
            'qtd_participantes_prefeitura'         => ['nullable', 'integer', 'min:0', 'max:9999'],
            'qtd_participantes_movimentos_sociais' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'avaliacao_logistica'                  => ['nullable', 'string'],
            'avaliacao_acolhimento_sme'            => ['nullable', 'string'],
            'avaliacao_recursos_materiais'         => ['nullable', 'string'],
            'avaliacao_planejamento'               => ['nullable', 'string'],
            'avaliacao_links_presenca'             => ['nullable', 'string'],
            'avaliacao_destaques'                  => ['nullable', 'string'],
            'avaliacao_atuacao_equipe'             => ['nullable', 'string'],
        ];
    }

    public function create(Atividade $atividade)
    {
        $this->authorize('update', $atividade->evento);

        if ($atividade->avaliacaoAtividade) {
            return redirect()->route('avaliacao-atividade.edit', $atividade);
        }

        $atividade->load(['evento', 'municipios']);
        $avaliacao = new AvaliacaoAtividade();

        return view('avaliacao-atividade.create', compact('atividade', 'avaliacao'));
    }

    public function store(Request $request, Atividade $atividade)
    {
        $this->authorize('update', $atividade->evento);

        $dados = $request->validate($this->rules());
        $atividade->avaliacaoAtividade()->create($dados);

        return redirect()
            ->route('eventos.show', $atividade->evento_id)
            ->with('success', 'Relatório de avaliação salvo com sucesso!');
    }

    public function edit(Atividade $atividade)
    {
        $this->authorize('update', $atividade->evento);

        $avaliacao = $atividade->avaliacaoAtividade
            ?? new AvaliacaoAtividade(['atividade_id' => $atividade->id]);

        $atividade->load(['evento', 'municipios']);

        return view('avaliacao-atividade.edit', compact('atividade', 'avaliacao'));
    }

    public function update(Request $request, Atividade $atividade)
    {
        $this->authorize('update', $atividade->evento);

        $dados = $request->validate($this->rules());

        $atividade->avaliacaoAtividade()->updateOrCreate(
            ['atividade_id' => $atividade->id],
            $dados
        );

        return redirect()
            ->route('eventos.show', $atividade->evento_id)
            ->with('success', 'Relatório de avaliação atualizado com sucesso!');
    }
}