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

    public function __construct()
    {
        $this->rows = collect();

        // Pré-carrega municípios para não consultar a cada linha
        $this->municipiosCache = Municipio::query()
            ->select('id', 'nome')
            ->get()
            ->mapWithKeys(fn($m) => [mb_strtolower(trim($m->nome)) => $m->id])
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
            // Normaliza strings
            $map = collect($row)->map(fn($v) => is_string($v) ? trim($v) : $v);

            // Resolve municipio_id via cache (se existir)
            $municipioNome = (string) ($map['municipio'] ?? '');
            $municipioId = null;
            if ($municipioNome !== '') {
                $key = mb_strtolower($municipioNome);
                $municipioId = $this->municipiosCache[$key] ?? null;
            }

            // Retorna array "editável" para a view
            return [
                'nome'           => (string) ($map['nome'] ?? ''),
                'email'          => (string) ($map['email'] ?? ''),
                'cpf'            => preg_replace('/\D+/', '', (string)($map['cpf'] ?? '')) ?: null,
                'telefone'       => preg_replace('/\D+/', '', (string)($map['telefone'] ?? '')) ?: null,
                'municipio'      => $municipioNome,
                'municipio_id'   => $municipioId,              // ajuda a montar <select> se quiser
                'escola_unidade' => (string) ($map['escola_unidade'] ?? ''),
                'data_entrada'   => (string) ($map['data_entrada'] ?? ''), // fica string p/ usuário ajustar
            ];
        })->values();
    }
}
