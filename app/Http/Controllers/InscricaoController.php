<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;
use App\Models\User;
use App\Models\Participante;
use App\Models\Inscricao;
use App\Imports\ParticipantesPreviewImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Illuminate\Validation\Rule;

class InscricaoController extends Controller
{
    public function index()
    { /* ... */
    }

    public function import(Evento $evento)
    {
        $atividades = $evento->atividades()
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get();

        return view('inscricoes.import', compact('evento', 'atividades'));
    }

    /**
     * Lê o arquivo e guarda TODAS as linhas na sessão. Redireciona para a prévia paginada.
     */
    public function cadastro(Request $request, Evento $evento)
    {
        $validated = $request->validate([
            'your_file'     => 'required|file|mimes:xlsx,xls,csv|max:20480',
            'atividade_id'  => [
                'required',
                'integer',
                Rule::exists('atividades', 'id')->where('evento_id', $evento->id),
            ],
        ]);

        $atividade = $evento->atividades()
            ->whereKey($validated['atividade_id'])
            ->first();

        if (!$atividade) {
            return back()
                ->withErrors(['atividade_id' => 'Momento inválido para este evento.'])
                ->withInput();
        }

        try {
            $import = new ParticipantesPreviewImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('your_file'));

            $rows = $import->rows->values()->all();

            $sessionKey = "import_preview_evento_{$evento->id}_atividade_{$atividade->id}";
            session([$sessionKey => [
                'atividade_id' => $atividade->id,
                'rows'         => $rows,
            ]]);

            return redirect()->route('inscricoes.preview', [
                'evento'       => $evento,
                'session_key'  => $sessionKey,
                'atividade_id' => $atividade->id,
            ]);
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['your_file' => 'Falha ao processar o arquivo: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Mostra a prévia paginada (sem gravar no banco).
     */
    public function preview(Request $request, Evento $evento)
    {
        $sessionKey = $request->query('session_key');
        $sessionPayload = session($sessionKey);

        if (!is_array($sessionPayload) || empty($sessionPayload['rows'] ?? [])) {
            return redirect()->route('inscricoes.import', $evento)
                ->withErrors(['your_file' => 'Sessão de importação vazia/expirada. Envie o arquivo novamente.']);
        }

        $atividadeId = $request->query('atividade_id') ?? ($sessionPayload['atividade_id'] ?? null);

        if (!$atividadeId) {
            return redirect()->route('inscricoes.import', $evento)
                ->withErrors(['atividade_id' => 'Momento da importação não encontrado. Inicie o processo novamente.']);
        }

        $atividade = $evento->atividades()
            ->whereKey($atividadeId)
            ->first();

        if (!$atividade) {
            return redirect()->route('inscricoes.import', $evento)
                ->withErrors(['atividade_id' => 'Momento informado não pertence a este evento.']);
        }

        $allRows = collect($sessionPayload['rows']);

        $perPage = (int) $request->query('per_page', 50);
        $page    = (int) max(1, $request->query('page', 1));
        $total   = $allRows->count();

        $slice = $allRows->slice(($page - 1) * $perPage, $perPage)->values();

        $rowsPaginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path'  => route('inscricoes.preview', $evento),
                'query' => [
                    'session_key'  => $sessionKey,
                    'per_page'     => $perPage,
                    'atividade_id' => $atividade->id,
                ],
            ]
        );

        $globalOffset = ($page - 1) * $perPage;

        $municipios = \App\Models\Municipio::with('estado')->orderBy('nome')->get(['id', 'nome', 'estado_id']);

        $organizacoes = config('engaja.organizacoes', []);
        $participanteTags = config('engaja.participante_tags', Participante::TAGS);

        return view('inscricoes.preview', [
            'evento'           => $evento,
            'atividade'        => $atividade,
            'rows'             => $rowsPaginator,
            'globalOffset'     => $globalOffset,
            'sessionKey'       => $sessionKey,
            'municipios'       => $municipios,
            'organizacoes'     => $organizacoes,
            'participanteTags' => $participanteTags,
        ]);
    }

