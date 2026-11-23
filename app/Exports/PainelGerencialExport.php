<?php

namespace App\Exports;

use App\Services\PainelGerencialService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Exporta o painel gerencial em XLSX com uma aba por bloco de métrica.
 */
class PainelGerencialExport implements WithMultipleSheets
{
    public function __construct(private Request $request) {}

    /**
     * @return array<int, FromArray>
     */
    public function sheets(): array
    {
        $payload = app(PainelGerencialService::class)->buildPayload($this->request);
        $k = $payload['kpis'];

        return [
            new PainelGerencialSheet('Resumo', ['Indicador', 'Valor'], [
                ['Municípios ativos', $k['municipios_ativos']],
                ['Participantes totais', $k['participantes_totais']],
                ['Participantes únicos', $k['participantes_unicos']],
                ['Eventos realizados', $k['eventos_realizados']],
                ['Horas presenciais', $k['horas_presenciais']],
                ['Horas EaD', $k['horas_ead']],
                ['Horas híbridas', $k['horas_hibrido']],
                ['Certificados emitidos', $k['certificados_emitidos']],
                ['Avaliações respondidas', $k['avaliacoes_respondidas']],
                ['Pendências de documentação', $k['pendencias_documentacao']],
            ]),

            new PainelGerencialSheet(
                'Metas por Ação',
                ['Ação', 'Previstas', 'Inscritos', 'Presentes', 'Avaliações', '% Realizado'],
                array_map(fn ($r) => [
                    $r['acao'], $r['previstas'], $r['inscritos'], $r['presentes'], $r['avaliacoes'], $r['pct_realizado'],
                ], $payload['metas_por_acao'])
            ),

            new PainelGerencialSheet(
                'Por Região',
                ['Região', 'Previstas', 'Presentes', '% Realizado'],
                array_map(fn ($r) => [$r['regiao'], $r['previstas'], $r['presentes'], $r['pct_realizado']], $payload['participacao_por_regiao'])
            ),

            new PainelGerencialSheet(
                'Segmentos',
                ['Segmento', 'Presentes', 'Participantes únicos'],
                array_map(fn ($r) => [$r['segmento'], $r['presentes'], $r['participantes_unicos']], $payload['segmentos'])
            ),

            new PainelGerencialSheet(
                'Evolução Semestral',
                ['Semestre', 'Eventos', 'Presentes', 'Avaliações'],
                array_map(fn ($r) => [$r['semestre'], $r['eventos'], $r['presentes'], $r['avaliacoes']], $payload['evolucao_semestral'])
            ),

            new PainelGerencialSheet(
                'Baixo Engajamento',
                ['Município', 'Região', 'Previstas', 'Presentes', '% Realizado'],
                array_map(fn ($r) => [$r['municipio'], $r['regiao'], $r['previstas'], $r['presentes'], $r['pct_realizado']], $payload['municipios_baixo_engajamento'])
            ),

            new PainelGerencialSheet(
                'Sem Avaliação',
                ['Ação', 'Momento', 'Município', 'Data'],
                array_map(fn ($r) => [$r['acao'], $r['momento'], $r['municipio'], $r['dia']], $payload['eventos_sem_avaliacao'])
            ),

            new PainelGerencialSheet(
                'Recorrência Ausência',
                ['Participante', 'Município', 'Ausências'],
                array_map(fn ($r) => [$r['participante'], $r['municipio'], $r['ausencias']], $payload['recorrencia_ausencia'])
            ),
        ];
    }
}
