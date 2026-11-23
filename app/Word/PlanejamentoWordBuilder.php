<?php

namespace App\Word;

use App\Models\Evento;
use Illuminate\Support\Carbon;

/**
 * Monta o documento de Planejamento de uma Ação Pedagógica como Word editável,
 * espelhando o PDF eventos.planejamento_pdf.
 */
class PlanejamentoWordBuilder
{
    /**
     * @param  array<string, string>  $acoesGerais
     * @param  array<int, string>  $checklistItems
     */
    public static function build(Evento $evento, $matrizesOrdenadas, array $acoesGerais, array $checklistItems): WordDocument
    {
        $doc = new WordDocument;
        $doc->addTitle('Planejamento de Ação Pedagógica');

        $doc->addHeading('Dados da Ação');
        self::campo($doc, 'Nome da ação pedagógica', $evento->nome);

        if ($evento->acao_geral) {
            self::campo($doc, 'Ação Geral', $acoesGerais[$evento->acao_geral] ?? $evento->acao_geral);
            self::campo($doc, 'Sub-Ação', $evento->subacao ?? '—');
        }

        self::campo($doc, 'Tipo', $evento->tipo);
        self::campo($doc, 'Modalidade', $evento->modalidade);
        self::campo($doc, 'Data de início', $evento->data_inicio ? Carbon::parse($evento->data_inicio)->format('d/m/Y') : null);
        self::campo($doc, 'Data de término', $evento->data_fim ? Carbon::parse($evento->data_fim)->format('d/m/Y') : null);
        self::campo($doc, 'Local', $evento->local);

        if ($evento->objetivos_gerais || $evento->objetivos_especificos) {
            $doc->addHeading('Objetivos');
            self::campo($doc, 'Objetivos Gerais', $evento->objetivos_gerais);
            self::campo($doc, 'Objetivos Específicos', $evento->objetivos_especificos);
        }

        if ($evento->situacoesDesafiadoras->isNotEmpty()) {
            $doc->addHeading('Situações Desafiadoras da EJA a serem enfrentadas');
            foreach ($evento->situacoesDesafiadoras as $sit) {
                $doc->addParagraph('• '.$sit->nome);
            }
        }

        $matrizes = $matrizesOrdenadas ?? $evento->matrizes;
        if ($matrizes->isNotEmpty()) {
            $doc->addHeading('Matriz de Aprendizagens');
            foreach ($matrizes as $matriz) {
                $doc->addParagraph('• '.$matriz->nome);
            }
        }

        if (! empty($evento->ods_selecionados)) {
            $doc->addHeading('Interfaces com os Objetivos de Desenvolvimento Sustentável (ODS)');
            foreach ($evento->ods_selecionados as $aspecto) {
                $doc->addParagraph('• '.$aspecto);
            }
        }

        if ($evento->sequenciasDidaticas->isNotEmpty()) {
            $doc->addHeading('Sequência Didática das Atividades');
            $rows = [];
            foreach ($evento->sequenciasDidaticas as $i => $seq) {
                $rows[] = [$seq->periodo ?: 'Momento '.($i + 1), $seq->descricao];
            }
            $doc->addTable(['Período', 'Descrição'], $rows);
        }

        if ($evento->recursos_materiais_necessarios || $evento->providencias_sme_parceria || $evento->observacoes_complementares) {
            $doc->addHeading('Informações Complementares');
            self::campo($doc, 'Recursos Materiais Necessários', $evento->recursos_materiais_necessarios);
            self::campo($doc, 'Providências junto à SME / Parceria', $evento->providencias_sme_parceria);
            self::campo($doc, 'Observações Complementares', $evento->observacoes_complementares);
        }

        $doc->addHeading('Checklist do Planejamento');
        $checkedInts = array_map('intval', $evento->checklist_planejamento ?? []);
        foreach ($checklistItems as $idx => $label) {
            $marca = in_array($idx, $checkedInts, true) ? '☑' : '☐';
            $doc->addParagraph($marca.'  '.$label);
        }

        return $doc;
    }

    private static function campo(WordDocument $doc, string $rotulo, ?string $valor): void
    {
        if ($valor === null || trim($valor) === '') {
            return;
        }

        $doc->getSection()->addText($rotulo, ['bold' => true, 'size' => 9, 'color' => '6B7280'], ['spaceAfter' => 0]);
        $doc->addParagraph($valor);
    }
}
