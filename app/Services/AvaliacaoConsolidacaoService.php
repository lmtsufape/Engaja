<?php

namespace App\Services;

use App\Models\Evento;
use App\Models\RespostaAvaliacao;
use Illuminate\Support\Collection;

class AvaliacaoConsolidacaoService
{
    public function __construct(private AvaliacaoRespostasDashboardService $dashboard) {}

    public function build(Evento $evento, string $agrupamento = 'geral'): array
    {
        $agrupamento = in_array($agrupamento, ['regiao', 'municipio'], true) ? $agrupamento : 'geral';

        $respostas = RespostaAvaliacao::query()
            ->select([
                'id',
                'avaliacao_id',
                'avaliacao_questao_id',
                'submissao_avaliacao_id',
                'resposta',
                'created_at',
            ])
            ->with([
                'avaliacaoQuestao.escala',
                'avaliacaoQuestao.indicador.dimensao',
                'avaliacao.templateAvaliacao',
                'submissaoAvaliacao.presenca.inscricao.participante.municipio.estado.regiao',
                'avaliacao.atividade.municipio.estado.regiao',
                'avaliacao.atividade.municipios.estado.regiao',
            ])
            ->whereHas('avaliacao.atividade', fn ($q) => $q->where('evento_id', $evento->id))
            ->get();

        $templateNames = [];
        $grouped = [];

        foreach ($respostas as $resposta) {
            $avaliacao = $resposta->avaliacao;
            if (! $avaliacao) {
                continue;
            }

            $template = $avaliacao->templateAvaliacao;
            $templateId = $template?->id ?? 0;
            $templateNames[$templateId] = $template?->nome ?? 'Modelo sem nome';

            $groupKeys = $this->resolveGroupKeys($resposta, $agrupamento);
            foreach ($groupKeys as $groupKey) {
                $grouped[$groupKey][$templateId][] = $resposta;
            }
        }

        $groups = collect($grouped)->map(function (array $templates, string $groupName) use ($templateNames) {
            $templatesPayload = collect($templates)->map(function (array $items, int $templateId) use ($templateNames) {
                $collection = $this->normalizarPerguntasRepetidas(collect($items));
                $perguntas = $this->dashboard->montarPerguntasFromRespostas($collection)
                    ->map(fn (array $pergunta) => $this->enrichPerguntaResumo($pergunta))
                    ->values();

                return [
                    'template_id' => $templateId ?: null,
                    'template_nome' => $templateNames[$templateId] ?? 'Modelo sem nome',
                    'submissoes' => $collection->pluck('submissao_avaliacao_id')->filter()->unique()->count(),
                    'respostas' => $collection->count(),
                    'media_geral' => $this->calcularMediaGeral($perguntas),
                    'perguntas_com_media' => $perguntas->whereNotNull('media')->count(),
                    'respostas_com_media' => $perguntas->whereNotNull('media')->sum('total'),
                    'perguntas' => $perguntas->all(),
                ];
            })->sortBy('template_nome')->values()->all();

            return [
                'nome' => $groupName,
                'templates' => $templatesPayload,
            ];
        })->values();

        return $this->sortGroups($groups, $agrupamento)->all();
    }

    private function resolveGroupKeys(RespostaAvaliacao $resposta, string $agrupamento): array
    {
        if ($agrupamento === 'geral') {
            return ['Todos os municípios'];
        }

        // 1. Prioridade: Município do usuário (participante) que respondeu
        $municipio = $resposta->submissaoAvaliacao?->presenca?->inscricao?->participante?->municipio;

        // 2. Fallback: Se não tem participante atrelado, pega o município da atividade
        // (Apenas se a atividade tiver EXATAMENTE UM município para evitar duplicação de respostas)
        if (! $municipio) {
            $atividade = $resposta->avaliacao?->atividade;
            if ($atividade) {
                if ($atividade->municipios->count() === 1) {
                    $municipio = $atividade->municipios->first();
                } elseif ($atividade->municipios->isEmpty() && $atividade->municipio) {
                    $municipio = $atividade->municipio;
                }
            }
        }

        if ($agrupamento === 'regiao') {
            $nomeRegiao = $municipio?->estado?->regiao?->nome;
            return [$nomeRegiao ?: 'Sem região'];
        }

        // Agrupamento por município
        $nomeMunicipio = $municipio?->nome;
        return [$nomeMunicipio ?: 'Município não cadastrado'];
    }

