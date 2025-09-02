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

            // array indexado 0..N (cada item é um array com as colunas)
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

        // usamos LengthAwarePaginator para ter links de paginação
        $rowsPaginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            ['path' => route('inscricoes.preview', $evento), 'query' => ['session_key' => $sessionKey, 'per_page' => $perPage]]
        );

        // importante: passamos também o "offset" para manter os índices globais no form
        $globalOffset = ($page - 1) * $perPage;

        // lista de municípios (select)
        $municipios = \App\Models\Municipio::with('estado')->orderBy('nome')->get(['id', 'nome', 'estado_id']);

        return view('inscricoes.preview', [
            'evento'       => $evento,
            'rows'         => $rowsPaginator, // paginator
            'globalOffset' => $globalOffset,
            'sessionKey'   => $sessionKey,
            'municipios'   => $municipios,
        ]);
    }

    /**
     * Salva as edições da PÁGINA ATUAL na sessão e volta para a prévia (pode mudar de página em seguida).
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

        // rows vem no formato rows[<global_index>] = [...campos...]
        foreach ($request->input('rows') as $globalIndex => $data) {
            $globalIndex = (int) $globalIndex;
            if ($allRows->has($globalIndex)) {
                // substitui a linha na sessão pela edição do usuário
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
     * Confirma TUDO: lê as linhas da SESSÃO (todas as páginas), grava no banco e vincula ao evento.
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

            foreach ($rows as $row) {
                $email = strtolower(trim((string)($row['email'] ?? '')));
                $name  = trim((string)($row['nome'] ?? ''));

                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name'     => $name !== '' ? $name : ($row['cpf'] ?? 'Participante'),
                        'password' => Hash::make(Str::random(12)),
                    ]
                );

                $participante = Participante::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'municipio_id'   => $row['municipio_id'] ?? null,
                        'cpf'            => $row['cpf'] ?? null,
                        'telefone'       => $row['telefone'] ?? null,
                        'escola_unidade' => $row['escola_unidade'] ?? null,
                        'data_entrada'   => $row['data_entrada'] ?? null,
                    ]
                );

                $ids[] = $participante->id;
            }

            $evento->participantes()->whereNotIn('id', $ids)->syncWithoutDetaching($ids);
        });

        session()->forget($sessionKey);

        return redirect()
            ->route('eventos.show', $evento)
            ->with('success', 'Importação confirmada e salva com sucesso!');
    }

    public function inscritos(Request $request, \App\Models\Evento $evento)
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
            'evento'     => $evento,
            'inscritos'  => $inscritos,
            'municipios' => $municipios,
            'search'     => $search,
            'municipioId' => $municipioId,
            'perPage'    => $perPage,
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
