<?php

namespace App\Http\Controllers;

use App\Exports\EventoParticipantesGeralExport;
use App\Exports\EventoParticipantesPorMomentoExport;
use App\Models\Evento;
use App\Models\Eixo;
use App\Models\MatrizAprendizagem;
use App\Models\SituacaoDesafiadora;
use App\Models\User;
use App\Models\Participante;
use App\Models\Atividade;
use App\Models\Inscricao;
use App\Models\ModeloCertificado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Imports\ParticipantesImport;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\CadastroParticipanteStoreRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Presenca;

class EventoController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $r)
    {
        $eventos = Evento::with(['user'])
            ->when($r->q, function ($q) use ($r) {
                $search = mb_strtolower($r->q);
                $q->where(function ($w) use ($search) {
                    $w->whereRaw('LOWER(nome) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(tipo) LIKE ?', ['%' . $search . '%'])
                    ->orWhereRaw('LOWER(objetivos_gerais) LIKE ?', ['%' . $search . '%']);
                });
            })
            ->when($r->acao_geral, fn($q) => $q->where('acao_geral', $r->acao_geral))
            ->when($r->de, fn($q) => $q->whereDate('data_inicio', '>=', $r->de))
            ->orderByDesc('id')
            ->paginate(10);

        $modelosCertificados = ModeloCertificado::orderBy('nome')->get();

        return view('eventos.index', compact('eventos', 'modelosCertificados'));
    }

    public function create()
    {
        $this->authorize('create', Evento::class);

        $matrizes  = MatrizAprendizagem::orderBy('nome')->get();
        $situacoes = SituacaoDesafiadora::orderBy('categoria')->orderBy('nome')->get()
                        ->groupBy('categoria');

        return view('eventos.create', compact('matrizes', 'situacoes'));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Evento::class);

        $request->validate([
            'nome'                        => 'required|string|max:255',
            'acao_geral'                  => 'required|string|in:1,2,3',
            'subacao'                     => 'required|string|max:200',
            'eixo_id'                     => 'nullable|exists:eixos,id',
            'link'                        => 'nullable|url',
            'data_inicio'                 => 'nullable|date',
            'data_fim'                    => 'nullable|date|after_or_equal:data_inicio',
            'local'                       => 'nullable|string|max:255',
            'imagem'                      => 'nullable|mimes:jpg,jpeg,png,webp,avif,svg|max:2048',
            'matrizes'                    => 'nullable|array',
            'matrizes.*'                  => 'exists:matrizes_aprendizagem,id',
            'situacoes_desafiadoras'      => 'nullable|array',
            'situacoes_desafiadoras.*'    => 'exists:situacoes_desafiadoras,id',
            'sequencias'                  => 'nullable|array',
            'sequencias.*.periodo'        => 'nullable|string|max:255',
            'sequencias.*.descricao'      => 'nullable|string',
            'ods_selecionados'            => 'nullable|array',
            'ods_selecionados.*'          => 'string|max:500',
            'checklist_planejamento'      => 'nullable|array',
            'checklist_planejamento.*'    => 'string',
        ]);

        $dados = $request->only([
            'eixo_id', 'acao_geral', 'subacao',
            'nome', 'tipo', 'data_inicio', 'data_fim', 'modalidade', 'link', 'local',
            'objetivos_gerais', 'objetivos_especificos',
            'recursos_materiais_necessarios', 'providencias_sme_parceria', 'observacoes_complementares',
        ]);
        $dados['user_id']                = Auth::id();
        $dados['ods_selecionados']       = $request->input('ods_selecionados', []);
        $dados['checklist_planejamento'] = $request->input('checklist_planejamento', []);

        if ($request->hasFile('imagem')) {
            $dados['imagem'] = $request->file('imagem')->store('eventos', 'public');
        }

        $evento = Evento::create($dados);

        $evento->matrizes()->sync($request->input('matrizes', []));
        $evento->situacoesDesafiadoras()->sync($request->input('situacoes_desafiadoras', []));
        $this->syncSequencias($evento, $request->input('sequencias', []));

        return redirect()->route('eventos.index')
            ->with('success', 'Ação pedagógica criada com sucesso!');
    }


    public function show(Evento $evento)
    {
        $evento->load([
            'eixo',
            'user',
            'atividades' => fn($q) => $q
                ->with(['municipios.estado', 'avaliacaoAtividades', 'avaliacoes'])
                ->orderBy('dia')
                ->orderBy('hora_inicio'),
        ]);

        $atividades = $evento->atividades;

        // Para utilizadores participantes: carrega as presenças por atividade
        $presencasPorAtividade = collect();
        $participante = auth()->user()?->participante;
        if ($participante) {
            $inscricaoIds = Inscricao::where('participante_id', $participante->id)
                ->where('evento_id', $evento->id)
                ->whereNull('deleted_at')
                ->pluck('id');

            if ($inscricaoIds->isNotEmpty()) {
                $presencasPorAtividade = Presenca::whereIn('inscricao_id', $inscricaoIds)
                    ->whereIn('atividade_id', $atividades->pluck('id'))
                    ->get()
                    ->keyBy('atividade_id');
            }
        }

        return view('eventos.show', compact('evento', 'atividades', 'presencasPorAtividade'));
    }

    public function relatorios(Request $request, Evento $evento)
    {
        $user = $request->user();

        if (!$user || !$user->hasAnyRole(['administrador', 'gerente', 'eq_pedagogica', 'articulador'])) {
            abort(403, 'Ação não autorizada.');
        }

        $tipo = $request->get('tipo', 'geral');
        $semOuvintes = $request->boolean('sem_ouvintes');
        $slug = Str::slug($evento->nome ?? 'acao-pedagogica');
        $sufixo = $semOuvintes ? '-sem-ouvintes' : '';

        if ($tipo === 'momentos') {
            $nomeArquivo = $slug.'-participantes-por-momento'.$sufixo.'.xlsx';
            return Excel::download(new EventoParticipantesPorMomentoExport($evento, $semOuvintes), $nomeArquivo);
        }

        $nomeArquivo = $slug.'-participantes-geral'.$sufixo.'.xlsx';
        return Excel::download(new EventoParticipantesGeralExport($evento, $semOuvintes), $nomeArquivo);
    }

    public function edit(Evento $evento)
    {
        $this->authorize('update', $evento);

        $evento->load(['matrizes', 'situacoesDesafiadoras', 'sequenciasDidaticas']);

        $matrizes  = MatrizAprendizagem::orderBy('nome')->get();
        $situacoes = SituacaoDesafiadora::orderBy('categoria')->orderBy('nome')->get()
                        ->groupBy('categoria');

        return view('eventos.edit', compact('evento', 'matrizes', 'situacoes'));
    }

    public function update(Request $request, Evento $evento)
    {
        $this->authorize('update', $evento);

        $request->validate([
            'nome'                        => 'required|string|max:255',
            'acao_geral'                  => 'required|string|in:1,2,3',
            'subacao'                     => 'required|string|max:200',
            'eixo_id'                     => 'nullable|exists:eixos,id',
            'link'                        => 'nullable|url',
            'data_inicio'                 => 'nullable|date',
            'data_fim'                    => 'nullable|date|after_or_equal:data_inicio',
            'local'                       => 'nullable|string|max:255',
            'imagem'                      => 'nullable|mimes:jpg,jpeg,png,webp,avif,svg|max:2048',
            'matrizes'                    => 'nullable|array',
            'matrizes.*'                  => 'exists:matrizes_aprendizagem,id',
            'situacoes_desafiadoras'      => 'nullable|array',
            'situacoes_desafiadoras.*'    => 'exists:situacoes_desafiadoras,id',
            'sequencias'                  => 'nullable|array',
            'sequencias.*.periodo'        => 'nullable|string|max:255',
            'sequencias.*.descricao'      => 'nullable|string',
            'ods_selecionados'            => 'nullable|array',
            'ods_selecionados.*'          => 'string|max:500',
            'checklist_planejamento'      => 'nullable|array',
            'checklist_planejamento.*'    => 'string',
        ]);

        $evento->fill($request->only([
            'eixo_id', 'acao_geral', 'subacao',
            'nome', 'tipo', 'data_inicio', 'data_fim', 'modalidade', 'link', 'local',
            'objetivos_gerais', 'objetivos_especificos',
            'recursos_materiais_necessarios', 'providencias_sme_parceria', 'observacoes_complementares',
        ]));

        $evento->ods_selecionados       = $request->input('ods_selecionados', []);
        $evento->checklist_planejamento = $request->input('checklist_planejamento', []);

        if ($request->hasFile('imagem')) {
            if ($evento->imagem) {
                Storage::disk('public')->delete($evento->imagem);
            }
            $evento->imagem = $request->file('imagem')->store('eventos', 'public');
        }

        $evento->save();

        $evento->matrizes()->sync($request->input('matrizes', []));
        $evento->situacoesDesafiadoras()->sync($request->input('situacoes_desafiadoras', []));
        $this->syncSequencias($evento, $request->input('sequencias', []));

        return redirect()->route('eventos.index')
            ->with('success', 'Ação pedagógica atualizada com sucesso!');
    }

    public function destroy(Evento $evento)
    {
        $this->authorize('delete', $evento);

        if ($evento->imagem) {
            Storage::disk('public')->delete($evento->imagem);
        }

        $evento->delete();
        return redirect()->route('eventos.index')->with('success', 'Evento excluído.');
    }

    public function gerarPdfPlanejamento(Evento $evento): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('update', $evento);

        $evento->load(['situacoesDesafiadoras', 'matrizes', 'sequenciasDidaticas']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('eventos.planejamento_pdf', [
                'evento'         => $evento,
                'acoesGerais'    => Evento::ACOES_GERAIS,
                'checklistItems' => array_values(Evento::CHECKLIST_PLANEJAMENTO_ITEMS),
            ])
            ->setPaper('a4', 'portrait');

        return $pdf->download('planejamento-' . Str::slug($evento->nome) . '.pdf');
    }

    public function relatorioParticipantesUnicos(Request $request, Evento $evento)
    {
        $presencas = Presenca::with(['inscricao.participante.user', 'atividade'])
            ->where('status', 'presente')
            ->whereHas('atividade', fn ($q) => $q->where('evento_id', $evento->id))
            ->get();

        $grupo = $presencas->groupBy(fn ($p) => $p->inscricao->participante->id ?? 0);

        $rows = $grupo->map(function ($lista) use ($evento) {
            $p = $lista->first()->inscricao?->participante;
            $user = $p?->user;
            $carga = $lista->sum(fn ($item) => (float) ($item->atividade?->carga_horaria ?? 0));
            $mun = $p?->municipio;
            $estado = $mun?->estado;
            $municipioFmt = $mun && $estado ? "{$mun->nome} - {$estado->sigla}" : ($mun->nome ?? '-');
            return [
                'Nome'                => $user->name ?? '-',
                'Email'               => $user->email ?? '-',
                'CPF'                 => $p->cpf ?? '-',
                'Telefone'            => $p->telefone ?? '-',
                'Escola/Unidade'      => $p->escola_unidade ?? '-',
                'Tipo organização'    => $p->tipo_organizacao ?? '-',
                'Município'           => $municipioFmt ?? '-',
                'Região'              => $estado->regiao ?? '-',
                'Tag'                 => $p->tag ?? '-',
                'Status'              => ($lista->contains(fn ($item) => $item->inscricao?->ouvinte)) ? 'Ouvinte' : 'Presente',
                'Ação pedagógica'     => $evento->nome,
                'Carga horária total' => $carga,
            ];
        })->values();

        $export = new class($rows) implements FromCollection, WithHeadings {
            private $rows;
            public function __construct($rows) { $this->rows = $rows; }
            public function collection() { return collect($this->rows); }
            public function headings(): array
            {
                return [
                    'Nome', 'Email', 'CPF', 'Telefone', 'Escola/Unidade',
                    'Tipo organização', 'Município', 'Região', 'Tag', 'Status',
                    'Ação pedagógica', 'Carga horária total',
                ];
            }
        };

        $nomeArquivo = 'relatorio-participantes-unicos-'.$evento->id.'.xlsx';
        return Excel::download($export, $nomeArquivo);
    }

    public function relatorioParticipantesPorMomento(Request $request, Evento $evento)
    {
        $presencas = Presenca::with(['inscricao.participante.user', 'atividade'])
            ->where('status', 'presente')
            ->whereHas('atividade', fn ($q) => $q->where('evento_id', $evento->id))
            ->get();

        $rows = $presencas->map(function ($p) use ($evento) {
            $participante = $p->inscricao?->participante;
            $user = $participante?->user;
            $atv  = $p->atividade;
            $titulo = $atv->titulo ?? $atv->descricao ?? 'Momento';
            $dia   = $atv->dia ? Carbon::parse($atv->dia)->format('d/m/Y') : '-';
            $hora  = $atv->hora_inicio ? Carbon::parse($atv->hora_inicio)->format('H:i') : '-';
            $mun = $participante?->municipio;
            $estado = $mun?->estado;
            $municipioFmt = $mun && $estado ? "{$mun->nome} - {$estado->sigla}" : ($mun->nome ?? '-');
            return [
                'Nome'                     => $user->name ?? '-',
                'Email'                    => $user->email ?? '-',
                'CPF'                      => $participante->cpf ?? '-',
                'Telefone'                 => $participante->telefone ?? '-',
                'Escola/Unidade'           => $participante->escola_unidade ?? '-',
                'Tipo organização'         => $participante->tipo_organizacao ?? '-',
                'Município'                => $municipioFmt ?? '-',
                'Região'                   => $estado->regiao ?? '-',
                'Tag'                      => $participante->tag ?? '-',
                'Status'                   => ($p->inscricao?->ouvinte ?? false) ? 'Ouvinte' : 'Presente',
                'Ação pedagógica'          => $evento->nome,
                'Momento'                  => $titulo,
                'Dia'                      => $dia,
                'Hora início'              => $hora,
                'Carga horária do momento' => (float) ($atv->carga_horaria ?? 0),
            ];
        })->values();

        $export = new class($rows) implements FromCollection, WithHeadings {
            private $rows;
            public function __construct($rows) { $this->rows = $rows; }
            public function collection() { return collect($this->rows); }
            public function headings(): array
            {
                return [
                    'Nome', 'Email', 'CPF', 'Telefone', 'Escola/Unidade',
                    'Tipo organização', 'Município', 'Região', 'Tag', 'Status',
                    'Ação pedagógica', 'Momento', 'Dia', 'Hora início',
                    'Carga horária do momento',
                ];
            }
        };

        $nomeArquivo = 'relatorio-participantes-momentos-'.$evento->id.'.xlsx';
        return Excel::download($export, $nomeArquivo);
    }

    public function cadastro_inscricao($evento_id, $atividade_id)
    {
        $evento = Evento::findOrFail($evento_id);
        $atividade = Atividade::findOrFail($atividade_id);

        $municipios = \App\Models\Municipio::with('estado')
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id']);

        $participanteTags = config('engaja.participante_tags', Participante::TAGS);

        return view('auth.cadastro-participante', compact('evento', 'municipios', 'atividade', 'participanteTags'));
    }

    public function store_cadastro_inscricao(CadastroParticipanteStoreRequest $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . \App\Models\User::class],
        ]);

        $data = $request->validated();

        $evento = Evento::findOrFail($request->evento_id);
        $atividade = Atividade::findOrFail($request->atividade_id);

        try {
            DB::beginTransaction();

            $user = \App\Models\User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(8)),
            ]);

            $user->assignRole('participante');

            $participanteData = [
                'cpf'              => $data['cpf']       ?? null,
                'telefone'         => $data['telefone']  ?? null,
                'municipio_id'     => $data['municipio_id'] ?? null,
                'escola_unidade'   => $data['escola_unidade'] ?? null,
                'tipo_organizacao' => $data['tipo_organizacao'] ?? null,
                'tag'              => $data['tag']       ?? null,
            ];

            $user->participante()->updateOrCreate(
                ['user_id' => $user->id],
                $participanteData
            );

            $inscricao = $this->inscricao($user, $evento, $atividade);
            $this->presenca($inscricao, $atividade);

            DB::commit();

            return redirect()->route('presenca.confirmar', $atividade->id)->with('success', 'Cadastro realizado com sucesso! Agora você já pode confirmar sua presença abaixo');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Ocorreu um erro ao tentar realizar o cadastro!');
        }
    }

    public function inscricao(User $user, Evento $evento, Atividade $atividade)
    {
        $participante = $user->participante;

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

        return $inscricao;
    }

    public function presenca(Inscricao $inscricao, Atividade $atividade)
    {
        $atividade->presencas()->updateOrCreate(
            ['inscricao_id' => $inscricao->id, 'atividade_id' => $atividade->id],
            ['status' => 'presente']
        );
    }

    private function syncSequencias(Evento $evento, array $sequencias): void
    {
        $evento->sequenciasDidaticas()->delete();

        foreach ($sequencias as $seq) {
            $periodo   = trim($seq['periodo'] ?? '');
            $descricao = trim($seq['descricao'] ?? '');

            if ($periodo !== '' || $descricao !== '') {
                $evento->sequenciasDidaticas()->create([
                    'periodo'   => $periodo,
                    'descricao' => $descricao,
                ]);
            }
        }
    }
}