    /**
     * Salva as edições da PÁGINA ATUAL na sessão.
     */
    public function savePage(Request $request, Evento $evento)
    {
        $request->validate([
            'session_key' => 'required|string',
            'rows'        => 'required|array',
        ]);

        $sessionKey     = $request->input('session_key');
        $sessionPayload = session($sessionKey);

        if (!is_array($sessionPayload) || empty($sessionPayload['rows'] ?? [])) {
            return back()->withErrors(['rows' => 'Sessão expirada. Reenvie o arquivo.']);
        }

        $atividadeId = $sessionPayload['atividade_id'] ?? $request->input('atividade_id');
        if (!$atividadeId) {
            return back()->withErrors(['atividade_id' => 'Momento da importação não encontrado. Inicie novamente.']);
        }

        $allRows = collect($sessionPayload['rows']);

        foreach ($request->input('rows') as $globalIndex => $data) {
            $globalIndex = (int) $globalIndex;
            if ($allRows->has($globalIndex)) {
                $allRows[$globalIndex] = array_merge($allRows[$globalIndex], $data);
            }
        }

        session([
            $sessionKey => [
                'atividade_id' => $atividadeId,
                'rows'         => $allRows->values()->all(),
            ],
        ]);

        $page    = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 50);

        return redirect()->route('inscricoes.preview', [
            'evento'       => $evento,
            'session_key'  => $sessionKey,
            'page'         => $page,
            'per_page'     => $perPage,
            'atividade_id' => $atividadeId,
        ])->with('success', 'Alterações desta página salvas.');
    }
