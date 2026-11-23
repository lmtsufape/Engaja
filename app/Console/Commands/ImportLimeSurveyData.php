<?php

namespace App\Console\Commands;

use App\Services\LimeSurvey\LimeSurveyClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ImportLimeSurveyData extends Command
{
    protected $signature = 'limesurvey:importar-dados
                            {--survey_id= : ID do survey específico (usa todos os ativos se omitido)}
                            {--force : Força a importação mesmo que já tenha sido executada hoje}';

    protected $description = 'Importa dados do LimeSurvey para o cache (TTL de 24h). Usado pelo scheduler diário.';

    public function __construct(private readonly LimeSurveyClient $client)
    {
        parent::__construct();
    }

    private const LAST_IMPORT_KEY = 'limesurvey:last_daily_import';

    private const FAILED_IDS_KEY = 'limesurvey:failed_survey_ids';

    public function handle(): int
    {
        $specificId = $this->option('survey_id');

        if ($specificId !== null) {
            Log::info('LimeSurvey: importação manual de survey específico.', ['survey_id' => $specificId]);

            return $this->runImport([(int) $specificId], isFullRun: false);
        }

        if ($this->option('force')) {
            Log::info('LimeSurvey: importação forçada de todos os surveys.');

            return $this->runImport($this->resolveSurveyIds(), isFullRun: true);
        }

        $lastRun = Cache::get(self::LAST_IMPORT_KEY);
        $jaRodouHoje = $lastRun !== null && $lastRun->isSameDay(now());

        if ($jaRodouHoje) {
            $failedIds = Cache::get(self::FAILED_IDS_KEY, []);

            if (empty($failedIds)) {
                $this->info('Importação já executada hoje sem pendências.');
                Log::info('LimeSurvey: importação já executada hoje sem pendências.', ['last_run' => $lastRun]);

                return self::SUCCESS;
            }

            $this->info(sprintf('Re-importando %d survey(s) com falha anterior: %s', count($failedIds), implode(', ', $failedIds)));
            Log::warning('LimeSurvey: re-importando surveys com falha anterior.', ['survey_ids' => $failedIds]);

            return $this->runImport($failedIds, isFullRun: false);
        }

        Log::info('LimeSurvey: iniciando importação diária completa.');

        return $this->runImport($this->resolveSurveyIds(), isFullRun: true);
    }

    private function runImport(array $surveyIds, bool $isFullRun): int
    {
        if (empty($surveyIds)) {
            $this->warn('Nenhum survey ativo encontrado.');
            Log::warning('LimeSurvey: nenhum survey ativo encontrado para importação.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Importando %d survey(s) para o cache...', count($surveyIds)));

        $failedIds = $isFullRun ? [] : Cache::get(self::FAILED_IDS_KEY, []);
        $successCount = 0;

        foreach ($surveyIds as $surveyId) {
            if ($this->importSurvey($surveyId)) {
                $successCount++;
                $failedIds = array_values(array_filter($failedIds, fn ($id) => $id !== $surveyId));
            } elseif (! in_array($surveyId, $failedIds)) {
                $failedIds[] = $surveyId;
            }
        }

        if (empty($failedIds)) {
            Cache::forget(self::FAILED_IDS_KEY);
            $this->info('Importação concluída sem pendências.');
            Log::info('LimeSurvey: importação concluída com sucesso.', [
                'surveys_importados' => $successCount,
                'is_full_run' => $isFullRun,
            ]);
        } else {
            Cache::put(self::FAILED_IDS_KEY, $failedIds, now()->addDays(2));
            $this->warn(sprintf('%d survey(s) pendente(s) para retry: %s', count($failedIds), implode(', ', $failedIds)));
            Log::warning('LimeSurvey: importação concluída com falhas.', [
                'surveys_importados' => $successCount,
                'surveys_com_falha' => $failedIds,
                'is_full_run' => $isFullRun,
            ]);
        }

        if ($isFullRun) {
            Cache::put(self::LAST_IMPORT_KEY, now(), now()->addDays(2));
        }

        return empty($failedIds) ? self::SUCCESS : self::FAILURE;
    }

    private function resolveSurveyIds(): array
    {
        $surveyIdOption = $this->option('survey_id');

        if ($surveyIdOption !== null) {
            return [(int) $surveyIdOption];
        }

        $defaultId = (int) config('services.limesurvey.survey_id');

        try {
            $surveys = $this->client->listSurveys();
            $active = array_filter($surveys, fn (array $s) => ($s['active'] ?? '') === 'Y');
            $ids = array_values(array_map(fn (array $s) => (int) $s['sid'], $active));

            return $ids ?: ($defaultId > 0 ? [$defaultId] : []);
        } catch (\Throwable $e) {
            $this->error('Erro ao listar surveys: '.$e->getMessage());
            Log::error('LimeSurvey: falha ao listar surveys ativos.', ['error' => $e->getMessage()]);

            return $defaultId > 0 ? [$defaultId] : [];
        }
    }

    private function importSurvey(int $surveyId): bool
    {
        $this->line("  Survey {$surveyId}...");

        try {
            $questions = $this->refreshCache(
                "limesurvey:{$surveyId}:questions",
                fn () => $this->client->listQuestions($surveyId)
            );
            $this->line('    ✓ Questões: '.count($questions));

            $responses = $this->refreshCache(
                "limesurvey:{$surveyId}:responses",
                fn () => $this->client->exportResponses($surveyId)
            );
            $this->line('    ✓ Respostas: '.count($responses));

            Cache::forever("limesurvey:{$surveyId}:cached_at", now());

            $this->refreshAnswerOptions($surveyId, $questions);

            Log::info("LimeSurvey: survey {$surveyId} importado com sucesso.", [
                'survey_id' => $surveyId,
                'questoes' => count($questions),
                'respostas' => count($responses),
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->error("    ✗ Erro no survey {$surveyId}: ".$e->getMessage());
            Log::error("LimeSurvey: falha ao importar survey {$surveyId}.", [
                'survey_id' => $surveyId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function refreshAnswerOptions(int $surveyId, array $questions): void
    {
        $lTypeQuestions = array_filter(
            $questions,
            fn (array $q) => strtoupper((string) ($q['type'] ?? '')) === 'L'
                && ($q['parent_qid'] ?? '0') === '0'
        );

        if (empty($lTypeQuestions)) {
            return;
        }

        $count = 0;
        foreach ($lTypeQuestions as $question) {
            $qid = (int) ($question['qid'] ?? 0);
            if ($qid <= 0) {
                continue;
            }

            try {
                $this->refreshCache(
                    "limesurvey:{$surveyId}:answer_options:{$qid}",
                    fn () => $this->client->getQuestionProperties($qid)
                );
                $count++;
            } catch (\Throwable $e) {
                $this->warn("    ⚠ Opções da questão {$qid}: ".$e->getMessage());
                Log::warning("LimeSurvey: falha ao importar opções da questão {$qid}.", [
                    'survey_id' => $surveyId,
                    'qid' => $qid,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($count > 0) {
            $this->line("    ✓ Opções de resposta: {$count} questão(ões) tipo L");
        }
    }

    private function refreshCache(string $key, \Closure $fetch): mixed
    {
        Cache::forget($key);
        $data = $fetch();
        Cache::forever($key, $data);

        return $data;
    }
}
