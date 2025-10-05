<?php

namespace App\Imports;

use App\Models\Municipio;
use App\Models\Participante;
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
    /** @var array<int,string> lista canônica */
    protected array $organizacoes = [];

    /** @var array<string,string> mapa lower => canônico */
    protected array $organizacoesMap = [];

    /** @var array<int,string> */
    protected array $tags = [];
    /** @var array<string,string> */
    protected array $tagsMap = [];

    public function __construct()
    {
        $this->rows = collect();

        $this->municipiosCache = Municipio::with('estado')
            ->get(['id', 'nome', 'estado_id'])
            ->mapWithKeys(function ($m) {
                return [mb_strtolower(trim($m->nome)) => (int) $m->id];
            })
            ->toArray();
        $this->organizacoes = config('engaja.organizacoes', []);
        $this->organizacoesMap = collect($this->organizacoes)
            ->mapWithKeys(function ($o) {
                return [mb_strtolower($this->slugify($o)) => $o];
            })->toArray();

        $this->tags = config('engaja.participante_tags', Participante::TAGS);
        $this->tagsMap = collect($this->tags)
            ->mapWithKeys(fn($tag) => [mb_strtolower($this->slugify($tag)) => $tag])
            ->toArray();
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
        if (!$raw) return null;
        $key = $this->slugify($raw);
        return $this->tagsMap[$key] ?? null;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $r) {
            // Normaliza campos base
            $nome     = trim((string)($r['nome'] ?? ''));
            $email    = strtolower(trim((string)($r['email'] ?? '')));
            $cpf      = preg_replace('/\D+/', '', (string)($r['cpf'] ?? '')) ?: null;
            $telefone = preg_replace('/\D+/', '', (string)($r['telefone'] ?? '')) ?: null;

            $munNome  = trim((string)($r['municipio'] ?? ''));
            $escola   = trim((string)($r['organizacao'] ?? $r['escola_unidade'] ?? '')); // aceita ambos
            $tagRaw   = trim((string)($r['tag'] ?? ''));
            $status   = $this->mapStatus((string)($r['status'] ?? ''));
            $justif   = trim((string)($r['justificativa'] ?? '')) ?: null;
            $entrada  = $this->parseDate($r['data_entrada'] ?? null);

            $linhaVazia = (
                $nome === '' &&
                $email === '' &&
                $cpf === null &&
                $telefone === null &&
                $munNome === '' &&
                $escola === '' &&
                $tagRaw === '' &&
                $status === null &&
                $justif === null &&
                $entrada === null
            );
            if ($linhaVazia) {
                continue;
            }

            // Resolve municipio_id (se nome existir)
            $municipioId = null;
            if ($munNome !== '') {
                $key = mb_strtolower($munNome);
                $municipioId = $this->municipiosCache[$key] ?? null;
            }

            $orgCanon = $this->normalizeOrganizacao($escola);
            $organizacaoOk = $escola === '' ? true : ($orgCanon !== null);
            $escolaOut = $orgCanon ?? $escola;

            $tagCanon = $this->normalizeTag($tagRaw);
            $tagOk = $tagRaw === '' ? true : ($tagCanon !== null);
            $tagOut = $tagCanon ?? $tagRaw;

            $this->rows->push([
                'nome'           => $nome,
                'email'          => $email,
                'cpf'            => $cpf,
                'telefone'       => $telefone,
                'municipio'      => $munNome,      // para exibir no input
                'municipio_id'   => $municipioId,  // para pré-selecionar no <select>
                'organizacao'    => $escolaOut,    // canônico se reconhecido
                'organizacao_ok' => $organizacaoOk,
                'tag'            => $tagOut,
                'tag_ok'         => $tagOk,
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
            'presente', 'p', 'present'      => 'presente',
            'ausente', 'a', 'absent'        => 'ausente',
            'justificado', 'j', 'justify'   => 'justificado',
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
