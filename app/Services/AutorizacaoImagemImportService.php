<?php

namespace App\Services;

use App\Enums\OrigemAutorizacaoImagem;
use App\Enums\SituacaoAutorizacaoImagem;
use App\Models\AutorizacaoImagem;
use App\Models\Evento;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AutorizacaoImagemImportService
{
    public function __construct(
        private readonly AutorizacaoImagemService $autorizacoes,
    ) {}

    public function normalize(array $rows): array
    {
        $normalized = [];
        foreach (array_values($rows) as $index => $row) {
            $nome = is_scalar($row['nome'] ?? null)
                ? preg_replace('/\s+/u', ' ', trim((string) $row['nome']))
                : '';
            $cpfRaw = is_scalar($row['cpf'] ?? null) ? trim((string) $row['cpf']) : '';
            $cpf = preg_replace('/\D+/', '', $cpfRaw);

            $normalized[] = [
                'aba_original' => is_scalar($row['aba_original'] ?? null) && trim((string) $row['aba_original']) !== ''
                    ? trim((string) $row['aba_original'])
                    : 'Planilha',
                'linha_original' => (int) ($row['linha_original'] ?? ($index + 2)),
                'nome' => $nome,
                'cpf' => $cpf,
            ];
        }

        return $normalized;
    }

    public function analyze(Evento $evento, array $rows, bool $lock = false): array
    {
        $rows = $this->normalize($rows);
        $validCpfs = collect($rows)
            ->pluck('cpf')
            ->filter(fn (string $cpf) => strlen($cpf) === 11)
            ->unique()
            ->values()
            ->all();

        $activeByCpf = [];
        $allCandidateIds = [];
        foreach (array_chunk($validCpfs, 1000) as $chunk) {
            $participants = Participante::query()
                ->whereHas('user', fn (Builder $q) => $q->where('sistema_origem', User::SISTEMA_ENGAJA))
                ->whereIn(DB::raw("regexp_replace(coalesce(cpf, ''), '[^0-9]', '', 'g')"), $chunk)
                ->with('user')
                ->get();

            foreach ($participants as $participant) {
                $cpf = preg_replace('/\D+/', '', (string) $participant->cpf);
                if ($cpf === '') {
                    continue;
                }
                $activeByCpf[$cpf][] = $participant;
                $allCandidateIds[$participant->id] = $participant->id;
            }
        }

        $missingCpfs = array_values(array_diff($validCpfs, array_keys($activeByCpf)));
        $trashedCpfs = [];
        foreach (array_chunk($missingCpfs, 1000) as $chunk) {
            $trashed = Participante::withTrashed()
                ->whereHas('user', fn (Builder $q) => $q->withTrashed()->where('sistema_origem', User::SISTEMA_ENGAJA))
                ->whereIn(DB::raw("regexp_replace(coalesce(cpf, ''), '[^0-9]', '', 'g')"), $chunk)
                ->pluck('cpf');

            foreach ($trashed as $cpfRaw) {
                $cpf = preg_replace('/\D+/', '', (string) $cpfRaw);
                if ($cpf !== '') {
                    $trashedCpfs[$cpf] = true;
                }
            }
        }

        $vinculadosSet = [];
        foreach (array_chunk(array_values($allCandidateIds), 1000) as $chunk) {
            $ids = $this->autorizacoes->participantesDaAcao($evento)
                ->whereIn('participantes.id', $chunk)
                ->pluck('participantes.id');

            foreach ($ids as $id) {
                $vinculadosSet[(int) $id] = true;
            }
        }

        $respostasByParticipante = [];
        foreach (array_chunk(array_keys($vinculadosSet), 1000) as $chunk) {
            $respostas = AutorizacaoImagem::query()
                ->where('evento_id', $evento->id)
                ->whereIn('participante_id', $chunk)
                ->when($lock, fn (Builder $q) => $q->lockForUpdate())
                ->get();

            foreach ($respostas as $resposta) {
                $respostasByParticipante[$resposta->participante_id] = $resposta;
            }
        }

        $multipleSheets = collect($rows)->pluck('aba_original')->unique()->count() > 1;
        $items = [];
        $errors = [];
        $targets = [];
        $seenParticipants = [];
        $counts = [
            'total' => count($rows),
            'autorizar' => 0,
            'alterar_recusa' => 0,
            'ja_autorizadas' => 0,
            'duplicadas' => 0,
            'pendencias' => 0,
            'unicos_validos' => 0,
        ];

        foreach ($rows as $index => $row) {
            $location = $this->location($row, $multipleSheets);
            $cpf = $row['cpf'];
            $item = [
                'location' => $location,
                'participante' => null,
                'situacao_atual' => null,
                'acao_prevista' => 'erro',
                'erro' => null,
                'avisos' => [],
            ];

            if ($cpf === '') {
                $item['erro'] = 'Informe o CPF do participante.';
            } elseif (strlen($cpf) !== 11) {
                $item['erro'] = 'O CPF informado tem '.strlen($cpf).' dígito(s); precisa ter 11 dígitos, incluindo zeros à esquerda.';
            } else {
                $candidates = $activeByCpf[$cpf] ?? [];
                if ($candidates === []) {
                    $item['erro'] = isset($trashedCpfs[$cpf])
                        ? 'O cadastro encontrado para este CPF está excluído no sistema.'
                        : 'Participante não encontrado no sistema.';
                } else {
                    $vinculados = array_values(array_filter(
                        $candidates,
                        fn (Participante $participant) => isset($vinculadosSet[$participant->id])
                    ));

                    if ($vinculados === []) {
                        $item['erro'] = 'Participante não está inscrito nesta ação.';
                    } else {
                        usort($vinculados, function (Participante $a, Participante $b): int {
                            return ($b->user->created_at?->getTimestamp() ?? 0)
                                <=> ($a->user->created_at?->getTimestamp() ?? 0)
                                ?: $b->user_id <=> $a->user_id
                                ?: $b->id <=> $a->id;
                        });

                        $participante = $vinculados[0];
                        $item['participante'] = $participante;

                        if (count($vinculados) > 1) {
                            $item['avisos'][] = 'Mais de um cadastro ativo com este CPF está vinculado à ação; foi selecionado o cadastro mais recente.';
                        }

                        if ($row['nome'] !== '' && $this->normalizeName($row['nome']) !== $this->normalizeName((string) $participante->user->name)) {
                            $item['avisos'][] = 'O nome na planilha difere do cadastro no sistema. Confira se o CPF está correto.';
                        }

                        $resposta = $respostasByParticipante[$participante->id] ?? null;
                        $situacaoAtual = SituacaoAutorizacaoImagem::daResposta($resposta);
                        $item['situacao_atual'] = $situacaoAtual;

                        if (isset($seenParticipants[$participante->id])) {
                            $item['acao_prevista'] = 'duplicada_planilha';
                            $item['avisos'][] = 'CPF repetido na planilha (já listado na '.$seenParticipants[$participante->id].'); será considerado apenas uma vez.';
                            $counts['duplicadas']++;
                        } else {
                            $seenParticipants[$participante->id] = $location;
                            $targets[$participante->id] = $participante;

                            if ($situacaoAtual === SituacaoAutorizacaoImagem::Pendente) {
                                $item['acao_prevista'] = 'autorizar';
                                $counts['autorizar']++;
                            } elseif ($situacaoAtual === SituacaoAutorizacaoImagem::Negada) {
                                $item['acao_prevista'] = 'alterar_recusa';
                                $counts['alterar_recusa']++;
                            } else {
                                $item['acao_prevista'] = 'ja_autorizada';
                                $counts['ja_autorizadas']++;
                            }
                        }
                    }
                }
            }

            if ($item['erro'] !== null) {
                $errors[$index] = $location.': '.$item['erro'];
                $counts['pendencias']++;
            }

            $items[$index] = $item;
        }

        $counts['unicos_validos'] = count($targets);

        return [
            'rows' => $rows,
            'items' => $items,
            'errors' => $errors,
            'targets' => $targets,
            'counts' => $counts,
        ];
    }

    public function apply(Evento $evento, array $rows, User $responsavel): array
    {
        abort_unless($evento->exists && ! $evento->trashed(), 404);
        abort_unless($responsavel->isEngajaUser() && $responsavel->can('autorizacao-imagem.registrar'), 403);

        return DB::transaction(function () use ($evento, $rows, $responsavel) {
            $analysis = $this->analyze($evento, $rows, lock: true);

            if ($analysis['errors'] !== []) {
                throw ValidationException::withMessages(['rows' => array_values($analysis['errors'])]);
            }

            if ($analysis['targets'] === []) {
                throw ValidationException::withMessages(['rows' => 'Não há participantes válidos para importar.']);
            }

            $targetIds = array_keys($analysis['targets']);
            sort($targetIds);

            $participantes = Participante::query()
                ->whereIn('id', $targetIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->with('user')
                ->get();

            $result = [
                'autorizadas' => 0,
                'alteradas_de_recusa' => 0,
                'ja_autorizadas' => 0,
                'total_processados' => 0,
            ];

            foreach ($participantes as $participante) {
                $atual = $this->autorizacoes->resposta($participante, $evento);
                if ($atual !== null && $atual->autorizado === true) {
                    $result['ja_autorizadas']++;
                    $result['total_processados']++;

                    continue;
                }

                $this->autorizacoes->registrar(
                    participante: $participante,
                    evento: $evento,
                    autorizado: true,
                    origem: OrigemAutorizacaoImagem::ImportacaoGestao,
                    responsavel: $responsavel,
                    versaoEsperada: $atual?->versao ?? 0,
                );

                if ($atual === null) {
                    $result['autorizadas']++;
                } else {
                    $result['alteradas_de_recusa']++;
                }
                $result['total_processados']++;
            }

            return $result;
        });
    }

    private function location(array $row, bool $multipleSheets): string
    {
        $prefix = $multipleSheets ? $row['aba_original'].' — ' : '';

        return $prefix.'Linha '.$row['linha_original'];
    }

    private function normalizeName(string $value): string
    {
        return preg_replace('/\s+/u', ' ', trim(mb_strtolower(Str::ascii($value))));
    }
}
