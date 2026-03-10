<?php

namespace App\Services\LimeSurvey;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LimeSurveyClient
{
    private string $url;
    private string $username;
    private string $password;
    private bool $verifySsl;
    private string $caFile;
    private int $timeout;

    public function __construct()
    {
        $this->url = (string) config('services.limesurvey.url', '');
        $this->username = (string) config('services.limesurvey.username', '');
        $this->password = (string) config('services.limesurvey.password', '');
        $this->verifySsl = (bool) config('services.limesurvey.verify_ssl', true);
        $this->caFile = (string) config('services.limesurvey.cafile', '');
        $this->timeout = (int) config('services.limesurvey.timeout', 30);
    }

    public function listQuestions(int $surveyId): array
    {
        return $this->withSessionKey(function (string $sessionKey) use ($surveyId) {
            $result = $this->call('list_questions', [$sessionKey, $surveyId]);
            return is_array($result) ? $result : [];
        });
    }

    public function exportResponses(int $surveyId): array
    {
        return $this->withSessionKey(function (string $sessionKey) use ($surveyId) {
            $base64 = $this->call('export_responses', [
                $sessionKey,
                $surveyId,
                'csv',
                null,
                'complete',
                'code',
                'short',
            ]);

            if (!is_string($base64) || $base64 === '') {
                return [];
            }

            $csv = base64_decode($base64, true);
            if (!is_string($csv) || $csv === '') {
                return [];
            }

            return $this->parseCsv($csv);
        });
    }

    public function listParticipants(int $surveyId, int $start = 0, int $limit = 10000, bool $unused = false): array
    {
        return $this->withSessionKey(function (string $sessionKey) use ($surveyId, $start, $limit, $unused) {
            $result = $this->call('list_participants', [
                $sessionKey,
                $surveyId,
                $start,
                $limit,
                $unused,
            ]);

            return is_array($result) ? $result : [];
        });
    }

    public function listSurveys(?string $username = null, ?int $groupId = null): array
    {
        return $this->withSessionKey(function (string $sessionKey) use ($username, $groupId) {
            $result = $this->call('list_surveys', [$sessionKey, $username, $groupId]);
            return is_array($result) ? $result : [];
        });
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function parseCsv(string $csv): array
    {
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv) ?? $csv;
        $delimiter = $this->detectDelimiter($csv);

        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return [];
        }

        fwrite($stream, $csv);
        rewind($stream);

        $header = fgetcsv($stream, 0, $delimiter, '"', '\\');
        if (!is_array($header) || count($header) === 0) {
            fclose($stream);
            return [];
        }

        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
        }

        $rows = [];

        while (($values = fgetcsv($stream, 0, $delimiter, '"', '\\')) !== false) {
            if (!is_array($values)) {
                continue;
            }

            $assoc = [];
            foreach ($header as $index => $column) {
                $columnName = trim((string) $column);
                if ($columnName === '') {
                    continue;
                }

                $assoc[$columnName] = (string) ($values[$index] ?? '');
            }

            if (!empty($assoc)) {
                $rows[] = $assoc;
            }
        }

        fclose($stream);

        return $rows;
    }

    private function detectDelimiter(string $csv): string
    {
        $firstLine = strtok($csv, "\r\n");
        $firstLine = is_string($firstLine) ? $firstLine : '';

        $semicolonCount = substr_count($firstLine, ';');
        $commaCount = substr_count($firstLine, ',');

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    private function withSessionKey(callable $callback): mixed
    {
        $this->ensureConfigured();

        $sessionKey = $this->getSessionKey();
        if ($sessionKey === '') {
            throw new RuntimeException('LimeSurvey nao retornou session key valida.');
        }

        try {
            return $callback($sessionKey);
        } finally {
            try {
                $this->call('release_session_key', [$sessionKey]);
            } catch (\Throwable) {
                // Ignora erro no release para nao mascarar o erro principal.
            }
        }
    }

    private function getSessionKey(): string
    {
        $result = $this->call('get_session_key', [$this->username, $this->password]);
        return is_string($result) ? $result : '';
    }

    private function call(string $method, array $params): mixed
    {
        $payload = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => now()->getTimestampMs(),
        ];

        $response = $this->http()->post($this->url, $payload)->throw()->json();
        if (!is_array($response)) {
            throw new RuntimeException("Resposta invalida do LimeSurvey para metodo {$method}.");
        }

        if (isset($response['error']) && $response['error']) {
            $message = is_array($response['error'])
                ? ($response['error']['message'] ?? json_encode($response['error']))
                : (string) $response['error'];

            throw new RuntimeException("Erro no LimeSurvey ({$method}): {$message}");
        }

        return $response['result'] ?? null;
    }

    private function http(): PendingRequest
    {
        $verifyOption = $this->verifySsl;
        if ($this->caFile !== '' && is_file($this->caFile)) {
            $verifyOption = $this->caFile;
        }

        return Http::acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->withOptions(['verify' => $verifyOption]);
    }

    private function ensureConfigured(): void
    {
        if ($this->url === '' || $this->username === '' || $this->password === '') {
            throw new RuntimeException('Credenciais do LimeSurvey nao configuradas em config/services.php (.env).');
        }
    }
}
