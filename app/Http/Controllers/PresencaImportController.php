<?php

namespace App\Http\Controllers;

use App\Imports\PresencasPreviewImport;
use App\Models\Atividade;
use App\Models\User;
use App\Models\Participante;
use App\Models\Municipio;
use App\Models\Presenca;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PresencaImportController extends Controller
{
    public function import(Atividade $atividade)
    {
        $evento = $atividade->evento;
        // $this->authorize('update', $evento); // opcional, remova se não usa policy
        return view('presencas.import', compact('evento', 'atividade'));
    }

    public function cadastro(Request $request, Atividade $atividade)
    {
        $evento = $atividade->evento;
        // $this->authorize('update', $evento);

        $request->validate([
            'your_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        $import = new PresencasPreviewImport();
        \Maatwebsite\Excel\Facades\Excel::import($import, $request->file('your_file'));

        $rows = $import->rows->values()->all();
        $sessionKey = "presenca_import_preview_atividade_{$atividade->id}";
        session([$sessionKey => $rows]);

        return redirect()->route('atividades.presencas.preview', [
            'atividade'   => $atividade,
            'session_key' => $sessionKey,
        ]);
    }

    public function preview(Request $request, Atividade $atividade)
    {
        $evento = $atividade->evento;
        // $this->authorize('update', $evento);

        $sessionKey = $request->query('session_key') ?? "presenca_import_preview_atividade_{$atividade->id}";
        $allRows = collect(session($sessionKey, []));

        if ($allRows->isEmpty()) {
            return redirect()->route('atividades.presencas.import', $atividade)
                ->withErrors(['your_file' => 'Sessão vazia/expirada. Envie o arquivo novamente.']);
        }

        $perPage = (int) $request->query('per_page', 50);
        $page    = (int) max(1, $request->query('page', 1));
        $total   = $allRows->count();

        $slice = $allRows->slice(($page - 1) * $perPage, $perPage)->values();

        $rowsPaginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path'  => route('atividades.presencas.preview', $atividade),
                'query' => ['session_key' => $sessionKey, 'per_page' => $perPage],
            ]
        );

        $globalOffset = ($page - 1) * $perPage;

        $municipios = \App\Models\Municipio::with('estado')
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id']);

        $organizacoes = config('engaja.organizacoes', []);
        $participanteTags = config('engaja.participante_tags', Participante::TAGS);

        return view('presencas.preview', [
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

    public function savePage(Request $request, Atividade $atividade)
    {
        $evento = $atividade->evento;

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
        return back()->with('success', 'Alterações desta página salvas.');
    }

    public function confirmar(Request $request, Atividade $atividade)
    {
        $evento = $atividade->evento;
        // $this->authorize('update', $evento);

        $request->validate([
            'session_key' => 'required|string',
        ]);

        $sessionKey = $request->input('session_key');
        $rows = collect(session($sessionKey, []));

        if ($rows->isEmpty()) {
            return back()->withErrors(['rows' => 'Sessão vazia/expirada. Reenvie o arquivo.']);
        }

        DB::transaction(function () use ($rows, $evento, $atividade) {
            $munCache = Municipio::pluck('id', 'nome')
                ->mapWithKeys(fn($id, $nome) => [mb_strtolower(trim($nome)) => $id])
                ->toArray();

            $tagOptions = config('engaja.participante_tags', Participante::TAGS);
            $tagLookup = array_fill_keys($tagOptions, true);

            foreach ($rows as $row) {
                $email = strtolower(trim((string)($row['email'] ?? '')));
                $nome  = trim((string)($row['nome']  ?? ''));
                $cpf   = $row['cpf'] ?? null;
                $tel   = $row['telefone'] ?? null;

                if ($nome === '' && $email === '' && $cpf === null && $tel === null) {
                    continue; // não cria user/inscrição pra linha vazia
                }

                // agora aceita tanto "organizacao" quanto "escola_unidade"
                $org   = $row['organizacao'] ?? $row['escola_unidade'] ?? null;

                $munId = null;
                if (!empty($row['municipio'])) {
                    $key = mb_strtolower(trim($row['municipio']));
                    $munId = $munCache[$key] ?? null;
                }

                // 1) User
                $user = $email
                    ? User::firstOrCreate(
                        ['email' => $email],
                        [
                            'name' => $nome !== '' ? $nome : ($cpf ?: 'Participante'),
                            'password' => Hash::make(Str::random(12))
                        ]
                    )
                    : User::firstOrCreate(
                        ['email' => Str::uuid() . '@placeholder.local'],
                        [
                            'name' => $nome !== '' ? $nome : ($cpf ?: 'Participante'),
                            'password' => Hash::make(Str::random(12))
                        ]
                    );

                // 2) Participante
                $tag = isset($row['tag']) ? trim((string)$row['tag']) : null;
                if ($tag === '') {
                    $tag = null;
                } elseif (!isset($tagLookup[$tag])) {
                    $tag = null;
                }

                $participante = Participante::firstOrCreate(['user_id' => $user->id], []);
                $participante->fill([
                    'municipio_id'   => $munId,
                    'cpf'            => $cpf ?: null,
                    'telefone'       => $tel ?: null,
                    'escola_unidade' => $org ?: null,   // grava a organização
                    'tag'            => $tag,
                    'data_entrada'   => $row['data_entrada'] ?? null,
                ])->save();

                // 3) Inscrição no evento
                $inscricao = DB::table('inscricaos')
                    ->where('evento_id', $evento->id)
                    ->where('participante_id', $participante->id)
                    ->first();

                if (!$inscricao) {
                    DB::table('inscricaos')->insert([
                        'evento_id'       => $evento->id,
                        'participante_id' => $participante->id,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                    $inscricaoId = DB::table('inscricaos')
                        ->where('evento_id', $evento->id)
                        ->where('participante_id', $participante->id)
                        ->value('id');
                } else {
                    if ($inscricao->deleted_at !== null) {
                        DB::table('inscricaos')->where('id', $inscricao->id)
                            ->update(['deleted_at' => null, 'updated_at' => now()]);
                    }
                    $inscricaoId = $inscricao->id;
                }

                // 4) Presença
                $status = $row['status'] ?? null;
                $just   = $row['justificativa'] ?? null;

                Presenca::updateOrCreate(
                    ['inscricao_id' => $inscricaoId, 'atividade_id' => $atividade->id],
                    ['status_participacao' => $status, 'justificativa' => $just]
                );
            }
        });

        session()->forget($sessionKey);

        return redirect()->route('eventos.show', $evento)
            ->with('success', 'Presenças importadas/atualizadas para a atividade com sucesso!');
    }
}
