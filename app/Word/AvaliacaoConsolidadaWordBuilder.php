<?php

namespace App\Word;

use App\Models\Evento;

/**
 * Monta a consolidação de avaliações de uma Ação Pedagógica como documento Word
 * editável, espelhando o PDF avaliacoes.consolidadas_pdf: grupos → modelos →
 * perguntas agregadas.
 */
class AvaliacaoConsolidadaWordBuilder
{
    /**
     * @param  array<int, array<string, mixed>>  $grupos
     */
    public static function build(Evento $evento, string $agrupamento, array $grupos): WordDocument
    {
        $contexto = match ($agrupamento) {
            'regiao' => 'Respostas consolidadas e agrupadas por região.',
            'municipio' => 'Respostas consolidadas e agrupadas por município.',
            default => 'Respostas consolidadas de todos os municípios.',
        };

        $doc = new WordDocument;
        $doc->addTitle('Consolidação de Avaliações — '.($evento->nome ?? ''));
        $doc->addParagraph($contexto, ['italic' => true, 'color' => '666666']);

        if ($grupos === []) {
            $doc->addParagraph('Nenhum dado de avaliação encontrado para esta ação pedagógica.', ['italic' => true, 'color' => '777777']);

            return $doc;
        }

        foreach ($grupos as $grupo) {
            $doc->addHeading($grupo['nome'].' ('.count($grupo['templates']).' modelo(s))');

            foreach ($grupo['templates'] as $tpl) {
                $doc->addParagraph($tpl['template_nome'], ['bold' => true, 'size' => 12]);
                $doc->addTable(
                    ['Submissões', 'Respostas', 'Média geral'],
                    [[
                        $tpl['submissoes'] ?? 0,
                        $tpl['respostas'] ?? 0,
                        $tpl['media_geral'] !== null ? number_format($tpl['media_geral'], 2, ',', '.') : '—',
                    ]]
                );

                AvaliacaoResultadosWordBuilder::renderPerguntas($doc, $tpl['perguntas'] ?? []);
            }
        }

        return $doc;
    }
}
