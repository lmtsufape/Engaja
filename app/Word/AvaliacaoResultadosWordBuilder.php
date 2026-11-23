<?php

namespace App\Word;

use App\Models\Atividade;
use App\Models\Avaliacao;
use Illuminate\Support\Carbon;

/**
 * Monta o relatório de respostas de uma avaliação (resultados por Momento) como
 * documento Word editável, a partir do mesmo payload do dashboard de avaliações
 * (totais + perguntas) usado pelo PDF avaliacoes.resultados_atividade_pdf.
 */
class AvaliacaoResultadosWordBuilder
{
    /**
     * @param  array<string, mixed>  $totais
     * @param  array<int, array<string, mixed>>  $perguntas
     */
    public static function build(Atividade $atividade, Avaliacao $avaliacao, array $totais, array $perguntas): WordDocument
    {
        $doc = new WordDocument;
        $doc->addTitle('Avaliação — '.($atividade->descricao ?? ''));

        $municipios = $atividade->municipios->isNotEmpty()
            ? $atividade->municipios->map(fn ($m) => $m->nome_com_estado ?? $m->nome)->join(', ')
            : '—';

        $doc->addFiltersSummary([
            'Ação pedagógica: '.($atividade->evento->nome ?? '—'),
            'Data: '.($atividade->dia ? Carbon::parse($atividade->dia)->format('d/m/Y') : '—'),
            'Município(s): '.$municipios,
            'Modelo de formulário: '.($avaliacao->templateAvaliacao->nome ?? '—'),
        ]);

        $doc->addTable(
            ['Submissões', 'Questões com resposta', 'Respostas (itens)', 'Última resposta'],
            [[
                $totais['submissoes'] ?? 0,
                $totais['questoes'] ?? 0,
                $totais['respostas'] ?? 0,
                $totais['ultima'] ?? '—',
            ]]
        );

        if ($perguntas === []) {
            $doc->addParagraph('Nenhuma resposta agregada para este momento.', ['italic' => true, 'color' => '777777']);

            return $doc;
        }

        self::renderPerguntas($doc, $perguntas);

        return $doc;
    }

    /**
     * Renderiza a lista de perguntas agregadas (agrupadas por dimensão/indicador),
     * cada uma como texto ou tabela de opções. Reutilizado pela consolidação.
     *
     * @param  array<int, array<string, mixed>>  $perguntas
     */
    public static function renderPerguntas(WordDocument $doc, array $perguntas): void
    {
        $dimAtual = null;
        $indAtual = null;
        $num = 0;

        foreach ($perguntas as $p) {
            $dim = $p['dimensao'] ?? 'Sem dimensão';
            $ind = $p['indicador'] ?? 'Sem indicador';

            if ($dim !== $dimAtual) {
                $dimAtual = $dim;
                $indAtual = null;
                $doc->addHeading('Dimensão — '.$dim);
            }

            if ($ind !== $indAtual) {
                $indAtual = $ind;
                $doc->addParagraph('Indicador — '.$ind, ['bold' => true]);
            }

            $num++;
            $resumo = ! empty($p['resumo']) ? ' · '.$p['resumo'] : '';
            $doc->addParagraph($num.'. '.($p['texto'] ?? 'Questão'), ['bold' => true]);
            $doc->addParagraph(($p['total'] ?? 0).' resposta(s)'.$resumo, ['size' => 9, 'color' => '666666']);

            if (($p['tipo'] ?? 'texto') === 'texto') {
                $lista = $p['respostas'] ?? [];

                if ($lista === []) {
                    $doc->addParagraph('Sem respostas de texto.', ['italic' => true, 'color' => '777777']);

                    continue;
                }

                foreach ($lista as $txt) {
                    $doc->addParagraph((string) $txt);
                }

                continue;
            }

            $labels = array_values((array) ($p['labels'] ?? []));
            $values = array_values((array) ($p['values'] ?? []));
            $total = array_sum($values) ?: 1;

            $rows = [];
            foreach ($labels as $idx => $label) {
                $val = (int) ($values[$idx] ?? 0);
                $rawLabel = trim(strip_tags((string) $label));
                $rows[] = [
                    $rawLabel === 'Nao' ? 'Não' : $rawLabel,
                    $val,
                    round($val / $total * 100).'%',
                ];
            }

            $doc->addTable(['Opção', 'Respostas', '%'], $rows);
        }
    }
}
