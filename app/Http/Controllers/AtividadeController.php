<?php

namespace App\Http\Controllers;

use App\Models\Atividade;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Municipio;
use App\Models\Participante;
use App\Models\Presenca;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AtividadeController extends Controller
{
    use AuthorizesRequests;
    public function index(Evento $evento)
    {
        $userId = auth()->id();

        $atividades = $evento->atividades()
            ->with([
                'municipios.estado',
                'avaliacaoAtividades' => fn($rel) => $rel->when($userId, fn($query) => $query->where('user_id', $userId)),
            ])
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->paginate(12);
        return view('atividades.index', compact('evento', 'atividades'));
    }

    public function create(Evento $evento)
    {
        $this->authorize('atividade.criar');

        $marcadosRaw = request()->query('marcados', []);
        if (is_string($marcadosRaw)) {
            $marcadosRaw = $marcadosRaw === '' ? [] : explode(',', $marcadosRaw);
        }
        if (!is_array($marcadosRaw)) {
            $marcadosRaw = [];
        }

        $marcadosPlanejamento = $this->normalizarChecklistIndices($marcadosRaw);

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

        return view('atividades.create', compact('evento', 'municipios', 'atividadesCopiaveis', 'marcadosPlanejamento'));
    }

    public function saveChecklist(Request $request, Atividade $atividade)
    {
        $request->validate([
            'tipo'   => 'required|in:planejamento,encerramento',
            'itens'  => 'nullable|array',
            'itens.*'=> 'integer|min:0',
        ]);

        $campo = 'checklist_' . $request->tipo;
        $atividade->$campo = $this->normalizarChecklistIndices($request->input('itens', []));
        $atividade->save();

        return response()->json(['status' => 'ok', 'saved' => $atividade->$campo]);
    }

    public function store(Request $request, Evento $evento)
    {
        $this->authorize('atividade.criar');

        $dados = $request->validate([
            'municipios'          => 'nullable|array',
            'municipios.*'        => 'exists:municipios,id',
            'descricao'           => 'required|string',
            'dia'                 => 'required|date',
            'hora_inicio'         => 'required|date_format:H:i',
            'hora_fim'            => 'required|date_format:H:i|after:hora_inicio',
            'publico_esperado'    => 'nullable|integer|min:0',
            'carga_horaria'       => 'nullable|integer|min:0',
            'copiar_inscritos_de' => 'nullable|exists:atividades,id',
            'checklist_planejamento'      => 'nullable|array',
            'checklist_planejamento.*'    => 'integer|min:0',
            'checklist_encerramento'      => 'nullable|array',
            'checklist_encerramento.*'    => 'integer|min:0',
        ]);

        $copiarDe = $dados['copiar_inscritos_de'] ?? null;
        unset($dados['copiar_inscritos_de']);

        $dados['checklist_planejamento'] = $this->normalizarChecklistIndices($dados['checklist_planejamento'] ?? []);
        $dados['checklist_encerramento'] = $this->normalizarChecklistIndices($dados['checklist_encerramento'] ?? []);

        $municipiosSelecionados = $dados['municipios'] ?? [];
        unset($dados['municipios']);

        // Mantém o campo legado municipio_id preenchido com o primeiro selecionado (para compatibilidade).
        $dados['municipio_id'] = $municipiosSelecionados[0] ?? null;

        $atividade = $evento->atividades()->create($dados);
        $atividade->municipios()->sync($municipiosSelecionados);
        $copiados = $this->copiarInscritos($copiarDe, $atividade);

        return redirect()
        ->route('eventos.show', $evento)
        ->with('success', 'Momento criado com sucesso!');
    }

    public function edit(Atividade $atividade)
    {
        $evento = $atividade->evento;
        $this->authorize('atividade.editar');

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
        $this->authorize('atividade.editar');

        $dados = $request->validate([
            'municipios'          => 'nullable|array',
            'municipios.*'        => 'exists:municipios,id',
            'descricao'           => 'required|string',
            'dia'                 => 'required|date',
            'hora_inicio'         => 'required|date_format:H:i',
            'hora_fim'            => 'required|date_format:H:i|after:hora_inicio',
            'publico_esperado'    => 'nullable|integer|min:0',
            'carga_horaria'       => 'nullable|integer|min:0',
            'copiar_inscritos_de' => 'nullable|exists:atividades,id',
            'checklist_planejamento'      => 'nullable|array',
            'checklist_planejamento.*'    => 'integer|min:0',
            'checklist_encerramento'      => 'nullable|array',
            'checklist_encerramento.*'    => 'integer|min:0',
        ]);

        $copiarDe = $dados['copiar_inscritos_de'] ?? null;
        unset($dados['copiar_inscritos_de']);

        $dados['checklist_planejamento'] = $this->normalizarChecklistIndices($dados['checklist_planejamento'] ?? []);
        $dados['checklist_encerramento'] = $this->normalizarChecklistIndices($dados['checklist_encerramento'] ?? []);

        $municipiosSelecionados = $dados['municipios'] ?? [];
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
        $this->authorize('atividade.excluir');

        $atividade->delete();

        return back()->with('success', 'Momento removida.');
    }

    private function normalizarChecklistIndices(array $itens): array
    {
        return array_values(array_unique(array_map('intval', $itens)));
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
                'ouvinte'         => $inscricao->atividade_id === $atividade->id ? $inscricao->ouvinte : true,
            ]);
            $inscricao->deleted_at = null;
            $inscricao->save();
        } else {
            $inscricao = Inscricao::create([
                'evento_id'       => $evento->id,
                'atividade_id'    => $atividade->id,
                'participante_id' => $participante->id,
                'ouvinte'         => true,
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
                $existente->ouvinte = false;
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
                'ouvinte'         => false,
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

    public function downloadListaPresencaPdf(Request $request, Atividade $atividade)
    {
        $this->authorize('presenca.abrir');

        //o default para evitar erros é assessoria
        $tipoTemplate = $request->query('tipo', 'assessoria');

        //puxa os dados
        $inscritos = $atividade->inscricoes()->with([
            'participante.user',
            'participante.municipio.estado'
        ])->get()->pluck('participante');

        $presentes = $atividade->presencas()->with([
            'inscricao.participante.user',
            'inscricao.participante.municipio.estado'
        ])->get()->pluck('inscricao.participante');

        $participantes = $inscritos->concat($presentes)
            ->filter()
            ->unique('id')
            ->sortBy(function ($participante) {
                $nome = mb_strtolower($participante->user->name ?? '');
                return \Illuminate\Support\Str::ascii($nome);
            })->values();

        try {
            $estrategia = \App\Pdf\ListaDePresenca\ListaPresencaFactory::criar($tipoTemplate);
            $conteudoPdf = $estrategia->gerarPdf($atividade, $participantes);

            $fileName = 'Lista_Presenca_' . Str::slug($atividade->descricao) . '.pdf';

            return response($conteudoPdf, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function downloadListaAutorizacaoImagemPdf(\App\Models\Atividade $atividade)
    {
        $this->authorize('presenca.abrir');

        //logica do codigo alterada para puxar de inscrição e presença
        $inscritos = $atividade->inscricoes()->with([
            'participante.user',
            'participante.municipio.estado'
        ])->get()->pluck('participante');

        $presentes = $atividade->presencas()->with([
            'inscricao.participante.user',
            'inscricao.participante.municipio.estado'
        ])->get()->pluck('inscricao.participante');

        //unifica e ordena as incrições e presenças, tirando duplicações por id
        $participantes = $inscritos->concat($presentes)
            ->filter()
            ->unique('id')
            ->sortBy(function ($participante) {
                $nome = mb_strtolower($participante->user->name ?? '');
                return \Illuminate\Support\Str::ascii($nome);
            })->values();

        $templatePath = storage_path('app/templates/base_lista_autorizacao.pdf');

        if (!file_exists($templatePath)) {
            return back()->with('error', 'O template base em PDF não foi encontrado.');
        }

        $pdf = new \App\Pdf\AutorizacaoDeImagem\ListaAutorizacaoImagem();
        $pdf->setBaseTemplate($templatePath);

        $pdf->SetMargins(10, 10, 10);
        $pdf->SetAutoPageBreak(true, 30);

        $pdf->AddPage();

        $pdf->SetFont('Helvetica', '', 8);

        $contador = 1;

        if ($participantes->isEmpty()) {
            $pdf->Cell(190, 8, utf8_decode('Nenhum participante inscrito neste momento.'), 1, 1, 'C');
        } else {
            foreach ($participantes as $participante) {
                $user = $participante->user;

                $nome = utf8_decode(substr($user->name ?? '', 0, 50));
                while ($pdf->GetStringWidth($nome) > 89) {
                    $nome = substr($nome, 0, -1);
                }

                //formatando o CPF
                $cpfSujo = $inscricao->participante->cpf ?? '';
                $cpfLimpo = preg_replace('/[^0-9]/', '', $cpfSujo);
                if (strlen($cpfLimpo) === 11) {
                    $cpfFormatado = preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cpfLimpo);
                } else {
                    $cpfFormatado = $cpfSujo ?: '';
                }
                $cpf = utf8_decode($cpfFormatado);


                $pdf->Cell(8, 8, $contador++, 1, 0, 'C');
                $pdf->Cell(90, 8, $nome, 1, 0, 'L');          //nome
                $pdf->Cell(45, 8, $cpf, 1, 0, 'C');           //CPF
                $pdf->Cell(47, 8, '', 1, 1, 'C');         //assinatura (em branco)
            }
        }
        $fileName = 'Lista_Autorizacao_' . \Illuminate\Support\Str::slug($atividade->descricao) . '.pdf';

        return response($pdf->Output('S'), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