    private function normalizarPerguntasRepetidas(Collection $respostas): Collection
    {
        $canonicalIds = [];

        return $respostas->map(function (RespostaAvaliacao $resposta) use (&$canonicalIds, $respostas) {
            $questao = $resposta->avaliacaoQuestao;
            if (! $questao) {
                return $resposta;
            }

            $signature = $this->questionSignature($questao);
            $canonicalIds[$signature] ??= $questao->id;

            if ($canonicalIds[$signature] === $questao->id) {
                return $resposta;
            }

            $clone = clone $resposta;
            $clone->avaliacao_questao_id = $canonicalIds[$signature];
            $clone->setRelation('avaliacaoQuestao', $respostas
                ->first(fn (RespostaAvaliacao $item) => $item->avaliacaoQuestao?->id === $canonicalIds[$signature])
                ?->avaliacaoQuestao ?? $questao);

            return $clone;
        });
    }

    private function questionSignature($questao): string
    {
        $opcoes = collect($questao->opcoes_resposta ?? [])
            ->map(fn ($opcao) => mb_strtolower(trim((string) $opcao)))
            ->filter()
            ->values()
            ->all();

        return implode('|', [
            mb_strtolower(trim((string) $questao->texto)),
            (string) ($questao->tipo ?? 'texto'),
            (string) ($questao->escala_id ?? ''),
            json_encode($opcoes),
        ]);
    }

    private function enrichPerguntaResumo(array $pergunta): array
    {
        if (! empty($pergunta['resumo'])) {
            return $pergunta;
        }

        $tipo = $pergunta['tipo'] ?? 'texto';
        $total = (int) ($pergunta['total'] ?? 0);
        if ($total === 0) {
            $pergunta['resumo'] = 'Sem respostas';

            return $pergunta;
        }

        if (in_array($tipo, ['unica', 'multipla'], true)) {
            $labels = $pergunta['labels'] ?? [];
            $values = $pergunta['values'] ?? [];
            if (empty($labels) || empty($values)) {
                $pergunta['resumo'] = 'Respostas: '.$total;

                return $pergunta;
            }

            $maxValue = max($values);
            $maxIndex = array_search($maxValue, $values, true);
            $label = $labels[$maxIndex] ?? 'Opção';
            $totalSelecoes = array_sum($values);
            $percent = $totalSelecoes > 0 ? round(($maxValue / $totalSelecoes) * 100) : 0;

            $textoQuantidade = $maxValue === 1 ? '1 resposta' : $maxValue.' respostas';
            $pergunta['resumo'] = 'Mais citado: '.$label.' ('.$textoQuantidade.', '.$percent.'%)';

            return $pergunta;
        }

        if ($tipo === 'texto') {
            $totalTexto = (int) ($pergunta['respostas_total'] ?? $total);
            $pergunta['resumo'] = 'Respostas abertas: '.$totalTexto;

            return $pergunta;
        }

        $pergunta['resumo'] = 'Respostas: '.$total;

        return $pergunta;
    }

    private function calcularMediaGeral(Collection $perguntas): ?float
    {
        $totalPonderado = 0.0;
        $totalRespostas = 0;

        foreach ($perguntas as $pergunta) {
            $media = $pergunta['media'] ?? null;
            $total = (int) ($pergunta['total'] ?? 0);

            if ($media === null || $total === 0) {
                continue;
            }

            $totalPonderado += (float) $media * $total;
            $totalRespostas += $total;
        }

        if ($totalRespostas === 0) {
            return null;
        }

        return round($totalPonderado / $totalRespostas, 2);
    }

    private function sortGroups(Collection $groups, string $agrupamento): Collection
    {
        if (! in_array($agrupamento, ['regiao', 'municipio'], true)) {
            return $groups;
        }

        return $groups->sortBy(function (array $group) {
            return in_array($group['nome'], ['Sem região', 'Município não cadastrado'], true)
                ? 'zzzzzz'
                : mb_strtolower((string) $group['nome']);
        })->values();
    }
}
