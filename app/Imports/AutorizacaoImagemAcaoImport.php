<?php

namespace App\Imports;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AutorizacaoImagemAcaoImport
{
    public const MAX_ROWS = 5000;

    public function read(string $path): array
    {
        $book = IOFactory::load($path);
        $rows = [];
        $aliases = [
            'nome' => ['nome', 'name', 'nome_completo', 'participante'],
            'cpf' => ['cpf', 'documento'],
        ];
        $lookup = [];
        foreach ($aliases as $field => $names) {
            foreach ($names as $name) {
                $lookup[Str::slug($name, '_')] = $field;
            }
        }

        try {
            foreach ($book->getWorksheetIterator() as $sheet) {
                if ($sheet->getSheetState() !== Worksheet::SHEETSTATE_VISIBLE
                    || strtolower(trim($sheet->getTitle())) === '_valid') {
                    continue;
                }

                $headers = [];
                foreach ($sheet->getRowIterator() as $source) {
                    $number = $source->getRowIndex();
                    $cells = $source->getCellIterator();
                    $cells->setIterateOnlyExistingCells(true);
                    $values = [];
                    foreach ($cells as $cell) {
                        $values[$cell->getColumn()] = $this->cellValue($cell);
                    }

                    if ($headers === []) {
                        if ($number > 30) {
                            break;
                        }
                        $candidate = [];
                        foreach ($values as $column => $value) {
                            $key = Str::slug((string) $value, '_');
                            if (isset($lookup[$key])) {
                                $candidate[$column] = $lookup[$key];
                            }
                        }
                        if (in_array('cpf', $candidate, true)) {
                            if (count($candidate) !== count(array_unique($candidate))) {
                                throw ValidationException::withMessages([
                                    'your_file' => 'Há colunas repetidas na aba '.$sheet->getTitle().'.',
                                ]);
                            }
                            $headers = $candidate;
                        }

                        continue;
                    }

                    $row = ['nome' => null, 'cpf' => null];
                    foreach ($headers as $column => $field) {
                        $row[$field] = $values[$column] ?? null;
                    }

                    if (! collect($row)->contains(fn ($value) => $value !== null && trim((string) $value) !== '')) {
                        continue;
                    }

                    $row['linha_original'] = $number;
                    $row['aba_original'] = $sheet->getTitle();
                    $rows[] = $row;

                    if (count($rows) > self::MAX_ROWS) {
                        throw ValidationException::withMessages([
                            'your_file' => 'Utilize no máximo 5.000 registros por arquivo.',
                        ]);
                    }
                }
            }
        } finally {
            $book->disconnectWorksheets();
        }

        if ($rows === []) {
            throw ValidationException::withMessages([
                'your_file' => 'Nenhum registro encontrado. Informe os cabeçalhos nome e cpf nas primeiras 30 linhas.',
            ]);
        }

        return $rows;
    }

    private function cellValue(Cell $cell): ?string
    {
        $raw = $cell->getValue();
        if ($raw === null) {
            return null;
        }
        if ($raw instanceof RichText) {
            return $raw->getPlainText();
        }
        if ($cell->getDataType() === DataType::TYPE_NUMERIC) {
            $formatted = trim((string) $cell->getFormattedValue());
            if ($formatted !== '' && ! preg_match('/e[+-]?\d+/i', $formatted)) {
                return $formatted;
            }

            return sprintf('%.0f', (float) $raw);
        }

        return is_scalar($raw) ? (string) $raw : null;
    }
}
