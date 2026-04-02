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
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Presenca;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    public function moodleImport(Evento $evento)
    {
        return view('inscricoes.moodle_import', compact('evento'));
    }
    public function moodleMomentTemplateDownload(Evento $evento)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('momentos');

        $sheet->setCellValue('A1', 'momento');
        $sheet->setCellValue('B1', 'carga_horaria');

        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setWidth(18);

        $fileName = 'modelo_momentos_moodle.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function moodleUpload(Request $request, Evento $evento)
    {
        $request->validate([
            'participants_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
            'workloads_file' => 'required|file|mimes:xlsx,xls,csv|max:20480',
        ]);

        try {
            $parsed = $this->parseMoodleFiles(
                $request->file('participants_file'),
                $request->file('workloads_file')
            );

            $sessionKey = "moodle_import_evento_{$evento->id}";
            session([$sessionKey => $parsed]);

            return redirect()->route('inscricoes.moodle.preview', [
                'evento' => $evento,
                'session_key' => $sessionKey,
            ]);
        } catch (\Throwable $e) {
            $message = 'Falha ao processar as planilhas: ' . $e->getMessage();
            $errorBag = str_contains(Str::lower($e->getMessage()), 'carga')
                ? ['workloads_file' => $message]
                : ['participants_file' => $message];

            return back()->withErrors([
                ...$errorBag,
                'moodle_files' => $message,
            ])->withInput();
        }
    }

    public function moodlePreview(Request $request, Evento $evento)
    {
        $sessionKey = (string) $request->query('session_key');
        $payload = session($sessionKey, []);

        if (!is_array($payload) || empty($payload['participants'] ?? [])) {
            return redirect()->route('inscricoes.moodle.import', $evento)
                ->withErrors(['participants_file' => 'Sessão expirada. Envie as duas planilhas novamente.']);
        }

        $participants = collect($payload['participants']);

        $perPage = (int) $request->query('per_page', 30);
        if ($perPage < 1) {
            $perPage = 30;
        }

        $page = (int) max(1, $request->query('page', 1));
        $total = $participants->count();
        $slice = $participants->slice(($page - 1) * $perPage, $perPage)->values();

        $rowsPaginator = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => route('inscricoes.moodle.preview', $evento),
                'query' => [
                    'session_key' => $sessionKey,
                    'per_page' => $perPage,
                ],
            ]
        );

        return view('inscricoes.moodle_preview', [
            'evento' => $evento,
            'sessionKey' => $sessionKey,
            'rows' => $rowsPaginator,
            'momentos' => collect($payload['momentos'] ?? []),
            'resumo' => $payload['summary'] ?? [],
            'newUsers' => collect($payload['new_users'] ?? []),
            'errorsList' => collect($payload['errors'] ?? []),
            'conflicts' => collect($payload['conflicts'] ?? []),
        ]);
    }

    public function moodleConfirm(Request $request, Evento $evento)
    {
        $validated = $request->validate([
            'session_key' => 'required|string',
        ]);

        $sessionKey = $validated['session_key'];
        $payload = session($sessionKey, []);

        if (!is_array($payload) || empty($payload['participants'] ?? [])) {
            return back()->withErrors(['session_key' => 'Sessão expirada. Refaça a pré-visualização.']);
        }

        $errorsList = collect($payload['errors'] ?? []);
        if ($errorsList->isNotEmpty()) {
            return redirect()->route('inscricoes.moodle.preview', [
                'evento' => $evento,
                'session_key' => $sessionKey,
            ])->withErrors(['import' => 'Existem inconsistências. Corrija as planilhas antes de confirmar.']);
        }

        $momentos = collect($payload['momentos'] ?? []);
        $participants = collect($payload['participants'] ?? []);

        $momentosSemCarga = $momentos
            ->filter(fn ($momento) => !is_int($momento['carga_horaria'] ?? null))
            ->pluck('nome')
            ->map(fn ($nome) => trim((string) $nome))
            ->filter()
            ->unique()
            ->values();

        if ($momentosSemCarga->isNotEmpty()) {
            return redirect()->route('inscricoes.moodle.preview', [
                'evento' => $evento,
                'session_key' => $sessionKey,
            ])->withErrors([
                'import' => 'Não é permitido criar momento sem carga horária. Corrija a planilha de cargas para: ' . $momentosSemCarga->implode(', '),
            ]);
        }

        $stats = DB::transaction(function () use ($evento, $momentos, $participants) {
            $atividadesByName = $evento->atividades()->get()->keyBy(
                fn ($a) => $this->normalizeMoodleLabel((string) $a->descricao)
            );

            $momentoMap = [];
            $momentoCriado = 0;
            $momentoAtualizado = 0;

            foreach ($momentos as $momento) {
                $nome = trim((string) ($momento['nome'] ?? ''));
                if ($nome === '') {
                    continue;
                }

                $carga = $momento['carga_horaria'];
                if (!is_int($carga)) {
                    throw new \RuntimeException("Momento '{$nome}' sem carga horária válida.");
                }

                $key = $this->normalizeMoodleLabel($nome);
                $atividade = $atividadesByName->get($key);

                if (!$atividade) {
                    $diaBase = $evento->data_inicio ?: now()->toDateString();
                    $horaInicio = '08:00';
                    $horaFim = '09:00';

                    if (is_int($carga) && $carga > 0 && $carga <= 12) {
                        $horaFim = Carbon::createFromFormat('H:i', $horaInicio)
                            ->addHours($carga)
                            ->format('H:i');
                    }

                    $atividade = $evento->atividades()->create([
                        'descricao' => $nome,
                        'dia' => $diaBase,
                        'hora_inicio' => $horaInicio,
                        'hora_fim' => $horaFim,
                        'carga_horaria' => is_int($carga) ? $carga : null,
                        'presenca_ativa' => false,
                    ]);
                    $momentoCriado++;
                    $atividadesByName->put($key, $atividade);
                } else {
                    if (is_int($carga) && $atividade->carga_horaria !== $carga) {
                        $atividade->update(['carga_horaria' => $carga]);
                        $momentoAtualizado++;
                    }
                }

                $momentoMap[$nome] = $atividade;
            }

            $emails = $participants
                ->pluck('email')
                ->map(fn ($email) => strtolower(trim((string) $email)))
                ->filter()
                ->unique()
                ->values();

            $usersByEmail = $this->fetchUsersByEmailInsensitive($emails);

            $usuariosCriados = 0;
            foreach ($participants as $row) {
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                $nome = trim((string) ($row['nome'] ?? ''));
                if ($email === '' || $nome === '' || $usersByEmail->has($email)) {
                    continue;
                }

                $nomeFormatado = $this->formatMoodleUserName($nome);
                $senhaPadrao = $this->buildMoodleDefaultPassword($nomeFormatado);

                $user = User::create([
                    'name' => $nomeFormatado,
                    'email' => $email,
                    'password' => Hash::make($senhaPadrao),
                ]);
                $usersByEmail->put($email, $user);
                $usuariosCriados++;
            }

            $participantesByUser = Participante::whereIn('user_id', $usersByEmail->pluck('id')->values())
                ->get()
                ->keyBy('user_id');

            $inscricoesCriadas = 0;
            $presencasAtualizadas = 0;

            foreach ($participants as $row) {
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                if (!$usersByEmail->has($email)) {
                    continue;
                }

                $user = $usersByEmail->get($email);
                $participante = $participantesByUser->get($user->id);
                if (!$participante) {
                    $participante = Participante::create(['user_id' => $user->id]);
                    $participantesByUser->put($user->id, $participante);
                }

                foreach (($row['status_por_momento'] ?? []) as $nomeMomento => $statusConclusao) {
                    if (!isset($momentoMap[$nomeMomento])) {
                        continue;
                    }

                    $atividade = $momentoMap[$nomeMomento];

                    $inscricao = Inscricao::withTrashed()
                        ->where('evento_id', $evento->id)
                        ->where('atividade_id', $atividade->id)
                        ->where('participante_id', $participante->id)
                        ->first();

                    if (!$inscricao) {
                        $inscricao = Inscricao::create([
                            'evento_id' => $evento->id,
                            'atividade_id' => $atividade->id,
                            'participante_id' => $participante->id,
                            'ouvinte' => false,
                        ]);
                        $inscricoesCriadas++;
                    } else {
                        $inscricao->fill([
                            'evento_id' => $evento->id,
                            'atividade_id' => $atividade->id,
                            'participante_id' => $participante->id,
                            'ouvinte' => false,
                        ]);
                        $inscricao->deleted_at = null;
                        $inscricao->save();
                    }

                    Presenca::updateOrCreate(
                        [
                            'inscricao_id' => $inscricao->id,
                            'atividade_id' => $atividade->id,
                        ],
                        [
                            'status' => $statusConclusao ? 'presente' : 'ausente',
                            'justificativa' => null,
                        ]
                    );
                    $presencasAtualizadas++;
                }
            }

            return [
                'usuarios_criados' => $usuariosCriados,
                'momentos_criados' => $momentoCriado,
                'momentos_atualizados' => $momentoAtualizado,
                'inscricoes_criadas' => $inscricoesCriadas,
                'presencas_atualizadas' => $presencasAtualizadas,
            ];
        });

        session()->forget($sessionKey);

        return redirect()->route('eventos.show', $evento)
            ->with('success', sprintf(
                'Importação Moodle concluída. Usuários criados: %d. Momentos criados: %d. Cargas atualizadas: %d. Inscrições criadas: %d. Status atualizados: %d.',
                $stats['usuarios_criados'],
                $stats['momentos_criados'],
                $stats['momentos_atualizados'],
                $stats['inscricoes_criadas'],
                $stats['presencas_atualizadas']
            ));
    }

    private function parseMoodleFiles(UploadedFile $participantsFile, UploadedFile $workloadsFile): array
    {
        $participantsSheet = Excel::toArray([], $participantsFile)[0] ?? [];
        $workloadsSheet = Excel::toArray([], $workloadsFile)[0] ?? [];

        if (empty($participantsSheet)) {
            throw new \RuntimeException('A planilha de participantes está vazia.');
        }

        if (empty($workloadsSheet)) {
            throw new \RuntimeException('A planilha de carga horária está vazia.');
        }

        [$participants, $momentosParticipantes, $errorsParticipants, $conflicts] = $this->parseMoodleParticipantsSheet($participantsSheet);
        [$workloadsMap, $momentosCargas, $errorsWorkloads] = $this->parseMoodleWorkloadSheet($workloadsSheet);

        $errors = array_merge($errorsParticipants, $errorsWorkloads);

        $participantsByEmail = collect($participants)
            ->filter(fn ($row) => filter_var($row['email'] ?? null, FILTER_VALIDATE_EMAIL))
            ->groupBy(fn ($row) => strtolower(trim((string) ($row['email'] ?? ''))))
            ->map(fn ($group) => $group->first())
            ->values();

        $participantsEmails = $participantsByEmail
            ->pluck('email')
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        $usersByEmail = $this->fetchUsersByEmailInsensitive($participantsEmails);

        $newUsers = $participantsByEmail
            ->filter(function ($row) use ($usersByEmail) {
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                return $email !== '' && !$usersByEmail->has($email);
            })
            ->map(fn ($row) => [
                'nome' => trim((string) ($row['nome'] ?? '')),
                'email' => strtolower(trim((string) ($row['email'] ?? ''))),
            ])
            ->values()
            ->all();

        $missingInWorkloads = array_values(array_diff($momentosParticipantes, $momentosCargas));
        foreach ($missingInWorkloads as $momento) {
            $errors[] = "Momento '{$momento}' está na planilha de participantes, mas não está na planilha de cargas.";
        }

        $missingInParticipants = array_values(array_diff($momentosCargas, $momentosParticipantes));
        foreach ($missingInParticipants as $momento) {
            $errors[] = "Momento '{$momento}' está na planilha de cargas, mas não aparece na planilha de participantes.";
        }

        $momentosUnicos = collect(array_merge($momentosParticipantes, $momentosCargas))
            ->filter(fn ($nome) => trim((string) $nome) !== '')
            ->unique()
            ->values();

        $momentos = $momentosUnicos->map(function ($nome) use ($workloadsMap, $momentosParticipantes, $momentosCargas) {
            return [
                'nome' => $nome,
                'carga_horaria' => $workloadsMap[$nome] ?? null,
                'em_participantes' => in_array($nome, $momentosParticipantes, true),
                'em_cargas' => in_array($nome, $momentosCargas, true),
            ];
        })->values()->all();

        return [
            'participants' => $participants,
            'momentos' => $momentos,
            'new_users' => $newUsers,
            'errors' => array_values(array_unique($errors)),
            'conflicts' => $conflicts,
            'summary' => [
                'participants_rows' => count($participants),
                'participants_unique_email' => collect($participants)->pluck('email')->filter()->unique()->count(),
                'new_users_count' => count($newUsers),
                'momentos_total' => count($momentos),
                'momentos_com_carga' => count(array_filter($momentos, fn ($m) => is_int($m['carga_horaria']))),
                'errors_total' => count(array_unique($errors)),
            ],
        ];
    }

    private function parseMoodleParticipantsSheet(array $sheet): array
    {
        $headersRaw = array_map(fn ($v) => trim((string) $v), $sheet[0] ?? []);
        $headersNorm = array_map(fn ($v) => $this->normalizeMoodleHeader($v), $headersRaw);

        $acceptedNome = ['nome', 'name', 'nome_completo'];
        $acceptedEmail = [
            'email',
            'mail',
            'e_mail',
            'endereco_de_e_mail',
            'endereco_de_email',
        ];

        $idxNome = $this->findMoodleHeaderIndex($headersNorm, $acceptedNome);
        $idxEmail = $this->findMoodleHeaderIndex($headersNorm, [
            ...$acceptedEmail,
        ]);

        if ($idxNome === null || $idxEmail === null) {
            throw new \RuntimeException(
                'A planilha de participantes precisa conter as colunas nome e email. '
                . 'Aceitos para nome: [' . implode(', ', $acceptedNome) . ']. '
                . 'Aceitos para email: [' . implode(', ', $acceptedEmail) . ']. '
                . 'Colunas encontradas: ' . $this->formatMoodleHeadersForError($headersRaw)
            );
        }

        $naoMomentos = [
            'nome', 'name', 'email', 'mail', 'e_mail', 'cpf', 'telefone', 'municipio', 'tag',
            'tipo_organizacao', 'tipo_de_organizacao', 'organizacao', 'escola_unidade',
            'nome_completo', 'endereco_de_e_mail', 'endereco_de_email',
            'concluido', 'concluida', 'nao_concluido', 'nao_concluida', 'status',
        ];

        $momentColumns = [];
        foreach ($headersNorm as $i => $hNorm) {
            $raw = trim((string) ($headersRaw[$i] ?? ''));
            if ($raw === '' || in_array($hNorm, $naoMomentos, true)) {
                continue;
            }
            $momentColumns[$i] = $raw;
        }

        $errors = [];
        $conflicts = [];
        $participants = [];
        $emailsSeen = [];

        for ($i = 1; $i < count($sheet); $i++) {
            $line = $i + 1;
            $row = $sheet[$i] ?? [];

            $nome = trim((string) ($row[$idxNome] ?? ''));
            $email = strtolower(trim((string) ($row[$idxEmail] ?? '')));

            if ($nome === '' && $email === '') {
                continue;
            }

            if ($nome === '' || $email === '') {
                $errors[] = "Linha {$line}: nome e email são obrigatórios.";
            }

            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Linha {$line}: email inválido ({$email}).";
            }

            if ($email !== '') {
                $nameNorm = $this->normalizeMoodleLabel($nome);
                if (isset($emailsSeen[$email]) && $emailsSeen[$email] !== $nameNorm) {
                    $errors[] = "Linha {$line}: email {$email} aparece com nomes diferentes na mesma planilha.";
                }
                $emailsSeen[$email] = $nameNorm;
            }

            $statusByMoment = [];
            foreach ($momentColumns as $idx => $momentName) {
                $status = $this->parseMoodleStatus($row[$idx] ?? null);
                if ($status === null) {
                    continue;
                }
                $statusByMoment[$momentName] = $status;
            }

            $participants[] = [
                'line' => $line,
                'nome' => $nome,
                'email' => $email,
                'status_por_momento' => $statusByMoment,
            ];
        }

        $usersByEmail = $this->fetchUsersByEmailInsensitive(
            collect($participants)->pluck('email')->filter()->unique()->values()
        );

        foreach ($participants as $row) {
            $email = $row['email'];
            if ($email === '' || !$usersByEmail->has($email)) {
                continue;
            }

            $user = $usersByEmail->get($email);
            if ($this->normalizeMoodleLabel($row['nome']) !== $this->normalizeMoodleLabel((string) $user->name)) {
                $errors[] = "Linha {$row['line']}: nome/email não bate com cadastro existente. Planilha '{$row['nome']}' x Sistema '{$user->name}' ({$email}).";
                $conflicts[] = [
                    'line' => $row['line'],
                    'email' => $email,
                    'sheet_name' => $row['nome'],
                    'system_name' => $user->name,
                ];
            }
        }

        return [
            $participants,
            array_values($momentColumns),
            $errors,
            $conflicts,
        ];
    }

    private function parseMoodleWorkloadSheet(array $sheet): array
    {
        $headersRaw = array_map(fn ($v) => trim((string) $v), $sheet[0] ?? []);
        $headersNorm = array_map(fn ($v) => $this->normalizeMoodleHeader($v), $headersRaw);

        $acceptedMomento = ['momento', 'momentos', 'atividade', 'nome_momento', 'nome_do_momento', 'nome', 'descricao'];
        $acceptedCarga = ['carga_horaria', 'carga_horaria_do_momento', 'carga', 'horas', 'hora', 'duracao', 'duracao_horas'];

        $idxMomento = $this->findMoodleHeaderIndex($headersNorm, $acceptedMomento);
        $idxCarga = $this->findMoodleHeaderIndex($headersNorm, $acceptedCarga);

        if ($idxMomento === null || $idxCarga === null) {
            throw new \RuntimeException(
                'A planilha de cargas precisa conter colunas de momento e carga horária. '
                . 'Aceitos para momento: [' . implode(', ', $acceptedMomento) . ']. '
                . 'Aceitos para carga: [' . implode(', ', $acceptedCarga) . ']. '
                . 'Colunas encontradas: ' . $this->formatMoodleHeadersForError($headersRaw)
            );
        }

        $workloadsMap = [];
        $errors = [];
        $moments = [];

        for ($i = 1; $i < count($sheet); $i++) {
            $line = $i + 1;
            $row = $sheet[$i] ?? [];

            $momento = trim((string) ($row[$idxMomento] ?? ''));
            $cargaRaw = trim((string) ($row[$idxCarga] ?? ''));

            if ($momento === '' && $cargaRaw === '') {
                continue;
            }

            if ($momento === '') {
                $errors[] = "Linha {$line}: momento vazio na planilha de carga horária.";
                continue;
            }

            if ($cargaRaw === '' || !is_numeric($cargaRaw)) {
                $errors[] = "Linha {$line}: carga horária inválida para o momento '{$momento}'.";
                continue;
            }

            $carga = (int) $cargaRaw;
            if ($carga < 0) {
                $errors[] = "Linha {$line}: carga horária negativa para o momento '{$momento}'.";
                continue;
            }

            $workloadsMap[$momento] = $carga;
            $moments[] = $momento;
        }

        return [$workloadsMap, $moments, $errors];
    }

    private function normalizeMoodleHeader(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value)));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
        return trim($value, '_');
    }

    private function normalizeMoodleLabel(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value)));
        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }

    private function findMoodleHeaderIndex(array $headersNorm, array $candidates): ?int
    {
        foreach ($headersNorm as $index => $header) {
            if (in_array($header, $candidates, true)) {
                return $index;
            }
        }

        return null;
    }

    private function parseMoodleStatus(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        $normalized = Str::lower(Str::ascii(trim((string) $value)));
        if ($normalized === '') {
            return null;
        }

        $trueValues = ['sim', 's', '1', 'true', 'ok', 'x', 'concluiu', 'concluido', 'presente'];
        $falseValues = [
            'nao', 'n', '0', 'false', 'ausente', 'pendente',
            'nao concluiu', 'nao_concluiu',
            'nao concluido', 'nao_concluido',
            'nao-concluido', 'nao concluida', 'nao_concluida',
            'nao finalizou', 'nao_finalizou',
        ];

        if (in_array($normalized, $trueValues, true)) {
            return true;
        }

        if (in_array($normalized, $falseValues, true)) {
            return false;
        }

        return null;
    }

    private function formatMoodleUserName(string $name): string
    {
        $normalized = preg_replace('/\s+/', ' ', trim($name)) ?? '';
        if ($normalized === '') {
            return 'Participante';
        }

        return Str::title(Str::lower($normalized));
    }

    private function buildMoodleDefaultPassword(string $name): string
    {
        $firstName = Str::of($name)
            ->trim()
            ->explode(' ')
            ->first();

        $firstName = Str::lower(Str::ascii((string) $firstName));
        $firstName = preg_replace('/[^a-z0-9]/', '', $firstName) ?? '';

        if ($firstName === '') {
            $firstName = 'usuario';
        }

        return $firstName . '1234';
    }

    private function fetchUsersByEmailInsensitive(Collection $emails): Collection
    {
        $normalizedEmails = $emails
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter()
            ->unique()
            ->values();

        if ($normalizedEmails->isEmpty()) {
            return collect();
        }

        return User::query()
            ->whereIn(DB::raw('LOWER(email)'), $normalizedEmails->all())
            ->get()
            ->keyBy(fn ($user) => strtolower(trim((string) $user->email)));
    }

    private function formatMoodleHeadersForError(array $headersRaw): string
    {
        $headers = collect($headersRaw)
            ->map(fn ($h) => trim((string) $h))
            ->filter(fn ($h) => $h !== '')
            ->values()
            ->all();

        if (empty($headers)) {
            return '[sem cabeçalhos na primeira linha]';
        }

        return '[' . implode(', ', $headers) . ']';
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

        $resumoImportacao = $this->montarResumoImportacao($allRows);

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
            'usuariosExistentesCount' => $resumoImportacao['usuariosExistentesCount'],
            'usuariosNovosCount' => $resumoImportacao['usuariosNovosCount'],
        ]);
    }


    private function montarResumoImportacao(Collection $allRows): array
    {
        $rowsUnicosPorEmail = $allRows
            ->filter(function ($row) {
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                return $email !== '';
            })
            ->groupBy(fn($row) => strtolower(trim((string) ($row['email'] ?? ''))))
            ->map(fn($grupo) => $grupo->first())
            ->values();

        $emailsImportacao = $rowsUnicosPorEmail
            ->map(fn($row) => strtolower(trim((string) ($row['email'] ?? ''))))
            ->values();

        $emailsExistentes = $emailsImportacao->isEmpty()
            ? collect()
            : User::whereIn('email', $emailsImportacao)
                ->pluck('email')
                ->map(fn($email) => strtolower(trim((string) $email)))
                ->unique()
                ->values();

        $emailsExistentesLookup = array_fill_keys($emailsExistentes->all(), true);

        $rowsNovos = $rowsUnicosPorEmail
            ->filter(function ($row) use ($emailsExistentesLookup) {
                $email = strtolower(trim((string) ($row['email'] ?? '')));
                return !isset($emailsExistentesLookup[$email]);
            })
            ->values();

        $usuariosExistentesCount = $emailsExistentes->count();
        $usuariosNovosCount = max($emailsImportacao->count() - $usuariosExistentesCount, 0);

        return [
            'usuariosExistentesCount' => $usuariosExistentesCount,
            'usuariosNovosCount'      => $usuariosNovosCount,
            'rowsNovos'               => $rowsNovos,
        ];
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

                $tipoOrgRaw = $row['tipo_organizacao'] ?? $row['organizacao'] ?? null;
                $tipoOrg    = is_string($tipoOrgRaw) ? trim($tipoOrgRaw) : null;

                $orgRaw = $row['escola_unidade'] ?? $row['organizacao_nome'] ?? null;
                if ($orgRaw === null && !isset($row['tipo_organizacao'])) {
                    $orgRaw = $row['organizacao'] ?? null;
                }
                $org    = is_string($orgRaw) ? trim($orgRaw) : null;

                $tagRaw = $row['tag'] ?? null;
                $tag    = is_string($tagRaw) ? trim($tagRaw) : null;
                if ($tag === '') {
                    $tag = null;
                } elseif (!isset($tagLookup[$tag])) {
                    $tag = null;
                }

                $telefoneRaw = $row['telefone'] ?? null;
                $telefoneValue = is_string($telefoneRaw)
                    ? trim($telefoneRaw)
                    : (is_scalar($telefoneRaw) ? trim((string) $telefoneRaw) : null);

                $telefoneValue = $telefoneValue !== '' ? $telefoneValue : null;

                $dados = [
                    'municipio_id'     => ($row['municipio_id'] ?? null) ?: null,
                    'cpf'              => (($row['cpf'] ?? '') !== '') ? trim((string)$row['cpf']) : null,
                    'telefone'         => $telefoneValue,
                    'escola_unidade'   => ($org !== '') ? $org : null,
                    'tipo_organizacao' => ($tipoOrg !== '') ? $tipoOrg : null,
                    'tag'              => $tag,
                    'data_entrada'     => $toDate($row['data_entrada'] ?? null),
                ];

                if ($participantesExistentes->has($userId)) {
                   $camposProtegidos = ['municipio_id', 'cpf', 'telefone', 'escola_unidade', 'tipo_organizacao', 'tag', 'data_entrada'];
                   foreach ($camposProtegidos as $campo) {
                    if (array_key_exists($campo, $dados) && $dados[$campo] === null) {
                        unset($dados[$campo]);
                    }
                }

                if (!empty($dados)) {
                    $atualizacoes[] = ['user_id' => $userId] + $dados;
                }

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
                $campos = ['municipio_id', 'cpf', 'telefone', 'escola_unidade', 'tipo_organizacao', 'tag', 'data_entrada'];
                $cases = [];
                foreach ($campos as $field) {
                    $sql = "$field = CASE user_id\n";
                    foreach ($atualizacoes as $upd) {
                        if (!array_key_exists($field, $upd)) {
                            $sql .= "WHEN {$upd['user_id']} THEN $field\n";
                            continue;
                        }
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
                        'ouvinte'         => false,
                    ]);
                    $inscricao->deleted_at = null;
                    $inscricao->save();
                } else {
                    Inscricao::create([
                        'evento_id'       => $evento->id,
                        'atividade_id'    => $atividade->id,
                        'participante_id' => $participanteId,
                        'ouvinte'         => false,
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

    public function selecionar(Request $request, Evento $evento)
    {
        $search      = trim((string) $request->query('q', ''));
        $municipioId = $request->query('municipio_id');
        $tagSelecionada = $request->query('tag');
        $atividadeId = $request->query('atividade_id');
        $perPage     = (int) $request->query('per_page', 25);

        if (!in_array($perPage, [25, 50, 100, 200], true)) {
            $perPage = 25;
        }

        $municipios = \App\Models\Municipio::with('estado')
            ->orderBy('nome')
            ->get(['id', 'nome', 'estado_id']);

        $atividades = $evento->atividades()
            ->orderBy('dia')
            ->orderBy('hora_inicio')
            ->get(['id', 'descricao', 'dia', 'hora_inicio']);

        $atividadeSelecionada = null;
        if ($atividadeId) {
            $atividadeSelecionada = $atividades->firstWhere('id', (int) $atividadeId);
            if (!$atividadeSelecionada) {
                $atividadeId = null;
            }
        }

        $apenasDisponiveis = $request->has('apenas_disponiveis')
            ? $request->boolean('apenas_disponiveis')
            : (bool) $atividadeId;

        if (!$atividadeId) {
            $apenasDisponiveis = false;
        }

        $participanteTags = config('engaja.participante_tags', Participante::TAGS);

        $inscricoesAtivas = Inscricao::query()
            ->where('evento_id', $evento->id)
            ->whereNull('deleted_at')
            ->get(['participante_id', 'atividade_id']);

        $inscritosEvento = $inscricoesAtivas
            ->pluck('participante_id')
            ->unique()
            ->all();

        $inscritosAtividade = $atividadeId
            ? $inscricoesAtivas
                ->filter(fn($item) => (int) $item->atividade_id === (int) $atividadeId)
                ->pluck('participante_id')
                ->unique()
                ->all()
            : [];

        $participantesQuery = Participante::query()
            ->with([
                'user:id,name,email',
                'municipio.estado:id,nome,sigla',
            ])
            ->whereNull('participantes.deleted_at')
            ->when($municipioId, fn($q) => $q->where('municipio_id', $municipioId))
            ->when($tagSelecionada, fn($q) => $q->where('tag', $tagSelecionada))
            ->when($search, function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('cpf', 'ilike', $like)
                        ->orWhere('telefone', 'ilike', $like)
                        ->orWhereHas('user', function ($uq) use ($like) {
                            $uq->where('name', 'ilike', $like)
                                ->orWhere('email', 'ilike', $like);
                        })
                        ->orWhereHas('municipio', function ($mq) use ($like) {
                            $mq->where('nome', 'ilike', $like);
                        });
                });
            });

        if ($atividadeId && $apenasDisponiveis) {
            $participantesQuery->whereDoesntHave('inscricoes', function ($q) use ($evento, $atividadeId) {
                $q->where('evento_id', $evento->id)
                    ->whereNull('deleted_at');
            });
        }

        $participantes = $participantesQuery
            ->leftJoin('users as users_order', 'users_order.id', '=', 'participantes.user_id')
            ->select('participantes.*')
            ->orderByRaw('LOWER(users_order.name) ASC NULLS LAST')
            ->paginate($perPage)
            ->appends($request->query());

        return view('inscricoes.selecionar', [
            'evento'               => $evento,
            'participantes'        => $participantes,
            'municipios'           => $municipios,
            'atividades'           => $atividades,
            'participanteTags'     => $participanteTags,
            'search'               => $search,
            'municipioId'          => $municipioId,
            'tagSelecionada'       => $tagSelecionada,
            'atividadeId'          => $atividadeId,
            'atividadeSelecionada' => $atividadeSelecionada,
            'apenasDisponiveis'    => $apenasDisponiveis,
            'perPage'              => $perPage,
            'inscritosNaAtividade' => $inscritosAtividade,
            'inscritosNoEvento'    => $inscritosEvento,
        ]);
    }

    public function selecionarStore(Request $request, Evento $evento)
    {
        $validated = $request->validate([
            'atividade_id' => [
                'required',
                'integer',
                Rule::exists('atividades', 'id')->where('evento_id', $evento->id),
            ],
            'participantes'   => ['required', 'array', 'min:1'],
            'participantes.*' => [
                'integer',
                Rule::exists('participantes', 'id'),
            ],
        ], [
            'participantes.required' => 'Selecione pelo menos um participante.',
            'participantes.min'      => 'Selecione pelo menos um participante.',
        ]);

        $atividade = $evento->atividades()
            ->whereKey($validated['atividade_id'])
            ->firstOrFail();

        $participanteIds = array_values(array_unique($validated['participantes']));

        $participantes = Participante::whereIn('id', $participanteIds)->get();

        if ($participantes->isEmpty()) {
            return back()->withErrors(['participantes' => 'Nenhum participante v�lido foi selecionado.']);
        }

        $resultado = DB::transaction(function () use ($participantes, $evento, $atividade) {
            $totais = [
                'adicionados' => 0,
                'ignorados'   => 0,
            ];

            foreach ($participantes as $participante) {
                $inscricao = Inscricao::withTrashed()
                    ->where('participante_id', $participante->id)
                    ->where('atividade_id', $atividade->id)
                    ->where('evento_id', $evento->id)
                    ->first();

                if ($inscricao && $inscricao->deleted_at === null) {
                    $totais['ignorados']++;
                    continue;
                }

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
                        'ouvinte'         => false,
                    ]);
                    $inscricao->deleted_at = null;
                    $inscricao->save();
                } else {
                    Inscricao::create([
                        'evento_id'       => $evento->id,
                        'atividade_id'    => $atividade->id,
                        'participante_id' => $participante->id,
                        'ouvinte'         => false,
                    ]);
                }

                $totais['adicionados']++;
            }

            return $totais;
        });

        $mensagem = "{$resultado['adicionados']} participante(s) inscrito(s).";
        if ($resultado['ignorados'] > 0) {
            $mensagem .= " {$resultado['ignorados']} j� estavam inscritos neste momento.";
        }

        $queryParams = [
            'atividade_id'       => $validated['atividade_id'],
            'q'                  => $request->input('q'),
            'municipio_id'       => $request->input('municipio_id'),
            'tag'                => $request->input('tag'),
            'per_page'           => $request->input('per_page'),
            'apenas_disponiveis' => $request->has('apenas_disponiveis')
                ? ($request->boolean('apenas_disponiveis') ? 1 : 0)
                : 1,
        ];

        $queryParams = array_filter(
            $queryParams,
            fn($value) => $value !== null && $value !== ''
        );

        return redirect()
            ->route('inscricoes.selecionar', array_merge(['evento' => $evento->id], $queryParams))
            ->with('success', $mensagem);
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