/**
     * Confirma TUDO: lê as linhas da sessão e grava no banco.
     */
    public function confirmar(Request $request, Evento $evento)
    {
        $validated = $request->validate([
            'session_key'  => 'required|string',
            'atividade_id' => [
                'required',
                'integer',
                Rule::exists('atividades', 'id')->where('evento_id', $evento->id),
            ],
        ]);

        $sessionKey     = $validated['session_key'];
        $sessionPayload = session($sessionKey);

        if (!is_array($sessionPayload) || empty($sessionPayload['rows'] ?? [])) {
            return back()->withErrors(['rows' => 'Sessão de importação vazia/expirada. Reenvie o arquivo.']);
        }

        $atividadeId = $validated['atividade_id'];
        $sessionAtividade = $sessionPayload['atividade_id'] ?? null;

        if ($sessionAtividade && (int) $sessionAtividade !== (int) $atividadeId) {
            return back()->withErrors(['atividade_id' => 'Momento informado não corresponde ao processo em andamento. Refaça a importação.']);
        }

        $atividade = $evento->atividades()
            ->whereKey($atividadeId)
            ->first();

        if (!$atividade) {
            return back()->withErrors(['atividade_id' => 'Momento informado não pertence a este evento.']);
        }

        $rows = collect($sessionPayload['rows']);

        DB::transaction(function () use ($rows, $evento, $atividade) {
            $ids = [];

            $emails = collect($rows)->pluck('email')->map(fn($e) => strtolower(trim((string)$e)))->unique()->filter()->values();
            $usersExistentes = User::whereIn('email', $emails)->get()->keyBy(fn($u) => strtolower($u->email));

            $novosUsuarios = [];
            foreach ($rows as $row) {
                $email = strtolower(trim((string)($row['email'] ?? '')));
                if (!$email || $usersExistentes->has($email)) continue;
                $name  = trim((string)($row['nome'] ?? ''));
                $novosUsuarios[] = [
                    'email'    => $email,
                    'name'     => $name !== '' ? $name : ($row['cpf'] ?? 'Participante'),
                    'password' => Hash::make(Str::random(12)),
                ];
            }
            if (count($novosUsuarios)) {
                User::insert($novosUsuarios);
                $usersExistentes = User::whereIn('email', $emails)->get()->keyBy(fn($u) => strtolower($u->email));
            }

            $userIds = $usersExistentes->pluck('id')->values();
            $participantesExistentes = Participante::whereIn('user_id', $userIds)->get()->keyBy('user_id');

            $novosParticipantes = [];
            $atualizacoes = [];

            $toDate = function ($raw) {
                if ($raw === null) return null;
                $s = trim((string)$raw);
                if ($s === '') return null;
                try {
                    if (is_numeric($s)) {
                        return Carbon::instance(ExcelDate::excelToDateTimeObject($s))->format('Y-m-d');
                    }
                    if (preg_match('~^\d{2}/\d{2}/\d{4}$~', $s)) {
                        return Carbon::createFromFormat('d/m/Y', $s)->format('Y-m-d');
                    }
                    return Carbon::parse($s)->format('Y-m-d');
                } catch (\Throwable $e) {
                    return null;
                }
            };

            $tagOptions = config('engaja.participante_tags', Participante::TAGS);
            $tagLookup = array_fill_keys($tagOptions, true);

            foreach ($rows as $row) {
                $email = strtolower(trim((string)($row['email'] ?? '')));
                if (!$email) continue;
                $user = $usersExistentes[$email] ?? null;
                if (!$user) continue;
                $userId = $user->id;

                $orgRaw = ($row['organizacao'] ?? $row['escola_unidade'] ?? null);
                $org    = is_string($orgRaw) ? trim($orgRaw) : null;

                $tagRaw = $row['tag'] ?? null;
                $tag    = is_string($tagRaw) ? trim($tagRaw) : null;
                if ($tag === '') {
                    $tag = null;
                } elseif (!isset($tagLookup[$tag])) {
                    $tag = null;
                }

                $dados = [
                    'municipio_id'   => ($row['municipio_id'] ?? null) ?: null,
                    'cpf'            => (($row['cpf'] ?? '') !== '') ? trim((string)$row['cpf']) : null,
                    'telefone'       => (($row['telefone'] ?? '') !== '') ? trim((string)$row['telefone']) : null,
                    'escola_unidade' => ($org !== '') ? $org : null,
                    'tag'            => $tag,
                    'data_entrada'   => $toDate($row['data_entrada'] ?? null),
                ];

                if ($participantesExistentes->has($userId)) {
                    $atualizacoes[] = ['user_id' => $userId] + $dados;
                    $ids[] = $participantesExistentes[$userId]->id;
                } else {
                    $dados['user_id']    = $userId;
                    $dados['created_at'] = now();
                    $novosParticipantes[] = $dados;
                }
            }

            if (count($novosParticipantes)) {
                Participante::insert($novosParticipantes);
                $participantesExistentes = Participante::whereIn('user_id', $userIds)->get()->keyBy('user_id');
                foreach ($novosParticipantes as $np) {
                    $ids[] = $participantesExistentes[$np['user_id']]->id ?? null;
                }
            }

            if (count($atualizacoes)) {
                $idsToUpdate = array_column($atualizacoes, 'user_id');
                $campos = ['municipio_id', 'cpf', 'telefone', 'escola_unidade', 'tag', 'data_entrada'];
                $cases = [];
                foreach ($campos as $field) {
                    $sql = "$field = CASE user_id\n";
                    foreach ($atualizacoes as $upd) {
                        $value = $upd[$field] === null ? 'NULL' : DB::getPdo()->quote($upd[$field]);
                        $sql .= "WHEN {$upd['user_id']} THEN $value\n";
                    }
                    $sql .= "ELSE $field END";
                    $cases[] = $sql;
                }
                $setSql = implode(",\n", $cases);
                $idsStr = implode(',', $idsToUpdate);
                DB::statement("UPDATE participantes SET $setSql WHERE user_id IN ($idsStr)");
            }

            $participanteIds = collect($ids)->filter()->unique()->values();

            foreach ($participanteIds as $participanteId) {
                $inscricao = Inscricao::withTrashed()
                    ->where('participante_id', $participanteId)
                    ->where('atividade_id', $atividade->id)
                    ->first();

                if (!$inscricao) {
                    $inscricao = Inscricao::withTrashed()
                        ->where('participante_id', $participanteId)
                        ->where('evento_id', $evento->id)
                        ->whereNull('atividade_id')
                        ->first();
                }

                if ($inscricao) {
                    $inscricao->fill([
                        'evento_id'       => $evento->id,
                        'atividade_id'    => $atividade->id,
                        'participante_id' => $participanteId,
                    ]);
                    $inscricao->deleted_at = null;
                    $inscricao->save();
                } else {
                    Inscricao::create([
                        'evento_id'       => $evento->id,
                        'atividade_id'    => $atividade->id,
                        'participante_id' => $participanteId,
                    ]);
                }
            }
        });

        session()->forget($sessionKey);

        return redirect()
            ->route('eventos.show', $evento)
            ->with('success', 'Importação confirmada e salva com sucesso!');
    }
    public function inscritos(Request $request, Evento $evento)
    {
        $search      = $request->query('q');
        $municipioId = $request->query('municipio_id');
        $atividadeId = $request->query('atividade_id');
        $perPage     = (int) $request->query('per_page', 50);

        $municipios = \App\Models\Municipio::with('estado')
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id']);

        $atividades = $evento->atividades()
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get(['id', 'descricao', 'dia', 'hora_inicio']);

        $inscricoesQuery = Inscricao::query()
            ->with([
                'participante.user:id,name,email',
                'participante.municipio.estado:id,nome,sigla',
                'atividade:id,descricao,dia,hora_inicio',
            ])
            ->where('evento_id', $evento->id)
            ->whereNull('deleted_at')
            ->when($atividadeId, fn($q) => $q->where('atividade_id', $atividadeId))
            ->when($municipioId, fn($q) => $q->whereHas('participante', fn($pq) => $pq->where('municipio_id', $municipioId)))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $like = "%{$search}%";
                    $w->whereHas('participante.user', function ($uq) use ($like) {
                        $uq->where('name', 'ilike', $like)
                            ->orWhere('email', 'ilike', $like);
                    })
                    ->orWhereHas('participante', function ($pq) use ($like) {
                        $pq->where('cpf', 'ilike', $like)
                            ->orWhere('telefone', 'ilike', $like);
                    })
                    ->orWhereHas('participante.municipio', function ($mq) use ($like) {
                        $mq->where('nome', 'ilike', $like);
                    });
                });
            })
            ->orderByDesc('id');

        $inscricoes = $inscricoesQuery
            ->paginate($perPage)
            ->appends($request->query());

        return view('inscricoes.index', [
            'evento'       => $evento,
            'inscricoes'   => $inscricoes,
            'municipios'   => $municipios,
            'atividades'   => $atividades,
            'search'       => $search,
            'municipioId'  => $municipioId,
            'atividadeId'  => $atividadeId,
            'perPage'      => $perPage,
        ]);
    }


    public function inscrever(Request $request, \App\Models\Evento $evento)
    {
        $user = $request->user();
        $participante = $user->participante; // já existe pelo booted()

        // existe ativo?
        $exists = \DB::table('inscricaos')
            ->where('evento_id', $evento->id)
            ->where('participante_id', $participante->id)
            ->whereNull('deleted_at')
            ->exists();

        if ($exists) {
            return back()->with('info', 'Você já está inscrito neste evento.');
        }

        // já existiu (soft-deletada)? restaura
        $restored = \DB::table('inscricaos')
            ->where('evento_id', $evento->id)
            ->where('participante_id', $participante->id)
            ->whereNotNull('deleted_at')
            ->update(['deleted_at' => null, 'updated_at' => now()]);

        if ($restored) {
            return back()->with('success', 'Inscrição reativada com sucesso!');
        }

        // cria nova
        \DB::table('inscricaos')->insert([
            'evento_id'       => $evento->id,
            'participante_id' => $participante->id,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return back()->with('success', 'Inscrição realizada com sucesso!');
    }

    public function cancelar(Request $request, Evento $evento)
    {
        $user = $request->user();
        $participanteId = optional($user->participante)->id;

        if (!$participanteId) {
            return back()->with('error', 'Você não possui cadastro de participante.');
        }

        $affected = DB::table('inscricaos')
            ->where('evento_id', $evento->id)
            ->where('participante_id', $participanteId)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);

        if ($affected) {
            return back()->with('success', 'Inscrição cancelada.');
        }

        return back()->with('info', 'Você não está inscrito neste evento.');
    }

    public function create()
    { /* ... */
    }
    public function store(Request $request)
    { /* ... */
    }
    public function show(string $id)
    { /* ... */
    }
    public function edit(string $id)
    { /* ... */
    }
    public function update(Request $request, string $id)
    { /* ... */
    }
    public function destroy(string $id)
    { /* ... */
    }
}



