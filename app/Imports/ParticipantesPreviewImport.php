<?php

namespace App\Imports;

use App\Models\Municipio;
use App\Models\Participante;
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
    protected array $tiposOrganizacao = [];
    /** @var array<string,string> */
    protected array $tiposOrganizacaoMap = [];
    /** @var array<int,string> */
    protected array $tags = [];
    /** @var array<string,string> */
    protected array $tagsMap = [];

    public function __construct()
    {
        $this->rows = collect();

        // Pré-carrega municípios para não consultar a cada linha
        $this->municipiosCache = Municipio::query()
            ->select('id', 'nome')
            ->get()
            ->mapWithKeys(fn($m) => [mb_strtolower(trim($m->nome)) => $m->id])
            ->all();

        $this->tiposOrganizacao = config('engaja.organizacoes', []);
        $this->tiposOrganizacaoMap = collect($this->tiposOrganizacao)
            ->mapWithKeys(fn($o) => [$this->slugify($o) => $o])
            ->all();

        $this->tags = config('engaja.participante_tags', Participante::TAGS);
        $this->tagsMap = collect($this->tags)
            ->mapWithKeys(fn($t) => [$this->slugify($t) => $t])
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
            $raw = is_array($row) ? $row : $row->toArray();
            $map = collect($raw)->map(fn($v) => is_string($v) ? trim($v) : $v);

            // Resolve municipio_id via cache (se existir)
            $municipioNome = (string) ($map['municipio'] ?? '');
            $municipioId = null;
            if ($municipioNome !== '') {
                $key = mb_strtolower($municipioNome);
                $municipioId = $this->municipiosCache[$key] ?? null;
            }

            $tipoColumnExists = false;
            $tipoRaw = $this->firstValue($raw, [
                'tipo_de_organizacao',
                'tipo_organizacao',
                'tipo-da-organizacao',
                'tipo_da_organizacao',
                'tipoorganizacao',
            ], $tipoColumnExists);
            if (!$tipoColumnExists) {
                $tipoRaw = (string) ($map['organizacao'] ?? $map['escola_unidade'] ?? '');
            }
            $tipoCanon = $this->normalizeTipoOrganizacao($tipoRaw);
            $tipoOut   = $tipoCanon ?? $tipoRaw;
            $tipoOk    = ($tipoRaw === '') ? true : ($tipoCanon !== null);

            $organizacaoLivre = $this->firstValue(
                $raw,
                $tipoColumnExists
                    ? ['organizacao', 'organizacao_nome', 'nome_da_organizacao', 'organizacao_livre', 'escola_unidade']
                    : ['escola_unidade', 'organizacao']
            ) ?? '';

            $tagRaw = (string)($map['tag'] ?? '');
            $tagCanon = $this->normalizeTag($tagRaw);
            $tagOut = $tagCanon;
            $tagOk = ($tagRaw === '') ? true : ($tagCanon !== null);

            return [
                'nome'            => (string) ($map['nome'] ?? ''),
                'email'           => (string) ($map['email'] ?? ''),
                'cpf'             => preg_replace('/\D+/', '', (string)($map['cpf'] ?? '')) ?: null,
                'telefone'        => preg_replace('/\D+/', '', (string)($map['telefone'] ?? '')) ?: null,
                'municipio'          => $municipioNome,
                'municipio_id'       => $municipioId,
                'tipo_organizacao'   => $tipoOut,
                'tipo_organizacao_ok'=> $tipoOk,
                'escola_unidade'     => $organizacaoLivre,
                'tag'             => $tagOut,
                'tag_ok'          => $tagOk,
            'data_entrada'    => (string) ($map['data_entrada'] ?? ''),
        ];
    })->values();
    }

    private function firstValue(array $row, array $keys, ?bool &$foundKey = null): ?string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                if ($foundKey !== null) {
                    $foundKey = true;
                }
                $value = $row[$key];
                if ($value === null) {
                    return null;
                }
                if (is_string($value)) {
                    return trim($value);
                }
                if (is_scalar($value)) {
                    return trim((string)$value);
                }
                return null;
            }
        }

        if ($foundKey !== null) {
            $foundKey = false;
        }

        return null;
    }

    private function slugify(string $s): string
    {
        $s = trim(mb_strtolower($s));
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s;
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        return trim($s);
    }

    private function normalizeTipoOrganizacao(?string $raw): ?string
    {
        if (!$raw) return null;
        $key = $this->slugify($raw);
        return $this->tiposOrganizacaoMap[$key] ?? null;
    }

    private function normalizeTag(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }
        $key = $this->slugify($raw);
        return $this->tagsMap[$key] ?? null;
    }
}
