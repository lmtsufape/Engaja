<?php

namespace App\Services\LimeSurvey;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LimeSurveyDashboardService
{
    public function __construct(private readonly LimeSurveyClient $client)
    {
    }

    public function buildPayload(Request $request): array
    {
        $surveyId = $this->resolveSurveyId($request);
        $cacheMinutes = max((int) config('services.limesurvey.cache_minutes', 5), 1);

        $questions = Cache::remember(
            "limesurvey:{$surveyId}:questions",
            now()->addMinutes($cacheMinutes),
            fn () => $this->client->listQuestions($surveyId)
        );

        $responses = Cache::remember(
            "limesurvey:{$surveyId}:responses",
            now()->addMinutes($cacheMinutes),
            fn () => $this->client->exportResponses($surveyId)
        );

        $responsesCollection = collect($this->applyDateFilter($responses, $request));
        $questionList = $this->normalizeQuestions($questions);
        $tokenMunicipioMap = $this->buildTokenMunicipioMap($surveyId);
        $biMatrizes = $this->buildBiMatrizes($questionList, $responsesCollection, $request, $tokenMunicipioMap);

        $excludedColumns = collect($biMatrizes)
            ->flatMap(fn (array $matriz) => $matriz['columns'] ?? [])
            ->unique()
            ->values();

        $perguntas = $this->buildPerguntas($questionList, $responsesCollection, $excludedColumns);
        $questionBlocks = $this->buildQuestionBlocks(
            $questionList,
            $perguntas,
            $biMatrizes,
            $responsesCollection,
            $request,
            $tokenMunicipioMap
        );

        return [
            'totais' => [
                'submissoes' => $responsesCollection->count(),
                'atividades' => 1,
                'eventos' => 1,
                'respostas' => $perguntas->sum('total'),
                'questoes' => $perguntas->where('total', '>', 0)->count(),
                'ultima' => $this->resolveUltimaResposta($responsesCollection),
            ],
            'perguntas' => $perguntas->values()->all(),
            'bi_matrizes' => $biMatrizes,
            'question_blocks' => $questionBlocks,
            'recentes' => [],
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $questionList
     * @param Collection<int, array<string, mixed>> $perguntas
     * @param array<int, array<string, mixed>> $biMatrizes
     * @return array<int, array<string, mixed>>
     */
    private function buildQuestionBlocks(
        Collection $questionList,
        Collection $perguntas,
        array $biMatrizes,
        Collection $responses,
        Request $request,
        array $tokenMunicipioMap
    ): array
    {
        $simpleByCode = $perguntas->keyBy('id');
        $matrixByCode = collect($biMatrizes)->keyBy('codigo');
        $singleSubquestionGrouped = $this->groupSingleSubquestionQuestions(
            $perguntas,
            $questionList,
            $responses,
            $request,
            $tokenMunicipioMap
        );

        $rootQuestions = $questionList
            ->filter(fn (array $q) => $q['parent_qid'] === '0')
            ->filter(fn (array $q) => !$this->shouldIgnoreQuestionMeta($q))
            ->sortBy([
                ['question_number', 'asc'],
                ['question_order', 'asc'],
                ['code', 'asc'],
            ])
            ->values();

        $blocks = [];
        $usedSimple = [];
        $usedMatrix = [];

        foreach ($rootQuestions as $root) {
            $code = (string) ($root['code'] ?? '');
            if ($code === '') {
                continue;
            }
            $orderBase = ((int) ($root['question_number'] ?? 999999) * 1000) + (int) ($root['question_order'] ?? 0);
            $rootType = strtoupper((string) ($root['type'] ?? ''));

            if ($matrixByCode->has($code)) {
                $matrix = $matrixByCode->get($code);
                if (is_array($matrix)) {
                    $blocks[] = [
                        'id' => $code,
                        'order' => $orderBase,
                        'kind' => 'matrix',
                        'title' => $root['text'] ?? $code,
                        'matrix' => $matrix,
                    ];
                    $usedMatrix[$code] = true;
                    continue;
                }
            }

            if ($rootType === 'L') {
                $listRadio = $this->buildMunicipioLevelQuestion($root, $responses, $request, $tokenMunicipioMap, $questionList);
                if (is_array($listRadio)) {
                    $blocks[] = [
                        'id' => $code,
                        'order' => $orderBase,
                        'kind' => 'simple',
                        'title' => $root['text'] ?? $code,
                        'question' => $listRadio,
                    ];
                    $usedSimple[$code] = true;
                    continue;
                }
            }

            if ($rootType === 'P') {
                $multiChoice = $this->buildMunicipioMultipleChoiceQuestion($root, $responses, $request, $tokenMunicipioMap, $questionList);
                if (is_array($multiChoice)) {
                    $blocks[] = [
                        'id' => $code,
                        'order' => $orderBase,
                        'kind' => 'simple',
                        'title' => $root['text'] ?? $code,
                        'question' => $multiChoice,
                    ];
                    foreach (($multiChoice['source_ids'] ?? []) as $sourceId) {
                        $usedSimple[(string) $sourceId] = true;
                    }
                    continue;
                }
            }

            if ($simpleByCode->has($code)) {
                $simple = $simpleByCode->get($code);
                if (is_array($simple)) {
                    $blocks[] = [
                        'id' => $code,
                        'order' => $orderBase,
                        'kind' => 'simple',
                        'title' => $root['text'] ?? $code,
                        'question' => $simple,
                    ];
                    $usedSimple[$code] = true;
                    continue;
                }
            }

            if ($singleSubquestionGrouped->has($code)) {
                $grouped = $singleSubquestionGrouped->get($code);
                if (is_array($grouped)) {
                    $blocks[] = [
                        'id' => $code,
                        'order' => $orderBase,
                        'kind' => 'simple',
                        'title' => $root['text'] ?? $code,
                        'question' => $grouped,
                    ];

                    foreach (($grouped['source_ids'] ?? []) as $sourceId) {
                        $usedSimple[(string) $sourceId] = true;
                    }
                }
            }
        }

        foreach ($matrixByCode as $code => $matrix) {
            if (isset($usedMatrix[$code]) || !is_array($matrix)) {
                continue;
            }
            $blocks[] = [
                'id' => (string) $code,
                'order' => 999998,
                'kind' => 'matrix',
                'title' => $matrix['texto'] ?? (string) $code,
                'matrix' => $matrix,
            ];
        }

        foreach ($simpleByCode as $code => $simple) {
            if (isset($usedSimple[$code]) || !is_array($simple)) {
                continue;
            }
            $blocks[] = [
                'id' => (string) $code,
                'order' => 999999,
                'kind' => 'simple',
                'title' => $simple['texto'] ?? (string) $code,
                'question' => $simple,
            ];
        }

        usort($blocks, function (array $a, array $b) {
            $orderA = (int) ($a['order'] ?? 999999);
            $orderB = (int) ($b['order'] ?? 999999);
            if ($orderA !== $orderB) {
                return $orderA <=> $orderB;
            }

            return strcmp((string) ($a['id'] ?? ''), (string) ($b['id'] ?? ''));
        });

        return array_values($blocks);
    }

    /**
     * @param array<string, mixed> $root
     * @return array<string, mixed>|null
     */
    private function buildMunicipioLevelQuestion(
        array $root,
        Collection $responses,
        Request $request,
        array $tokenMunicipioMap,
        Collection $questionList
    ): ?array
    {
        $rootCode = (string) ($root['code'] ?? '');
        if ($rootCode === '') {
            return null;
        }

        $firstRow = $responses->first();
        $firstRow = is_array($firstRow) ? $firstRow : [];
        if (!array_key_exists($rootCode, $firstRow)) {
            return null;
        }

        $municipioField = $this->resolveMunicipioField($request, $questionList, $firstRow);
        $sumByMunicipio = [];
        $countByMunicipio = [];

        foreach ($responses as $row) {
            if (!is_array($row)) {
                continue;
            }

            $level = $this->toLikertLevel($row[$rootCode] ?? null);
            if ($level === null) {
                continue;
            }

            $municipio = $this->resolveMunicipioFromResponse($row, $municipioField, $tokenMunicipioMap);
            $sumByMunicipio[$municipio] = ($sumByMunicipio[$municipio] ?? 0.0) + $level;
            $countByMunicipio[$municipio] = ($countByMunicipio[$municipio] ?? 0) + 1;
        }

        if (empty($sumByMunicipio)) {
            return null;
        }

        $municipios = array_keys($sumByMunicipio);
        sort($municipios, SORT_NATURAL | SORT_FLAG_CASE);
        $levels = array_map(function (string $municipio) use ($sumByMunicipio, $countByMunicipio) {
            $count = max((int) ($countByMunicipio[$municipio] ?? 0), 1);
            return round(((float) ($sumByMunicipio[$municipio] ?? 0)) / $count, 2);
        }, $municipios);

        return [
            'id' => $rootCode,
            'texto' => $root['text'] ?? $rootCode,
            'tipo' => 'municipio_level',
            'total' => array_sum($countByMunicipio),
            'labels' => [],
            'values' => [],
            'media' => null,
            'resumo' => 'Nivel medio por municipio',
            'exemplos' => [],
            'respostas' => [],
            'municipio_labels' => $municipios,
            'municipio_levels' => $levels,
        ];
    }

    /**
     * @param array<string, mixed> $root
     * @return array<string, mixed>|null
     */
    private function buildMunicipioMultipleChoiceQuestion(
        array $root,
        Collection $responses,
        Request $request,
        array $tokenMunicipioMap,
        Collection $questionList
    ): ?array
    {
        $rootCode = (string) ($root['code'] ?? '');
        if ($rootCode === '') {
            return null;
        }

        $firstRow = $responses->first();
        $firstRow = is_array($firstRow) ? $firstRow : [];
        if (empty($firstRow)) {
            return null;
        }

        $optionColumns = collect(array_keys($firstRow))
            ->filter(fn (string $col) => preg_match('/^'.preg_quote($rootCode, '/').'\[([A-Za-z0-9]+)\]$/', $col) === 1)
            ->reject(fn (string $col) => str_ends_with(mb_strtolower($col), 'comment]') || str_ends_with(mb_strtolower($col), '[other]'))
            ->values();

        if ($optionColumns->isEmpty()) {
            return null;
        }

        $municipioField = $this->resolveMunicipioField($request, $questionList, $firstRow);
        $optionByMunicipio = [];
        $allMunicipios = [];
        $sourceIds = [];

        foreach ($responses as $row) {
            if (!is_array($row)) {
                continue;
            }

            $municipio = $this->resolveMunicipioFromResponse($row, $municipioField, $tokenMunicipioMap);
            $allMunicipios[$municipio] = true;

            foreach ($optionColumns as $columnName) {
                if (!preg_match('/^'.preg_quote($rootCode, '/').'\[([A-Za-z0-9]+)\]$/', $columnName, $match)) {
                    continue;
                }
                $subCode = $match[1];
                $sourceIds[] = $columnName;

                if (!$this->toBoolSelection($row[$columnName] ?? null)) {
                    continue;
                }

                if (!isset($optionByMunicipio[$municipio])) {
                    $optionByMunicipio[$municipio] = [];
                }
                $optionByMunicipio[$municipio][$subCode] = true;
            }
        }

        $municipios = array_keys($allMunicipios);
        sort($municipios, SORT_NATURAL | SORT_FLAG_CASE);

        $subCodes = $optionColumns
            ->map(function (string $columnName) use ($rootCode) {
                preg_match('/^'.preg_quote($rootCode, '/').'\[([A-Za-z0-9]+)\]$/', $columnName, $match);
                return $match[1] ?? null;
            })
            ->filter()
            ->unique()
            ->values();

        $series = [];
        $totals = [];
        foreach ($subCodes as $subCode) {
            $subQuestion = $this->findSubQuestionByCode($questionList, $rootCode, (string) $subCode);
            $label = (string) ($subQuestion['text'] ?? $subCode);
            $data = [];
            $countMunicipios = 0;

            foreach ($municipios as $municipio) {
                $selected = !empty($optionByMunicipio[$municipio][(string) $subCode]);
                $value = $selected ? 1 : 0;
                $data[] = $value;
                if ($value === 1) {
                    $countMunicipios++;
                }
            }

            $series[] = [
                'code' => (string) $subCode,
                'label' => $label,
                'data' => $data,
            ];
            $totals[] = [
                'label' => $label,
                'value' => $countMunicipios,
            ];
        }

        usort($totals, fn (array $a, array $b) => ($b['value'] <=> $a['value']));

        return [
            'id' => $rootCode,
            'texto' => $root['text'] ?? $rootCode,
            'tipo' => 'municipio_multiselect',
            'total' => $responses->count(),
            'labels' => [],
            'values' => [],
            'media' => null,
            'resumo' => 'Articulacao por secretaria e municipio',
            'exemplos' => [],
            'respostas' => [],
            'source_ids' => array_values(array_unique($sourceIds)),
            'municipio_labels' => $municipios,
            'municipio_series' => $series,
            'totais_labels' => array_map(fn (array $item) => $item['label'], $totals),
            'totais_values' => array_map(fn (array $item) => $item['value'], $totals),
        ];
    }

    private function resolveSurveyId(Request $request): int
    {
        $surveyId = (int) ($request->integer('survey_id') ?: config('services.limesurvey.survey_id'));
        if ($surveyId <= 0) {
            throw new \RuntimeException('Defina LIMESURVEY_SURVEY_ID ou envie survey_id na query string.');
        }

        return $surveyId;
    }

    /**
     * @param array<int, array<string, mixed>> $questions
     * @return Collection<int, array{code: string, text: string, type: string, qid: string, parent_qid: string, scale_id: string, question_order: int, question_number: int}>
     */
    private function normalizeQuestions(array $questions): Collection
    {
        return collect($questions)
            ->map(function (array $question) {
                $code = trim((string) ($question['title'] ?? $question['code'] ?? ''));
                if ($code === '') {
                    return null;
                }

                return [
                    'code' => $code,
                    'text' => $this->normalizeQuestionText((string) ($question['question'] ?? $question['text'] ?? $code)),
                    'type' => (string) ($question['type'] ?? ''),
                    'qid' => (string) ($question['qid'] ?? $question['id'] ?? ''),
                    'parent_qid' => (string) ($question['parent_qid'] ?? '0'),
                    'scale_id' => (string) ($question['scale_id'] ?? '0'),
                    'question_order' => (int) ($question['question_order'] ?? 999999),
                    'question_number' => $this->extractQuestionNumber((string) ($question['question'] ?? $question['text'] ?? '')),
                ];
            })
            ->filter()
            ->values();
    }

    private function extractQuestionNumber(string $text): int
    {
        $normalized = $this->normalizeQuestionText($text);
        if (preg_match('/^\s*(\d{1,4})\b/u', $normalized, $match)) {
            return (int) $match[1];
        }

        return 999999;
    }

    private function normalizeQuestionText(string $text): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $stripped = strip_tags($decoded);
        $normalized = preg_replace('/\s+/u', ' ', trim($stripped));

        return $normalized !== null && $normalized !== '' ? $normalized : trim($text);
    }

    /**
     * @param Collection<int, array<string, mixed>> $responses
     * @param Collection<int, string> $excludedColumns
     * @return Collection<int, array<string, mixed>>
     */
    private function buildPerguntas(Collection $questionList, Collection $responses, Collection $excludedColumns): Collection
    {
        if ($responses->isEmpty()) {
            return collect();
        }

        $metadataColumns = [
            'id',
            'submitdate',
            'lastpage',
            'startlanguage',
            'seed',
            'token',
            'ipaddr',
            'datestamp',
            'startdate',
        ];

        $columns = collect($responses->first())->keys()
            ->reject(fn (string $column) => in_array(strtolower($column), $metadataColumns, true))
            ->reject(fn (string $column) => $excludedColumns->contains($column))
            ->reject(fn (string $column) => $this->parseMatrixColumnKey($column) !== null)
            ->reject(fn (string $column) => $this->shouldIgnoreQuestionColumn($column, $questionList));

        return $columns->map(function (string $column) use ($responses, $questionList) {
            $respostas = $responses
                ->pluck($column)
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn (string $value) => $value !== '')
                ->values();

            $tipo = $this->inferTipo($respostas);
            $meta = $this->findQuestionByCode($questionList, $column) ?? ['text' => $column, 'type' => ''];

            $bloco = [
                'id' => $column,
                'texto' => $meta['text'],
                'tipo' => $tipo,
                'total' => $respostas->count(),
                'labels' => [],
                'values' => [],
                'media' => null,
                'resumo' => null,
                'exemplos' => [],
                'respostas' => [],
            ];

            if ($tipo === 'texto') {
                $bloco['respostas'] = $respostas->take(200)->all();
                $bloco['exemplos'] = $respostas->take(5)->all();
                return $bloco;
            }

            if ($tipo === 'boolean') {
                $sim = $respostas->filter(fn (string $v) => $this->toBool($v) === true)->count();
                $nao = $respostas->filter(fn (string $v) => $this->toBool($v) === false)->count();
                $totalBool = max($sim + $nao, 1);
                $bloco['labels'] = ['Sim', 'Nao'];
                $bloco['values'] = [$sim, $nao];
                $bloco['resumo'] = round(($sim / $totalBool) * 100) . '% de sim';
                return $bloco;
            }

            $contagem = $respostas->countBy()->sortDesc();
            $bloco['labels'] = $contagem->keys()->values()->all();
            $bloco['values'] = $contagem->values()->all();

            $numeros = $respostas->filter(fn (string $v) => is_numeric($v))->map(fn (string $v) => (float) $v);
            if ($numeros->isNotEmpty()) {
                $media = round((float) $numeros->avg(), 2);
                $bloco['media'] = $media;
                $bloco['resumo'] = 'Media ' . number_format($media, 2, ',', '.');
            }

            return $bloco;
        })->values();
    }

