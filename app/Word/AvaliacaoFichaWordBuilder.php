<?php

namespace App\Word;

use App\Models\Avaliacao;

/**
 * Monta a ficha de avaliação em branco (para preenchimento) como documento Word
 * editável, espelhando o PDF avaliacoes.ficha_pdf — agrupada por dimensão →
 * indicador, com o campo apropriado a cada tipo de questão.
 */
class AvaliacaoFichaWordBuilder
{
    public static function build(Avaliacao $avaliacao): WordDocument
    {
        $templateNome = $avaliacao->templateAvaliacao->nome ?? 'Avaliação';
        $descricao = $avaliacao->descricao_universal ?? $avaliacao->atividade?->descricao ?? '';
        $titulo = $templateNome.($descricao ? ' — '.$descricao : '');

        $doc = new WordDocument;
        $doc->addTitle($titulo);

        if ($avaliacao->anonima) {
            $doc->addParagraph('Avaliação anônima — não é necessário se identificar.', ['italic' => true, 'color' => '421944']);
        } else {
            self::addIdentificacao($doc);
        }

        $grupos = $avaliacao->avaliacaoQuestoes
            ->sortBy(function ($q) {
                $dim = mb_strtolower($q->indicador->dimensao->descricao ?? '');
                $ind = mb_strtolower($q->indicador->descricao ?? '');
                $ordem = $q->ordem ?? 999;

                return sprintf('%s|%s|%03d|%06d', $dim, $ind, $ordem, $q->id);
            })
            ->groupBy(fn ($q) => $q->indicador->dimensao->descricao ?? 'Sem dimensão')
            ->map(fn ($col) => $col->groupBy(fn ($q) => $q->indicador->descricao ?? 'Sem indicador'));

        $num = 0;

        foreach ($grupos as $dimNome => $indicadores) {
            $doc->addHeading('Dimensão — '.$dimNome);

            foreach ($indicadores as $indNome => $questoes) {
                $doc->addParagraph('Indicador — '.$indNome, ['bold' => true, 'color' => '008BBC']);

                foreach ($questoes as $questao) {
                    $num++;
                    self::addQuestao($doc, $num, $questao);
                }
            }
        }

        return $doc;
    }

    private static function addIdentificacao(WordDocument $doc): void
    {
        $doc->addHeading('Identificação do respondente');
        $doc->addParagraph('Nome completo: ________________________________________________');
        $doc->addParagraph('E-mail: ______________________________________________________');
        $doc->addParagraph('CPF: _____._____._____-___');
        $doc->addTextBreak(1);
    }

    private static function addQuestao(WordDocument $doc, int $num, $questao): void
    {
        $doc->addParagraph($num.'. '.$questao->texto, ['bold' => true]);

        switch ($questao->tipo) {
            case 'texto':
                for ($i = 0; $i < 5; $i++) {
                    $doc->addParagraph('__________________________________________________________________');
                }
                break;

            case 'escala':
                foreach (array_reverse($questao->escala?->valores ?? []) as $valor) {
                    $doc->addParagraph('(   )  '.$valor);
                }
                break;

            case 'numero':
                $doc->addParagraph('R: ____________');
                break;

            case 'boolean':
                $doc->addParagraph('(   )  Sim      (   )  Não');
                break;

            case 'unica':
                foreach ($questao->opcoes_resposta ?? [] as $opcao) {
                    $doc->addParagraph('(   )  '.$opcao);
                }
                break;

            case 'multipla':
                foreach ($questao->opcoes_resposta ?? [] as $opcao) {
                    $doc->addParagraph('[   ]  '.$opcao);
                }
                break;
        }

        $doc->addTextBreak(1);
    }
}
