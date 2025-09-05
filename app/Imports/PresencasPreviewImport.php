<?php

namespace App\Imports;

use App\Models\Municipio;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Carbon\Carbon;

class PresencasPreviewImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public Collection $rows;

    /** @var array<string,int> nome (lower) => municipio_id */
    protected array $municipiosCache = [];

    public function __construct()
    {
        $this->rows = collect();

        // Carrega cache de municípios (nome normalizado -> id)
        $this->municipiosCache = Municipio::with('estado')
            ->get(['id','nome','estado_id'])
            ->mapWithKeys(function ($m) {
                return [mb_strtolower(trim($m->nome)) => (int) $m->id];
            })
            ->toArray();
    }

    public function headingRow(): int
    {
        return 1;
    }

    // Colunas esperadas:
    // nome, email, cpf, telefone, municipio, escola_unidade, status, justificativa, data_entrada
    public function collection(Collection $rows): void
    {
        foreach ($rows as $r) {
            // Normaliza campos base
            $nome     = trim((string)($r['nome'] ?? ''));
            $email    = strtolower(trim((string)($r['email'] ?? '')));
            $cpf      = preg_replace('/\D+/', '', (string)($r['cpf'] ?? '')) ?: null;
            $telefone = preg_replace('/\D+/', '', (string)($r['telefone'] ?? '')) ?: null;
            $munNome  = trim((string)($r['municipio'] ?? ''));
            $escola   = trim((string)($r['escola_unidade'] ?? ''));
            $status   = $this->mapStatus((string)($r['status'] ?? ''));
            $justif   = trim((string)($r['justificativa'] ?? '')) ?: null;
            $entrada  = $this->parseDate($r['data_entrada'] ?? null);

            // Resolve municipio_id (se nome existir)
            $municipioId = null;
            if ($munNome !== '') {
                $key = mb_strtolower($munNome);
                $municipioId = $this->municipiosCache[$key] ?? null;
            }

            $this->rows->push([
                'nome'           => $nome,
                'email'          => $email,
                'cpf'            => $cpf,
                'telefone'       => $telefone,
                'municipio'      => $munNome,      // para exibir no input
                'municipio_id'   => $municipioId,  // para pré-selecionar no <select>
                'escola_unidade' => $escola,
                'status'         => $status,
                'justificativa'  => $justif,
                'data_entrada'   => $entrada,
            ]);
        }
    }

    private function mapStatus(string $raw): ?string
    {
        $s = mb_strtolower(trim($raw));
        return match ($s) {
            'presente','p','present'      => 'presente',
            'ausente','a','absent'        => 'ausente',
            'justificado','j','justify'   => 'justificado',
            default                       => null,
        };
    }

    private function parseDate($v): ?string
    {
        if (!$v) return null;
        try {
            if (is_numeric($v)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($v)->format('Y-m-d');
            }
            return Carbon::parse($v)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