    /**
     * @param Collection<int, array<string, mixed>> $responses
     * @return array<int, array<string, mixed>>
     */
    private function buildBiMatrizes(Collection $questionList, Collection $responses, Request $request, array $tokenMunicipioMap): array
    {
        if ($responses->isEmpty()) {
            return [];
        }

        $firstRow = $responses->first();
        if (!is_array($firstRow) || empty($firstRow)) {
            return [];
        }

        $matrixColumns = collect(array_keys($firstRow))
            ->map(fn (string $column) => $this->parseMatrixColumnKey($column))
            ->filter()
            ->groupBy('question_code');

        if ($matrixColumns->isEmpty()) {
            return [];
        }

        $municipioField = $this->resolveMunicipioField($request, $questionList, $firstRow);
        $questionsByQid = $questionList
            ->filter(fn (array $question) => $question['qid'] !== '')
            ->keyBy('qid');

        $result = [];

        foreach ($matrixColumns as $questionCode => $columnsInfo) {
            $parentQuestion = $this->findRootQuestionByCode($questionList, (string) $questionCode);
            if (!$parentQuestion) {
                continue;
            }

            $parentQid = $parentQuestion['qid'] ?? '';
            $rowQuestions = $questionsByQid
                ->filter(fn (array $question) => $question['parent_qid'] === $parentQid && $question['scale_id'] === '0')
                ->keyBy('code');

            $columnQuestions = $questionsByQid
                ->filter(fn (array $question) => $question['parent_qid'] === $parentQid && $question['scale_id'] === '1')
                ->keyBy('code');

            $columns = collect($columnsInfo)->pluck('column')->unique()->values()->all();
            $rowCodes = collect($columnsInfo)->pluck('row_code')->unique()->values();
            $columnCodes = collect($columnsInfo)->pluck('column_code')->unique()->values();

            $allChildQuestions = $questionsByQid
                ->filter(fn (array $question) => $question['parent_qid'] === $parentQid)
                ->keyBy('code');

            $columnMeta = $columnCodes
                ->map(fn (string $columnCode) => $this->resolveMatrixColumnMeta((string) $questionCode, $columnCode, $columnQuestions, $allChildQuestions))
                ->keyBy('code');

            $lineData = [];
            $municipiosSet = [];
            $anosSet = [];
            $medidasSet = [];

            foreach ($responses as $responseRow) {
                if (!is_array($responseRow)) {
                    continue;
                }

                $municipio = $this->resolveMunicipioFromResponse($responseRow, $municipioField, $tokenMunicipioMap);
                $municipiosSet[$municipio] = true;

                foreach ($columnsInfo as $columnInfo) {
                    if (!is_array($columnInfo)) {
                        continue;
                    }

                    $columnName = (string) ($columnInfo['column'] ?? '');
                    $rowCode = (string) ($columnInfo['row_code'] ?? '');
                    $columnCode = (string) ($columnInfo['column_code'] ?? '');

                    $value = $this->toNumeric($responseRow[$columnName] ?? null);
                    if ($value === null) {
                        continue;
                    }

                    $meta = $columnMeta->get($columnCode, [
                        'code' => $columnCode,
                        'label' => $columnCode,
                        'year' => $columnCode,
                        'measure' => 'Valor',
                    ]);

                    $year = (string) ($meta['year'] ?? $columnCode);
                    $measure = (string) ($meta['measure'] ?? 'Valor');

                    $anosSet[$year] = true;
                    $medidasSet[$measure] = true;

                    if (!isset($lineData[$rowCode])) {
                        $lineData[$rowCode] = [];
                    }
                    if (!isset($lineData[$rowCode][$measure])) {
                        $lineData[$rowCode][$measure] = [];
                    }
                    if (!isset($lineData[$rowCode][$measure][$year])) {
                        $lineData[$rowCode][$measure][$year] = [];
                    }
                    if (!isset($lineData[$rowCode][$measure][$year][$municipio])) {
                        $lineData[$rowCode][$measure][$year][$municipio] = 0.0;
                    }

                    $lineData[$rowCode][$measure][$year][$municipio] += $value;
                }
            }

            $municipios = array_keys($municipiosSet);
            sort($municipios, SORT_NATURAL | SORT_FLAG_CASE);

            $anos = array_keys($anosSet);
            usort($anos, fn (string $a, string $b) => strcmp($a, $b));

            $medidas = array_keys($medidasSet);
            sort($medidas, SORT_NATURAL | SORT_FLAG_CASE);

            $linhas = $rowCodes
                ->map(function (string $rowCode) use ($rowQuestions) {
                    $label = $rowQuestions->get($rowCode)['text'] ?? $rowCode;
                    return [
                        'codigo' => $rowCode,
                        'label' => $label,
                    ];
                })
                ->values()
                ->all();

            $colunas = $columnCodes
                ->map(fn (string $columnCode) => $columnMeta->get($columnCode, [
                    'code' => $columnCode,
                    'label' => $columnCode,
                    'year' => $columnCode,
                    'measure' => 'Valor',
                ]))
                ->values()
                ->all();

            if (empty($lineData)) {
                continue;
            }

            $result[] = [
                'codigo' => $questionCode,
                'texto' => $parentQuestion['text'],
                'municipio_field' => $municipioField,
                'tokens_mapeados' => count($tokenMunicipioMap),
                'columns' => $columns,
                'linhas' => $linhas,
                'colunas' => $colunas,
                'anos' => $anos,
                'medidas' => $medidas,
                'municipios' => $municipios,
                'valores' => $lineData,
            ];
        }

        return $result;
    }

