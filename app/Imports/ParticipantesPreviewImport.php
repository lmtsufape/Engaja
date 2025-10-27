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
    protected array $organizacoes = [];
    /** @var array<string,string> */
    protected array $organizacoesMap = [];
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

        $this->organizacoes = config('engaja.organizacoes', []);
        $this->organizacoesMap = collect($this->organizacoes)
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
            $orgOut   = $orgCanon;
            $orgOk    = ($orgRaw === '') ? true : ($orgCanon !== null);

            $tagRaw = (string)($map['tag'] ?? '');
            $tagCanon = $this->normalizeTag($tagRaw);
            $tagOut = $tagCanon;
            $tagOk = ($tagRaw === '') ? true : ($tagCanon !== null);

            return [
                'nome'            => (string) ($map['nome'] ?? ''),
                'email'           => (string) ($map['email'] ?? ''),
                'cpf'             => preg_replace('/\D+/', '', (string)($map['cpf'] ?? '')) ?: null,
                'telefone'        => preg_replace('/\D+/', '', (string)($map['telefone'] ?? '')) ?: null,
                'municipio'       => $municipioNome,
                'municipio_id'    => $municipioId,
                'organizacao'     => $orgOut,
                'organizacao_ok'  => $orgOk,
                'escola_unidade'  => $orgOut,
                'tag'             => $tagOut,
                'tag_ok'          => $tagOk,
                'data_entrada'    => (string) ($map['data_entrada'] ?? ''),
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

    private function normalizeTag(?string $raw): ?string
    {
        if (!$raw) {
            return null;
        }
        $key = $this->slugify($raw);
        return $this->tagsMap[$key] ?? null;
    }
}
