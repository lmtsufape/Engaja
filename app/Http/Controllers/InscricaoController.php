<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Evento;
use App\Models\User;
use App\Models\Participante;
use App\Imports\ParticipantesPreviewImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class InscricaoController extends Controller
{
    public function index()
    { /* ... */
    }

    public function import(Evento $evento)
    {
        return view('inscricoes.import', compact('evento'));
    }

    /**
     * Lê o arquivo e guarda TODAS as linhas na sessão. Redireciona para a prévia paginada.
     */
    public function cadastro(Request $request, Evento $evento)
    {
        $request->validate([
            'your_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        try {
            $import = new ParticipantesPreviewImport();
            \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('your_file'));

            $rows = $import->rows->values()->all();

            $sessionKey = "import_preview_evento_{$evento->id}";
            session([$sessionKey => $rows]);

            return redirect()->route('inscricoes.preview', ['evento' => $evento, 'session_key' => $sessionKey]);
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
        $sessionKey = $request->query('session_key') ?? "import_preview_evento_{$evento->id}";
        $allRows = collect(session($sessionKey, []));

        if ($allRows->isEmpty()) {
            return redirect()->route('inscricoes.import', $evento)
                ->withErrors(['your_file' => 'Sessão de importação vazia/expirada. Envie o arquivo novamente.']);
        }

        $perPage = (int) $request->query('per_page', 50);
        $page    = (int) max(1, $request->query('page', 1));
        $total   = $allRows->count();

        $slice = $allRows->slice(($page - 1) * $perPage, $perPage)->values();

        $rowsPaginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => route('inscricoes.preview', $evento), 'query' => ['session_key' => $sessionKey, 'per_page' => $perPage]]
        );

        $globalOffset = ($page - 1) * $perPage;

        $municipios = \App\Models\Municipio::with('estado')->orderBy('nome')->get(['id', 'nome', 'estado_id']);

        return view('inscricoes.preview', [
            'evento'       => $evento,
            'rows'         => $rowsPaginator,
            'globalOffset' => $globalOffset,
            'sessionKey'   => $sessionKey,
            'municipios'   => $municipios,
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

        $sessionKey = $request->input('session_key');
        $allRows    = collect(session($sessionKey, []));

        if ($allRows->isEmpty()) {
            return back()->withErrors(['rows' => 'Sessão expirada. Reenvie o arquivo.']);
        }

        foreach ($request->input('rows') as $globalIndex => $data) {
            $globalIndex = (int) $globalIndex;
            if ($allRows->has($globalIndex)) {
                $allRows[$globalIndex] = array_merge($allRows[$globalIndex], $data);
            }
        }

        session([$sessionKey => $allRows->values()->all()]);

        $page    = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 50);

        return redirect()->route('inscricoes.preview', [
            'evento'      => $evento,
            'session_key' => $sessionKey,
            'page'        => $page,
            'per_page'    => $perPage,
        ])->with('success', 'Alterações desta página salvas.');
    }

    /**
     * Confirma TUDO: lê as linhas da sessão e grava no banco.
     */
    public function confirmar(Request $request, Evento $evento)
    {
        $request->validate([
            'session_key' => 'required|string',
        ]);

        $sessionKey = $request->input('session_key');
        $rows = collect(session($sessionKey, []));

        if ($rows->isEmpty()) {
            return back()->withErrors(['rows' => 'Sessão de importação vazia/expirada. Reenvie o arquivo.']);
        }

        DB::transaction(function () use ($rows, $evento) {
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

            // helper para normalizar data
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

            foreach ($rows as $row) {
                $email = strtolower(trim((string)($row['email'] ?? '')));
                if (!$email) continue;
                $user = $usersExistentes[$email] ?? null;
                if (!$user) continue;
                $userId = $user->id;

                $dados = [
                    'municipio_id'   => ($row['municipio_id'] ?? null) ?: null,
                    'cpf'            => (($row['cpf'] ?? '') !== '') ? trim((string)$row['cpf']) : null,
                    'telefone'       => (($row['telefone'] ?? '') !== '') ? trim((string)$row['telefone']) : null,
                    'escola_unidade' => (($row['escola_unidade'] ?? '') !== '') ? trim((string)$row['escola_unidade']) : null,
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
                $campos = ['municipio_id', 'cpf', 'telefone', 'escola_unidade', 'data_entrada'];
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

            $ids = array_filter($ids);
            $existingIds = $evento->participantes()->pluck('participante_id')->all();
            $newIds = array_diff($ids, $existingIds);
            $evento->participantes()->attach($newIds);
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
        $perPage     = (int) $request->query('per_page', 50);

        $municipios = \App\Models\Municipio::with('estado')
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id']);

        $inscritos = $evento->participantes()
            ->with([
                'user:id,name,email',
                'municipio.estado:id,nome,sigla',
            ])
            ->wherePivotNull('deleted_at')
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                })->orWhere('cpf', 'ilike', "%{$search}%")
                    ->orWhere('telefone', 'ilike', "%{$search}%")
                    ->orWhereHas('municipio', function ($mq) use ($search) {
                        $mq->where('nome', 'ilike', "%{$search}%");
                    });
            })
            ->when($municipioId, fn($q) => $q->where('municipio_id', $municipioId))
            ->orderByDesc('participantes.id')
            ->paginate($perPage)
            ->appends($request->query());

        return view('inscricoes.index', [
            'evento'      => $evento,
            'inscritos'   => $inscritos,
            'municipios'  => $municipios,
            'search'      => $search,
            'municipioId' => $municipioId,
            'perPage'     => $perPage,
        ]);
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