    /**
     * @return array{column: string, question_code: string, row_code: string, column_code: string}|null
     */
    private function parseMatrixColumnKey(string $column): ?array
    {
        if (!preg_match('/^([A-Za-z0-9]+)\[([A-Za-z0-9]+)_([A-Za-z0-9]+)\]$/', $column, $matches)) {
            return null;
        }

        return [
            'column' => $column,
            'question_code' => $matches[1],
            'row_code' => $matches[2],
            'column_code' => $matches[3],
        ];
    }

    /**
     * @return array{code: string, label: string, year: string, measure: string}
     */
    private function resolveMatrixColumnMeta(string $questionCode, string $columnCode, Collection $columnQuestions, Collection $allChildQuestions): array
    {
        $defaultMap = [
            'G01Q06' => [
                'SQ004' => '2022 Matriculados',
                'SQ005' => '2022 Evadidos',
                'SQ006' => '2023 Matriculados',
                'SQ007' => '2023 Evadidos',
                'SQ008' => '2024 Matriculados',
                'SQ009' => '2024 Evadidos',
            ],
        ];

        $customMapRaw = config('services.limesurvey.matrix_column_labels');
        $customMap = [];
        if (is_string($customMapRaw) && trim($customMapRaw) !== '') {
            $decoded = json_decode($customMapRaw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $customMap = $decoded;
            }
        }

        $questionLabel = $columnQuestions->get($columnCode)['text'] ?? null;
        $fallbackChildLabel = $allChildQuestions->get($columnCode)['text'] ?? null;
        $label = (string) ($customMap[$questionCode][$columnCode] ?? $defaultMap[$questionCode][$columnCode] ?? $questionLabel ?? $fallbackChildLabel ?? $columnCode);

        $year = $label;
        if (preg_match('/(20\d{2})/', $label, $yearMatch)) {
            $year = $yearMatch[1];
        }

        $measure = 'Valor';
        $labelLower = mb_strtolower($label);
        if (str_contains($labelLower, 'matric')) {
            $measure = 'Matriculados';
        } elseif (str_contains($labelLower, 'evad')) {
            $measure = 'Evadidos';
        }

        return [
            'code' => $columnCode,
            'label' => $label,
            'year' => $year,
            'measure' => $measure,
        ];
    }

