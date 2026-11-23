<?php

namespace App\Word;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Adaptador que renderiza uma classe Export de Excel (maatwebsite/excel) como
 * tabela(s) num WordDocument, sem duplicar as queries.
 *
 * - Exports de aba única (FromCollection/FromArray + WithHeadings [+ WithMapping])
 *   viram uma tabela.
 * - Exports WithMultipleSheets viram uma seção (título + tabela) por aba.
 *
 * Abas baseadas em FromView (ex.: matriz de presença) não são suportadas aqui —
 * essas devem ser montadas por um builder dedicado a partir dos dados de origem.
 */
class WordTableExport
{
    public static function render(WordDocument $doc, object $export): void
    {
        if ($export instanceof WithMultipleSheets) {
            foreach ($export->sheets() as $sheet) {
                if ($sheet instanceof WithTitle) {
                    $doc->addHeading($sheet->title());
                }

                self::renderSingle($doc, $sheet);
            }

            return;
        }

        self::renderSingle($doc, $export);
    }

    private static function renderSingle(WordDocument $doc, object $export): void
    {
        $headings = $export instanceof WithHeadings
            ? self::flattenHeadings($export->headings())
            : [];

        $rows = [];

        if ($export instanceof FromCollection) {
            foreach ($export->collection() as $item) {
                $rows[] = $export instanceof WithMapping
                    ? $export->map($item)
                    : (array) $item;
            }
        } elseif ($export instanceof FromArray) {
            foreach ($export->array() as $item) {
                $rows[] = $export instanceof WithMapping
                    ? $export->map($item)
                    : (array) $item;
            }
        }

        $doc->addTable($headings, $rows);
    }

    /**
     * WithHeadings pode retornar uma linha de cabeçalho (array de strings) ou
     * várias (array de arrays). Nesse caso usamos a última — os rótulos de coluna.
     *
     * @param  array<int, mixed>  $headings
     * @return array<int, mixed>
     */
    private static function flattenHeadings(array $headings): array
    {
        if ($headings !== [] && is_array($headings[array_key_first($headings)])) {
            return array_values((array) end($headings));
        }

        return $headings;
    }
}
