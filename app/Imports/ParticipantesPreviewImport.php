<?php

namespace App\Imports;

use App\Models\Municipio;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ParticipantesPreviewImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    /** @var \Illuminate\Support\Collection<array<string,mixed>> Linhas normalizadas para exibir na prévia */
    public Collection $rows;

    /** @var array<string,int> Cache: nome_do_municipio_lower => id */
    protected array $municipiosCache = [];
    /** @var array<int,string> */
    protected array $organizacoes = [];
    /** @var array<string,string> */
    protected array $organizacoesMap = [];

    public function __construct()
    {
        $this->rows = collect();

        // Pré-carrega municípios para não consultar a cada linha
        $this->municipiosCache = Municipio::query()
            ->select('id', 'nome')
            ->get()
            ->mapWithKeys(fn($m) => [mb_strtolower(trim($m->nome)) => $m->id])
            ->all();

        $this->organizacoes = config('engaja.organizacoes', []);
        $this->organizacoesMap = collect($this->organizacoes)
            ->mapWithKeys(fn($o) => [$this->slugify($o) => $o])
            ->all();
    }

    /** Primeira linha contém os cabeçalhos */
    public function headingRow(): int
    {
        return 1;
    }

    /**
     * Recebe TODAS as linhas da planilha (com cabeçalhos mapeados) e
     * transforma para um formato amigável de edição (NÃO persiste no banco).
     */
    public function collection(Collection $rows): void
    {
        $this->rows = $rows->map(function ($row) {
            $map = collect($row)->map(fn($v) => is_string($v) ? trim($v) : $v);

            // Resolve municipio_id via cache (se existir)
            $municipioNome = (string) ($map['municipio'] ?? '');
            $municipioId = null;
            if ($municipioNome !== '') {
                $key = mb_strtolower($municipioNome);
                $municipioId = $this->municipiosCache[$key] ?? null;
            }

            // Aceita "organizacao" OU "escola_unidade"
            $orgRaw   = (string) ($map['organizacao'] ?? $map['escola_unidade'] ?? '');
            $orgCanon = $this->normalizeOrganizacao($orgRaw); // null se não bater com a lista

            // ⚠️ Sem fallback: se não for canônico, fica null (ou string vazia na view)
            $orgOut   = $orgCanon;                // <<-- aqui mudou
            $orgOk    = ($orgRaw === '') ? true : ($orgCanon !== null);

            return [
                'nome'           => (string) ($map['nome'] ?? ''),
                'email'          => (string) ($map['email'] ?? ''),
                'cpf'            => preg_replace('/\D+/', '', (string)($map['cpf'] ?? '')) ?: null,
                'telefone'       => preg_replace('/\D+/', '', (string)($map['telefone'] ?? '')) ?: null,
                'municipio'      => $municipioNome,
                'municipio_id'   => $municipioId,
                'organizacao'     => $orgOut,      // se sua view usa 'organizacao'
                'organizacao_ok'  => $orgOk,
                'escola_unidade'  => $orgOut,      // se sua view usa 'escola_unidade'
                'data_entrada'   => (string) ($map['data_entrada'] ?? ''),
            ];
        })->values();
    }

    private function slugify(string $s): string
    {
        $s = trim(mb_strtolower($s));
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s;
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        return trim($s);
    }

    private function normalizeOrganizacao(?string $raw): ?string
    {
        if (!$raw) return null;
        $key = $this->slugify($raw);
        return $this->organizacoesMap[$key] ?? null;
    }
}