    /**
     * @param array<string, mixed> $firstRow
     */
    private function resolveMunicipioField(Request $request, Collection $questionMap, array $firstRow): ?string
    {
        $queryField = trim((string) $request->query('municipio_field', ''));
        if ($queryField !== '' && array_key_exists($queryField, $firstRow)) {
            return $queryField;
        }

        $configField = trim((string) config('services.limesurvey.municipio_field', ''));
        if ($configField !== '' && array_key_exists($configField, $firstRow)) {
            return $configField;
        }

        $detected = $questionMap
            ->filter(function (array $question) use ($firstRow) {
                $text = mb_strtolower($question['text']);
                return $question['parent_qid'] === '0'
                    && str_contains($text, 'munic')
                    && array_key_exists($question['code'], $firstRow);
            })
            ->first();

        return is_array($detected) ? $detected['code'] : null;
    }

    /**
     * @return array{code: string, text: string, type: string, qid: string, parent_qid: string}|null
     */
    private function findQuestionByCode(Collection $questionList, string $code): ?array
    {
        $root = $questionList->first(fn (array $question) => $question['code'] === $code && $question['parent_qid'] === '0');
        if (is_array($root)) {
            return $root;
        }

        $any = $questionList->first(fn (array $question) => $question['code'] === $code);
        return is_array($any) ? $any : null;
    }

