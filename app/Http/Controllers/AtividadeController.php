<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\Atividade;
use App\Models\Inscricao;
use App\Models\Presenca;
use App\Models\Participante;
use App\Models\Municipio;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AtividadeController extends Controller
{
    use AuthorizesRequests;
    public function index(Evento $evento)
    {
        $atividades = $evento->atividades()
            ->with('municipios.estado')
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->paginate(12);
        return view('atividades.index', compact('evento', 'atividades'));
    }

    public function create(Evento $evento)
    {
        $this->authorize('update', $evento);
        $municipios = Municipio::with(['estado.regiao'])
            ->get(['id', 'nome', 'estado_id'])
            ->sortBy(function ($m) {
                $regiao = $m->estado->regiao->nome ?? '';
                $regiaoLower = mb_strtolower(trim($regiao));
                $ordemRegiao = match ($regiaoLower) {
                    'nordeste i', 'nordeste 1'  => 1,
                    'nordeste ii', 'nordeste 2' => 2,
                    'norte'                     => 3,
                    default                     => 9,
                };
                return sprintf('%02d-%s', $ordemRegiao, $m->nome);
            })
            ->values();

        $atividadesCopiaveis = $this->listarAtividadesCopiaveis();

        return view('atividades.create', compact('evento', 'municipios', 'atividadesCopiaveis'));
    }

    public function store(Request $request, Evento $evento)
    {
        $this->authorize('update', $evento);

        $dados = $request->validate([
            'municipios'          => 'required|array|min:1',
            'municipios.*'        => 'exists:municipios,id',
            'descricao'           => 'required|string',
            'dia'                 => 'required|date',
            'hora_inicio'         => 'required|date_format:H:i',
            'hora_fim'            => 'required|date_format:H:i|after:hora_inicio',
            'publico_esperado'    => 'nullable|integer|min:0',
            'carga_horaria'       => 'nullable|integer|min:0',
            'copiar_inscritos_de' => 'nullable|exists:atividades,id',
        ]);

        $copiarDe = $dados['copiar_inscritos_de'] ?? null;
        unset($dados['copiar_inscritos_de']);
        $municipiosSelecionados = $dados['municipios'];
        unset($dados['municipios']);

        // Mantém o campo legado municipio_id preenchido com o primeiro selecionado (para compatibilidade).
        $dados['municipio_id'] = $municipiosSelecionados[0] ?? null;

        $atividade = $evento->atividades()->create($dados);
        $atividade->municipios()->sync($municipiosSelecionados);
        $copiados = $this->copiarInscritos($copiarDe, $atividade);

        return redirect()
            ->route('eventos.show', $evento)
            ->with('success', $this->mensagemSucesso('Momento adicionado com sucesso!', $copiados));
    }

    public function edit(Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('update', $evento);

        $atividade->load('municipios');

        $municipios = Municipio::with(['estado.regiao'])
            ->get(['id', 'nome', 'estado_id'])
            ->sortBy(function ($m) {
                $regiao = $m->estado->regiao->nome ?? '';
                $regiaoLower = mb_strtolower(trim($regiao));
                $ordemRegiao = match ($regiaoLower) {
                    'nordeste i', 'nordeste 1'  => 1,
                    'nordeste ii', 'nordeste 2' => 2,
                    'norte'                     => 3,
                    default                     => 9,
                };
                return sprintf('%02d-%s', $ordemRegiao, $m->nome);
            })
            ->values();

        $atividadesCopiaveis = $this->listarAtividadesCopiaveis($atividade);

        return view('atividades.edit', compact('evento', 'atividade', 'municipios', 'atividadesCopiaveis'));
    }

    public function update(Request $request, Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('update', $evento);

        $dados = $request->validate([
            'municipios'          => 'required|array|min:1',
            'municipios.*'        => 'exists:municipios,id',
            'descricao'           => 'required|string',
            'dia'                 => 'required|date',
            'hora_inicio'         => 'required|date_format:H:i',
            'hora_fim'            => 'required|date_format:H:i|after:hora_inicio',
            'publico_esperado'    => 'nullable|integer|min:0',
            'carga_horaria'       => 'nullable|integer|min:0',
            'copiar_inscritos_de' => 'nullable|exists:atividades,id',
        ]);

        $copiarDe = $dados['copiar_inscritos_de'] ?? null;
        unset($dados['copiar_inscritos_de']);
        $municipiosSelecionados = $dados['municipios'];
        unset($dados['municipios']);

        $dados['municipio_id'] = $municipiosSelecionados[0] ?? null;

        $atividade->update($dados);
        $atividade->municipios()->sync($municipiosSelecionados);
        $copiados = $this->copiarInscritos($copiarDe, $atividade);

        return redirect()
            ->route('eventos.show', $evento)
            ->with('success', $this->mensagemSucesso('Momento atualizado com sucesso!', $copiados));
    }

    public function destroy(Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('delete', $evento);

        $atividade->delete();

        return back()->with('success', 'Momento removida.');
    }

    public function show(\App\Models\Atividade $atividade)
    {
        $atividade->load(['evento', 'municipios.estado']);

        $presencas = $atividade->presencas()
            ->with([
                'inscricao.participante.user:id,name,email',
                'inscricao.participante.municipio.estado:id,nome,sigla',
            ])
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();
        $user = auth()->user();
        $podeImportar = $user?->can('presenca.import') ?? false;
        $podeAbrir    = $user?->can('presenca.abrir')   ?? false;

        return view('atividades.show', compact('atividade', 'presencas', 'podeImportar', 'podeAbrir'));
    }

    public function togglePresenca(Atividade $atividade)
    {
        $atividade->presenca_ativa = ! $atividade->presenca_ativa;
        $atividade->save();

        return back()->with(
            'success',
            $atividade->presenca_ativa ? 'Presença aberta para este momento.' : 'Presença fechada para este momento.'
        );
    }

    public function checkin(Atividade $atividade)
    {
        if (! $atividade->presenca_ativa) {
            return back()->withErrors(['checkin' => 'Presença não está aberta para este momento.']);
        }

        $user = auth()->user();

        // 1) Garante Participante para o usuário
        $participante = Participante::firstOrCreate(['user_id' => $user->id], []);

        // 2) Garante Inscrição no evento (cria/reativa)
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

        // 3) Marca presenca (idempotente)
        Presenca::updateOrCreate(
            ['inscricao_id' => $inscricao->id, 'atividade_id' => $atividade->id],
            ['status' => 'presente', 'justificativa' => null]
        );

        return back()->with('success', 'Presença confirmada com sucesso!');
    }

    private function listarAtividadesCopiaveis(?Atividade $ignorar = null)
    {
        return Atividade::with('evento')
            ->withCount('inscricoes')
            ->whereHas('inscricoes')
            ->when($ignorar, fn($q) => $q->where('id', '!=', $ignorar->id))
            ->orderByDesc('dia')
            ->orderBy('hora_inicio')
            ->get();
    }

    private function copiarInscritos(?int $origemId, Atividade $destino): int
    {
        if (!$origemId || $origemId === $destino->id) {
            return 0;
        }

        $origem = Atividade::find($origemId);
        if (!$origem) {
            return 0;
        }

        $inscricoes = Inscricao::withTrashed()
            ->where('atividade_id', $origem->id)
            ->get();

        $copiados = 0;
        foreach ($inscricoes as $inscricao) {
            $existente = Inscricao::withTrashed()
                ->where('atividade_id', $destino->id)
                ->where('participante_id', $inscricao->participante_id)
                ->first();

            if ($existente) {
                $existente->evento_id = $destino->evento_id;
                if ($existente->trashed()) {
                    $existente->restore();
                    $copiados++;
                }
                $existente->save();
                continue;
            }

            Inscricao::create([
                'evento_id'       => $destino->evento_id,
                'atividade_id'    => $destino->id,
                'participante_id' => $inscricao->participante_id,
            ]);
            $copiados++;
        }

        return $copiados;
    }

    private function mensagemSucesso(string $mensagem, int $copiados = 0): string
    {
        if ($copiados <= 0) {
            return $mensagem;
        }

        $sufixo = $copiados === 1 ? ' 1 inscrito copiado.' : " {$copiados} inscritos copiados.";
        return $mensagem . $sufixo;
    }
}
