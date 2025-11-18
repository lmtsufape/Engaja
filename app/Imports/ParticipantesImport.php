<?php

namespace App\Imports;

use App\Models\Participante;
use App\Models\User;
use App\Models\Municipio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class ParticipantesImport implements ToModel, WithHeadingRow, SkipsEmptyRows
{
    public Collection $importados;

    /** @var array<string,int> */
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
        $this->importados = new Collection();

        // Pré-carrega os municípios em memória para evitar query em cada linha
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

    public function headingRow(): int
    {
        return 1;
    }

    public function model(array $row)
    {
        // Resolve municipio_id usando cache
        $municipioId = null;
        if (!empty($row['municipio'])) {
            $key = mb_strtolower(trim((string) $row['municipio']));
            if (isset($this->municipiosCache[$key])) {
                $municipioId = $this->municipiosCache[$key];
            }
        }

        // Cria ou reaproveita usuário pelo email
        $email = strtolower(trim((string)($row['email'] ?? '')));
        $name  = trim((string)($row['nome'] ?? ''));

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name !== '' ? $name : ($row['cpf'] ?? 'Participante'),
                'password' => Hash::make(Str::random(12)),
            ]
        );

        // Normaliza data de entrada
        $dataEntrada = null;
        if (!empty($row['data_entrada'])) {
            try {
                $dataEntrada = Carbon::parse($row['data_entrada'])->format('Y-m-d');
            } catch (\Throwable $e) {
                $dataEntrada = null;
            }
        }

        $tipoColumnExists = false;
        $tipoRaw = $this->firstValue($row, [
            'tipo_de_organizacao',
            'tipo_organizacao',
            'tipo-da-organizacao',
            'tipo_da_organizacao',
            'tipoorganizacao',
        ], $tipoColumnExists);
        if (!$tipoColumnExists) {
            $tipoRaw = trim((string)($row['organizacao'] ?? $row['escola_unidade'] ?? ''));
        }
        $tipoCanon = $this->normalizeTipoOrganizacao($tipoRaw);
        $tipoOut = $tipoCanon ?? ($tipoRaw !== '' ? $tipoRaw : null);

        $organizacaoLivre = $this->firstValue(
            $row,
            $tipoColumnExists
                ? ['organizacao', 'organizacao_nome', 'nome_da_organizacao', 'organizacao_livre', 'escola_unidade']
                : ['escola_unidade', 'organizacao']
        );
        $organizacaoOut = ($organizacaoLivre !== null && $organizacaoLivre !== '')
            ? $organizacaoLivre
            : (isset($row['escola_unidade']) ? trim((string)$row['escola_unidade']) : null);

        $tagCanon = $this->normalizeTag($row['tag'] ?? null);

        // Cria ou atualiza participante
        $participante = Participante::updateOrCreate(
            [
                'user_id' => $user->id,
            ],
            [
                'municipio_id'     => $municipioId,
                'cpf'              => $row['cpf'] ?? null,
                'telefone'         => $row['telefone'] ?? null,
                'escola_unidade'   => $organizacaoOut ?: null,
                'tipo_organizacao' => $tipoOut,
                'tag'              => $tagCanon,
                'data_entrada'     => $dataEntrada,
            ]
        );

        $this->importados->push($participante);

        return $participante;
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
        $s = iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s; // remove acentos
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        return trim($s);
    }

    private function normalizeTipoOrganizacao(?string $raw): ?string
    {
        if (!$raw) return null;
        $key = $this->slugify($raw);
        return $this->tiposOrganizacaoMap[$key] ?? null;
    }

    private function normalizeTag($raw): ?string
    {
        if (!$raw) {
            return null;
        }

        if (is_string($raw)) {
            $key = $this->slugify($raw);
            return $this->tagsMap[$key] ?? null;
        }

        return null;
    }
}