    /**
     * @return array{code: string, text: string, type: string, qid: string, parent_qid: string}|null
     */
    private function findRootQuestionByCode(Collection $questionList, string $code): ?array
    {
        $root = $questionList->first(fn (array $question) => $question['code'] === $code && $question['parent_qid'] === '0');
        return is_array($root) ? $root : null;
    }

    /**
     * @param Collection<int, array<string, mixed>> $perguntas
     * @param Collection<int, array<string, mixed>> $questionList
     * @param Collection<int, array<string, mixed>> $responses
     * @param array<string, string> $tokenMunicipioMap
     * @return Collection<string, array<string, mixed>>
     */
    private function groupSingleSubquestionQuestions(
        Collection $perguntas,
        Collection $questionList,
        Collection $responses,
        Request $request,
        array $tokenMunicipioMap
    ): Collection
    {
        $grouped = [];
        $subColumnsByRoot = [];

        foreach ($perguntas as $pergunta) {
            if (!is_array($pergunta)) {
                continue;
            }

            $id = (string) ($pergunta['id'] ?? '');
            if (!preg_match('/^([A-Za-z0-9]+)\[([A-Za-z0-9]+)\]$/', $id, $match)) {
                continue;
            }

            $rootCode = $match[1];
            $subCode = $match[2];
            $rootMeta = $this->findRootQuestionByCode($questionList, $rootCode);
            $subQuestion = $this->findSubQuestionByCode($questionList, $rootCode, $subCode);
            $subLabel = $subQuestion['text'] ?? ($pergunta['texto'] ?? $subCode);

            if (!isset($grouped[$rootCode])) {
                $grouped[$rootCode] = [
                    'id' => $rootCode,
                    'texto' => $rootMeta['text'] ?? $rootCode,
                    'tipo' => 'escala',
                    'total' => 0,
                    'labels' => [],
                    'values' => [],
                    'media' => null,
                    'resumo' => null,
                    'exemplos' => [],
                    'respostas' => [],
                    'source_ids' => [],
                    '__items' => [],
                    '__root_type' => strtoupper((string) ($rootMeta['type'] ?? '')),
                ];
            }

            $grouped[$rootCode]['total'] += (int) ($pergunta['total'] ?? 0);
            $grouped[$rootCode]['source_ids'][] = $id;
            $grouped[$rootCode]['__items'][] = [
                'id' => $id,
                'sub_code' => $subCode,
                'sub_label' => $subLabel,
                'total' => (int) ($pergunta['total'] ?? 0),
                'order' => (int) ($subQuestion['question_order'] ?? 999999),
            ];
            $subColumnsByRoot[$rootCode][$subCode] = $id;
        }

        foreach ($grouped as $rootCode => $item) {
            usort($item['__items'], function (array $a, array $b) {
                if ($a['order'] !== $b['order']) {
                    return $a['order'] <=> $b['order'];
                }
                return strcmp((string) $a['sub_code'], (string) $b['sub_code']);
            });

            $item['labels'] = array_values(array_map(fn (array $i) => (string) $i['sub_label'], $item['__items']));
            $item['values'] = array_values(array_map(fn (array $i) => (int) $i['total'], $item['__items']));
            $item['resumo'] = 'Campos preenchidos por subquestao';

            $rootType = (string) ($item['__root_type'] ?? '');
            $isArrayColumnLikert = $rootType === 'H';
            $item['tipo'] = 'municipio_series';
            $item['chart_mode'] = $isArrayColumnLikert ? 'grouped' : 'stacked';
            if ($isArrayColumnLikert) {
                $item['resumo'] = 'Nivel medio por subquestao em cada municipio';
            }
            $item['municipio_labels'] = [];
            $item['municipio_series'] = [];

            $firstResponse = $responses->first();
            $firstResponse = is_array($firstResponse) ? $firstResponse : [];
            $municipioField = $this->resolveMunicipioField($request, $questionList, $firstResponse);

            $seriesData = [];
            $seriesCount = [];
            $municipiosSet = [];
            $subCols = $subColumnsByRoot[$rootCode] ?? [];
            foreach ($responses as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $municipio = $this->resolveMunicipioFromResponse($row, $municipioField, $tokenMunicipioMap);
                $municipiosSet[$municipio] = true;

                foreach ($subCols as $subCode => $columnName) {
                    $rawValue = $row[$columnName] ?? null;
                    $value = $isArrayColumnLikert
                        ? $this->toLikertLevel($rawValue)
                        : ($this->toNumeric($rawValue) ?? $this->toLikertLevel($rawValue));
                    if ($value === null) {
                        continue;
                    }

                    if (!isset($seriesData[$subCode])) {
                        $seriesData[$subCode] = [];
                    }
                    if (!isset($seriesData[$subCode][$municipio])) {
                        $seriesData[$subCode][$municipio] = 0.0;
                    }
                    if (!isset($seriesCount[$subCode])) {
                        $seriesCount[$subCode] = [];
                    }
                    if (!isset($seriesCount[$subCode][$municipio])) {
                        $seriesCount[$subCode][$municipio] = 0;
                    }

                    $seriesData[$subCode][$municipio] += $value;
                    $seriesCount[$subCode][$municipio]++;
                }
            }

            $municipios = array_keys($municipiosSet);
            sort($municipios, SORT_NATURAL | SORT_FLAG_CASE);
            $item['municipio_labels'] = $municipios;

            $series = [];
            foreach ($item['__items'] as $subItem) {
                $subCode = (string) $subItem['sub_code'];
                $series[] = [
                    'code' => $subCode,
                    'label' => (string) $subItem['sub_label'],
                    'data' => array_map(function (string $m) use ($isArrayColumnLikert, $seriesData, $seriesCount, $subCode) {
                        $sum = (float) ($seriesData[$subCode][$m] ?? 0);
                        if (!$isArrayColumnLikert) {
                            return $sum;
                        }

                        $count = (int) ($seriesCount[$subCode][$m] ?? 0);
                        if ($count <= 0) {
                            return 0.0;
                        }

                        return round($sum / $count, 2);
                    }, $municipios),
                ];
            }
            $item['municipio_series'] = $series;

            unset($item['__items']);
            unset($item['__root_type']);

            $grouped[$rootCode] = $item;
        }

        return collect($grouped);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findSubQuestionByCode(Collection $questionList, string $rootCode, string $subCode): ?array
    {
        $root = $this->findRootQuestionByCode($questionList, $rootCode);
        if (!$root) {
            return null;
        }

        $rootQid = (string) ($root['qid'] ?? '');
        if ($rootQid === '') {
            return null;
        }

        $sub = $questionList->first(function (array $question) use ($rootQid, $subCode) {
            return (string) ($question['parent_qid'] ?? '') === $rootQid
                && (string) ($question['code'] ?? '') === $subCode;
        });

        return is_array($sub) ? $sub : null;
    }

    private function shouldIgnoreQuestionColumn(string $column, Collection $questionList): bool
    {
        if (str_ends_with(mb_strtolower($column), 'comment]') || str_ends_with(mb_strtolower($column), '[other]')) {
            return true;
        }

        $meta = $this->findQuestionByCode($questionList, $column);
        if (!is_array($meta)) {
            return false;
        }

        $text = mb_strtolower(trim((string) ($meta['text'] ?? '')));
        if ($text === '') {
            return false;
        }

        if (str_contains($text, 'municipio') || str_contains($text, 'município')) {
            return true;
        }

        if (str_contains($text, 'observa') || str_contains($text, 'coment')) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, mixed> $question
     */
    private function shouldIgnoreQuestionMeta(array $question): bool
    {
        $text = mb_strtolower(trim((string) ($question['text'] ?? '')));
        if ($text === '') {
            return false;
        }

        if (str_contains($text, 'municipio') || str_contains($text, 'município')) {
            return true;
        }

        if (str_contains($text, 'observa') || str_contains($text, 'coment')) {
            return true;
        }

        return false;
    }

    private function normalizeMunicipio(mixed $value): string
    {
        $text = trim((string) $value);
        return $text !== '' ? $text : 'Nao informado';
    }

    /**
     * @return array<string, string>
     */
    private function buildTokenMunicipioMap(int $surveyId): array
    {
        try {
            $participants = $this->client->listParticipants($surveyId, 0, 10000, false);
        } catch (\Throwable) {
            return [];
        }

        if (empty($participants)) {
            return [];
        }

        $emailMunicipio = $this->buildEmailMunicipioLookup();
        if (empty($emailMunicipio)) {
            return [];
        }

        $tokenMunicipio = [];

        foreach ($participants as $participant) {
            if (!is_array($participant)) {
                continue;
            }

            $token = trim((string) ($participant['token'] ?? ''));
            if ($token === '') {
                continue;
            }

            $participantInfo = $participant['participant_info'] ?? [];
            $email = '';
            if (is_array($participantInfo)) {
                $email = mb_strtolower(trim((string) ($participantInfo['email'] ?? '')));
            }

            if ($email === '' || !isset($emailMunicipio[$email])) {
                continue;
            }

            $tokenMunicipio[$token] = $emailMunicipio[$email];
        }

        return $tokenMunicipio;
    }

    /**
     * @return array<string, string>
     */
    private function buildEmailMunicipioLookup(): array
    {
        $lookup = [];

        if (Schema::hasColumn('municipios', 'interlocutor_email')) {
            $municipios = DB::table('municipios')
                ->select('nome', 'interlocutor_email')
                ->whereNotNull('interlocutor_email')
                ->get();

            foreach ($municipios as $municipio) {
                $email = mb_strtolower(trim((string) ($municipio->interlocutor_email ?? '')));
                $nome = trim((string) ($municipio->nome ?? ''));
                if ($email !== '' && $nome !== '') {
                    $lookup[$email] = $nome;
                }
            }
        }

        $usuarioParticipante = DB::table('users')
            ->join('participantes', 'participantes.user_id', '=', 'users.id')
            ->join('municipios', 'municipios.id', '=', 'participantes.municipio_id')
            ->select('users.email', 'municipios.nome')
            ->whereNull('users.deleted_at')
            ->whereNull('participantes.deleted_at')
            ->whereNull('municipios.deleted_at')
            ->get();

        foreach ($usuarioParticipante as $item) {
            $email = mb_strtolower(trim((string) ($item->email ?? '')));
            $nome = trim((string) ($item->nome ?? ''));
            if ($email !== '' && $nome !== '' && !isset($lookup[$email])) {
                $lookup[$email] = $nome;
            }
        }

        foreach ($this->defaultEmailMunicipioMap() as $email => $nomeMunicipio) {
            $emailNorm = mb_strtolower(trim((string) $email));
            $nomeNorm = trim((string) $nomeMunicipio);
            if ($emailNorm !== '' && $nomeNorm !== '' && !isset($lookup[$emailNorm])) {
                $lookup[$emailNorm] = $nomeNorm;
            }
        }

        return $lookup;
    }

    /**
     * @return array<string, string>
     */
    private function defaultEmailMunicipioMap(): array
    {
        return [
            'ausilenebraga4006@gmail.com' => 'CARAUARI',
            'sarmentonajar@gmail.com' => 'COARI',
            'valcienegarcia@gmail.com' => 'OIAPOQUE',
            'manuella.porto@semec.belem.pa.gov.br' => 'BELEM',
            'osvaldo.melo@educacao.fortaleza.ce.gov.br' => 'FORTALEZA',
            'janainaguedes1006@gmail.com' => 'CAUCAIA',
            'thtbmaia@gmail.com' => 'ICAPUI',
            'eleonez@bol.com.br' => 'ALTO DO RODRIGUES',
            'myziara.miranda@educacao.ipojuca.pe.gov.br' => 'IPOJUCA',
            'coordenacaoejaicabo25@gmail.com' => 'CABO DE SANTO AGOSTINHO',
            'torres.lucas77@yahoo.com.br' => 'BREJO GRANDE',
            'mariaizabelpassos@outlook.com' => 'SANTA LUZIA DO ITANHY',
            'andersoneduardolopes@gmail.com' => 'CONDE',
            'supervisaotecanosfinais.eja@gmail.com' => 'ARACAS',
            'marciamarino@gmail.com' => 'SAO FRANCISCO DO CONDE',
        ];
    }

    /**
     * @param array<string, mixed> $responseRow
     * @param array<string, string> $tokenMunicipioMap
     */
    private function resolveMunicipioFromResponse(array $responseRow, ?string $municipioField, array $tokenMunicipioMap): string
    {
        $token = trim((string) ($responseRow['token'] ?? ''));
        if ($token !== '' && isset($tokenMunicipioMap[$token])) {
            return $this->normalizeMunicipio($tokenMunicipioMap[$token]);
        }

        $municipioRaw = $municipioField ? ($responseRow[$municipioField] ?? null) : null;
        return $this->normalizeMunicipio($municipioRaw);
    }

    private function toNumeric(mixed $value): ?float
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $normalized = str_replace(['.', ','], ['', '.'], $text);
        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function toLikertLevel(mixed $value): ?float
    {
        $text = trim((string) $value);
        if ($text === '') {
            return null;
        }

        $numeric = $this->toNumeric($text);
        if ($numeric !== null) {
            return $numeric;
        }

        if (preg_match('/^AO\s*0*([1-9]\d*)$/i', $text, $match)) {
            return (float) $match[1];
        }

        if (preg_match('/^A\s*0*([1-9]\d*)$/i', $text, $match)) {
            return (float) $match[1];
        }

        return null;
    }

    private function toBoolSelection(mixed $value): bool
    {
        $text = mb_strtolower(trim((string) $value));
        if ($text === '') {
            return false;
        }

        if (in_array($text, ['n', 'no', 'nao', 'false', '0'], true)) {
            return false;
        }

        if (in_array($text, ['y', 'yes', 'sim', 'true', '1', 'on', 'x', 'checked'], true)) {
            return true;
        }

        if (preg_match('/^ao\s*0*([1-9]\d*)$/i', $text) === 1) {
            return true;
        }

        $numeric = $this->toNumeric($text);
        if ($numeric !== null) {
            return $numeric > 0;
        }

        return true;
    }

    /**
     * @param Collection<int, string> $respostas
     */
    private function inferTipo(Collection $respostas): string
    {
        if ($respostas->isEmpty()) {
            return 'texto';
        }

        $normalizadas = $respostas->map(fn (string $v) => mb_strtolower(trim($v)));
        $unicas = $normalizadas->unique()->values();
        $booleanSet = ['y', 'n', 'yes', 'no', 'sim', 'nao', 'true', 'false', '1', '0'];

        if ($unicas->every(fn (string $v) => in_array($v, $booleanSet, true))) {
            return 'boolean';
        }

        if ($respostas->every(fn (string $v) => is_numeric($v))) {
            return $unicas->count() <= 10 ? 'escala' : 'numero';
        }

        $mediaTamanho = (float) $respostas->map(fn (string $v) => mb_strlen($v))->avg();
        $diversidade = $unicas->count() / max($respostas->count(), 1);

        if ($mediaTamanho > 25 || $diversidade > 0.6) {
            return 'texto';
        }

        return 'escala';
    }

    private function toBool(string $value): ?bool
    {
        $v = mb_strtolower(trim($value));
        if (in_array($v, ['y', 'yes', 'sim', 'true', '1'], true)) {
            return true;
        }

        if (in_array($v, ['n', 'no', 'nao', 'false', '0'], true)) {
            return false;
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $responses
     * @return array<int, array<string, mixed>>
     */
    private function applyDateFilter(array $responses, Request $request): array
    {
        $de = $request->date('de');
        $ate = $request->date('ate');

        if (!$de && !$ate) {
            return $responses;
        }

        return collect($responses)
            ->filter(function (array $row) use ($de, $ate) {
                $raw = $row['submitdate'] ?? $row['datestamp'] ?? $row['startdate'] ?? null;
                if (!is_string($raw) || trim($raw) === '') {
                    return false;
                }

                try {
                    $date = Carbon::parse($raw);
                } catch (\Throwable) {
                    return false;
                }

                if ($de && $date->lt($de->startOfDay())) {
                    return false;
                }

                if ($ate && $date->gt($ate->endOfDay())) {
                    return false;
                }

                return true;
            })
            ->values()
            ->all();
    }

    private function resolveUltimaResposta(Collection $responses): ?string
    {
        $ultima = $responses
            ->map(fn (array $row) => $row['submitdate'] ?? $row['datestamp'] ?? $row['startdate'] ?? null)
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(function (string $value) {
                try {
                    return Carbon::parse($value);
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter()
            ->sortDesc()
            ->first();

        return $ultima?->format('d/m/Y H:i');
    }
}
