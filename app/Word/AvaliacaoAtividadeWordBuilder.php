<?php

namespace App\Word;

use App\Models\Atividade;
use App\Models\AvaliacaoAtividade;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Monta os relatórios qualitativos do Momento (avaliação de atividade) como
 * documento Word editável — versão individual e consolidada.
 */
class AvaliacaoAtividadeWordBuilder
{
    /**
     * Relatório individual de um educador.
     *
     * @param  array<string, string>  $camposPerguntas
     * @param  array<string, int>  $resumoPublico
     */
    public static function single(AvaliacaoAtividade $relatorio, array $resumoPublico, array $camposPerguntas): WordDocument
    {
        $atividade = $relatorio->atividade;

        $doc = new WordDocument;
        $doc->addTitle('Relatório do Momento');

        self::addMetadados($doc, [
            'Educador(a)' => $relatorio->user->name ?? $relatorio->nome_educador ?? '—',
            'Ação pedagógica' => $atividade?->evento?->nome ?? '—',
            'Momento' => $atividade?->descricao ?? '—',
            'Município(s)' => self::municipios($atividade),
            'Data' => $atividade?->dia ? Carbon::parse($atividade->dia)->format('d/m/Y') : '—',
        ]);

        self::addResumoPublico($doc, $resumoPublico);

        foreach ($camposPerguntas as $campo => $pergunta) {
            $doc->addHeading($pergunta);
            $doc->addParagraph(trim((string) ($relatorio->{$campo} ?? '')) ?: '—');
        }

        return $doc;
    }

    /**
     * Relatório consolidado de todos os educadores de um momento.
     *
     * @param  array<string, int>  $resumoPublico
     * @param  Collection<int, array{pergunta: string, respostas: Collection}>  $respostasPorPergunta
     */
    public static function consolidado(
        Atividade $atividade,
        Collection $relatorios,
        array $resumoPublico,
        Collection $respostasPorPergunta
    ): WordDocument {
        $doc = new WordDocument;
        $doc->addTitle('Relatório Consolidado do Momento');

        self::addMetadados($doc, [
            'Ação pedagógica' => $atividade->evento?->nome ?? '—',
            'Momento' => $atividade->descricao ?? '—',
            'Município(s)' => self::municipios($atividade),
            'Data' => $atividade->dia ? Carbon::parse($atividade->dia)->format('d/m/Y') : '—',
            'Relatórios recebidos' => (string) $relatorios->count(),
        ]);

        self::addResumoPublico($doc, $resumoPublico);

        foreach ($respostasPorPergunta as $bloco) {
            $doc->addHeading($bloco['pergunta']);

            if ($bloco['respostas']->isEmpty()) {
                $doc->addParagraph('Sem respostas.', ['italic' => true, 'color' => '777777']);

                continue;
            }

            foreach ($bloco['respostas'] as $resposta) {
                $doc->addParagraph($resposta['responsavel_nome'].':', ['bold' => true]);
                $doc->addParagraph(trim((string) $resposta['resposta']) ?: '—');
            }
        }

        return $doc;
    }

    /**
     * @param  array<string, string>  $linhas
     */
    private static function addMetadados(WordDocument $doc, array $linhas): void
    {
        foreach ($linhas as $rotulo => $valor) {
            $doc->getSection()->addText(
                $rotulo.': ',
                ['bold' => true, 'size' => 10],
                ['spaceAfter' => 20]
            );
            $doc->getSection()->addText((string) $valor, ['size' => 10], ['spaceAfter' => 40]);
        }

        $doc->addTextBreak(1);
    }

    /**
     * @param  array<string, int>  $resumo
     */
    private static function addResumoPublico(WordDocument $doc, array $resumo): void
    {
        $doc->addHeading('Público');
        $doc->addTable(
            ['Previstos', 'Inscritos', 'Presentes', 'Mov. sociais', 'Prefeitura', 'Sem vínculo'],
            [[
                $resumo['prevista'] ?? 0,
                $resumo['inscritos'] ?? 0,
                $resumo['presentes'] ?? 0,
                $resumo['movimentos'] ?? 0,
                $resumo['prefeitura'] ?? 0,
                $resumo['sem_vinculo'] ?? 0,
            ]]
        );
    }

    private static function municipios(?Atividade $atividade): string
    {
        if (! $atividade) {
            return '—';
        }

        $nomes = $atividade->municipios?->pluck('nome')->filter()->values();

        return $nomes && $nomes->isNotEmpty() ? $nomes->join(', ') : '—';
    }
}
