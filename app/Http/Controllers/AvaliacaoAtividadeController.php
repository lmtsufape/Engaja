<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\AvaliacaoAtividade;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class AvaliacaoAtividadeController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $query = AvaliacaoAtividade::with(['atividade.evento', 'atividade.municipios'])
            ->whereHas('atividade');

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->whereHas('atividade', fn($a) => $a->where('descricao', 'like', "%{$search}%"))
                  ->orWhereHas('atividade.evento', fn($e) => $e->where('nome', 'like', "%{$search}%"))
                  ->orWhere('nome_educador', 'like', "%{$search}%");
            });
        }

        $relatorios = $query->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('avaliacao-atividade.index', compact('relatorios', 'search'));
    }

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
            'checklist_pos_acao'                   => ['nullable', 'array'],
            'checklist_pos_acao.*'                 => ['string', 'max:100'],
        ];
    }

    private function calcularResumoPublico(Atividade $atividade, AvaliacaoAtividade $avaliacao): array
    {
        return [
            'prevista'   => $atividade->publico_esperado ?? 0,
            'inscritos'  => $atividade->inscricoes()->whereNull('deleted_at')->count(),
            'presentes'  => $atividade->presencas()->where('status', 'presente')->count(),
            'movimentos' => $avaliacao->qtd_participantes_movimentos_sociais ?? 0,
            'prefeitura' => $avaliacao->qtd_participantes_prefeitura ?? 0,
        ];
    }

    private function authorizeReport(Atividade $atividade): void
    {
        $user = auth()->user();
        abort_unless(
            $user->hasAnyRole(['administrador', 'gerente']) || $user->can('evento.editar'),
            403,
            'Sem permissão para aceder a este relatório.'
        );
    }

    public function create(Atividade $atividade)
    {
        $this->authorizeReport($atividade);

        if ($atividade->avaliacaoAtividade) {
            return redirect()->route('avaliacao-atividade.edit', $atividade);
        }

        $atividade->load(['evento', 'municipios', 'avaliacoes']);
        $avaliacao = new AvaliacaoAtividade();
        $resumoPublico = $this->calcularResumoPublico($atividade, $avaliacao);

        return view('avaliacao-atividade.create', compact('atividade', 'avaliacao', 'resumoPublico'));
    }

    public function store(Request $request, Atividade $atividade)
    {
        $this->authorizeReport($atividade);

        $dados = $request->validate($this->rules());
        $atividade->avaliacaoAtividade()->create($dados);

        return redirect()
            ->route('eventos.show', $atividade->evento_id)
            ->with('success', 'Relatório de avaliação salvo com sucesso!');
    }

    public function edit(Atividade $atividade)
    {
        $this->authorizeReport($atividade);

        $avaliacao = $atividade->avaliacaoAtividade
            ?? new AvaliacaoAtividade(['atividade_id' => $atividade->id]);

        $atividade->load(['evento', 'municipios', 'avaliacoes']);
        $resumoPublico = $this->calcularResumoPublico($atividade, $avaliacao);

        return view('avaliacao-atividade.edit', compact('atividade', 'avaliacao', 'resumoPublico'));
    }

    public function update(Request $request, Atividade $atividade)
    {
        $this->authorizeReport($atividade);

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