<?php

namespace App\ViewModels\Avaliacao;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\MessageBag;

class QuestoesFormViewModel implements Arrayable
{
    private Collection $templates;

    private ?string $selectedTemplateId;

    /**
     * @var array<int|string, array<string, mixed>>
     */
    private array $personalizacoes;

    /**
     * @var array<int, array<string, mixed>>
     */
    private array $questoesAdicionais;

    /**
     * @var array<string, string>
     */
    private array $tiposQuestao;

    /**
     * @var array<int|string, string>
     */
    private array $evidenciasOptions;

    private Collection $evidenciasData;

    /**
     * @var array<int|string, string>
     */
    private array $escalasOptions;

    private Collection $escalasData;

    /**
     * @var array<int|string, mixed>
     */
    private array $respostas;

    private bool $exibirRespostas;

    private MessageBag $errors;

    /**
     * @var array<string, mixed>
     */
    private array $oldInput;

    public function __construct(
        Collection $templates,
        ?string $selectedTemplateId,
        array $personalizacoes,
        array $questoesAdicionais,
        array $tiposQuestao,
        array $evidenciasOptions,
        Collection $evidenciasData,
        array $escalasOptions,
        Collection $escalasData,
        array $respostas,
        bool $exibirRespostas,
        ?MessageBag $errors,
        array $oldInput
    ) {
        $this->templates = $templates;
        $this->selectedTemplateId = $selectedTemplateId !== null ? (string) $selectedTemplateId : null;
        $this->personalizacoes = $personalizacoes;
        $this->questoesAdicionais = $questoesAdicionais;
        $this->tiposQuestao = $tiposQuestao;
        $this->evidenciasOptions = $evidenciasOptions;
        $this->evidenciasData = $evidenciasData;
        $this->escalasOptions = $escalasOptions;
        $this->escalasData = $escalasData;
        $this->respostas = $respostas;
        $this->exibirRespostas = $exibirRespostas;
        $this->errors = $errors instanceof MessageBag ? $errors : new MessageBag();
        $this->oldInput = $oldInput;
    }

    public static function make(
        Collection $templates,
        ?string $selectedTemplateId,
        array $personalizacoes,
        array $questoesAdicionais,
        array $tiposQuestao,
        array $evidenciasOptions,
        Collection $evidenciasData,
        array $escalasOptions,
        Collection $escalasData,
        array $respostas,
        bool $exibirRespostas,
        ?MessageBag $errors,
        array $oldInput
    ): self {
        return new self(
            $templates,
            $selectedTemplateId,
            $personalizacoes,
            $questoesAdicionais,
            $tiposQuestao,
            $evidenciasOptions,
            $evidenciasData,
            $escalasOptions,
            $escalasData,
            $respostas,
            $exibirRespostas,
            $errors,
            $oldInput
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'selected_template_id' => $this->selectedTemplateId,
            'templates' => $this->buildTemplates(),
            'adicionais' => $this->buildAdicionais(),
            'options' => [
                'tipos' => $this->makeOptions($this->tiposQuestao, 'texto', false),
                'evidencias' => $this->makeOptions($this->evidenciasOptions, null, true),
                'escalas' => $this->makeOptions($this->escalasOptions, null, true),
            ],
            'option_maps' => [
                'tipos' => $this->tiposQuestao,
                'evidencias' => $this->evidenciasOptions,
                'escalas' => $this->escalasOptions,
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTemplates(): array
    {
        return $this->templates
            ->map(function ($template) {
                $templateId = (string) $template->id;
                $ativo = $this->selectedTemplateId === null
                    ? false
                    : $this->selectedTemplateId === $templateId;

                $questoes = collect($template->questoes ?? [])
                    ->map(function ($questao, int $index) use ($ativo) {
                        return $this->buildQuestao($questao, $index, $ativo);
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'id' => $templateId,
                    'nome' => $template->nome,
                    'active' => $ativo,
                    'questoes' => $questoes,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param mixed $questao
     * @return array<string, mixed>
     */
    private function buildQuestao($questao, int $index, bool $templateAtivo): array
    {
        $questaoId = $questao->id;
        $questaoKey = (string) $questaoId;
        $baseKey = "questoes.$questaoKey";

        $personalizacao = $this->personalizacoes[$questaoKey] ?? $this->personalizacoes[$questaoId] ?? [];

        $textoDefault = $questao->fixa
            ? $questao->texto
            : ($personalizacao['texto'] ?? $questao->texto);

        $textoValue = $questao->fixa
            ? null
            : $this->oldValue("$baseKey.texto", $personalizacao['texto'] ?? $questao->texto);

        $tipoDefault = $questao->fixa
            ? $questao->tipo
            : ($personalizacao['tipo'] ?? $questao->tipo);

        $tipoSelecionado = $this->normalizeTipo(
            (string) $this->oldValue("$baseKey.tipo", $tipoDefault ?? ''),
            (string) $questao->tipo
        );

        $evidenciaDefault = $questao->fixa
            ? $questao->evidencia_id
            : ($personalizacao['evidencia_id'] ?? $questao->evidencia_id);

        $evidenciaSelecionada = $this->normalizeNullableId(
            $this->oldValue("$baseKey.evidencia_id", $evidenciaDefault)
        );

        $escalaDefault = $questao->fixa
            ? $questao->escala_id
            : ($personalizacao['escala_id'] ?? $questao->escala_id);

        $escalaSelecionada = $this->normalizeNullableId(
            $this->oldValue("$baseKey.escala_id", $escalaDefault)
        );

        $escalaAtual = $tipoSelecionado !== 'escala'
            ? ($questao->fixa ? $questao->escala_id : null)
            : $escalaSelecionada;

        $indicadorAtual = $questao->indicador;
        if (! $questao->fixa && $evidenciaSelecionada !== null) {
            $evidenciaLookup = (int) $evidenciaSelecionada;
            if ($this->evidenciasData->has($evidenciaLookup)) {
                $indicadorAtual = $this->evidenciasData->get($evidenciaLookup)->indicador;
            }
        }

        $dimensaoAtual = optional($indicadorAtual)->dimensao;

        $escalaSelecionadaModel = null;
        if ($tipoSelecionado === 'escala') {
            if ($escalaAtual !== null && $this->escalasData->has((int) $escalaAtual)) {
                $escalaSelecionadaModel = $this->escalasData->get((int) $escalaAtual);
            } elseif ($questao->escala) {
                $escalaSelecionadaModel = $questao->escala;
            }
        }

        $opcoesEscala = [];
        if ($escalaSelecionadaModel) {
            foreach (range(1, 5) as $indice) {
                $valor = $escalaSelecionadaModel->{"opcao$indice"} ?? null;
                if ($valor !== null && $valor !== '') {
                    $opcoesEscala[] = $valor;
                }
            }
        }

        $respostaAtual = $this->respostas[$questaoKey] ?? null;

        $tipoLabel = $this->tiposQuestao[$questao->tipo] ?? ucfirst((string) $questao->tipo);
        $evidenciaLabel = optional($questao->evidencia)->descricao ?? 'Sem evidencia';
        $escalaLabel = $questao->escala && $questao->escala->descricao
            ? $questao->escala->descricao
            : ($questao->tipo === 'escala' ? 'Defina uma escala' : '---');

        return [
            'key' => $questaoKey,
            'card' => [
                'label' => 'Questao ' . ($questao->ordem ?? ($index + 1)),
                'badge' => [
                    'label' => $questao->fixa ? 'Fixa' : 'Personalizavel',
                    'class' => $questao->fixa
                        ? 'bg-light text-muted border'
                        : 'bg-primary-subtle text-primary border-primary',
                ],
            ],
            'meta' => [
                'indicador' => $indicadorAtual->descricao ?? '-',
                'dimensao' => optional($dimensaoAtual)->descricao,
            ],
            'fixa' => (bool) $questao->fixa,
            'texto_display' => $questao->texto,
            'resumo' => [
                'tipo' => $tipoLabel,
                'evidencia' => $evidenciaLabel,
                'escala' => $escalaLabel,
            ],
            'form' => [
                'disabled' => ! $templateAtivo,
                'texto' => [
                    'show' => ! $questao->fixa,
                    'id' => "questoes-{$questaoKey}-texto",
                    'name' => "questoes[{$questaoKey}][texto]",
                    'value' => $textoValue,
                    'error' => $this->errors->first("$baseKey.texto"),
                ],
                'tipo' => [
                    'id' => "questoes-{$questaoKey}-tipo",
                    'name' => "questoes[{$questaoKey}][tipo]",
                    'options' => $this->makeOptions($this->tiposQuestao, $tipoSelecionado, false),
                    'error' => $this->errors->first("$baseKey.tipo"),
                ],
                'evidencia' => [
                    'id' => "questoes-{$questaoKey}-evidencia_id",
                    'name' => "questoes[{$questaoKey}][evidencia_id]",
                    'options' => $this->makeOptions($this->evidenciasOptions, $evidenciaSelecionada, true),
                    'error' => $this->errors->first("$baseKey.evidencia_id"),
                ],
                'escala' => [
                    'id' => "questoes-{$questaoKey}-escala_id",
                    'name' => "questoes[{$questaoKey}][escala_id]",
                    'options' => $this->makeOptions($this->escalasOptions, $escalaAtual, true),
                    'error' => $this->errors->first("$baseKey.escala_id"),
                    'visible' => $tipoSelecionado === 'escala',
                ],
            ],
            'resposta' => [
                'show' => $this->exibirRespostas,
                'tipo' => $tipoSelecionado,
                'valor' => $respostaAtual,
                'escala_opcoes' => $opcoesEscala,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAdicionais(): array
    {
        $cards = collect($this->questoesAdicionais)
            ->values()
            ->map(function ($questao, int $index) {
                $baseKey = "questoes_adicionais.$index";

                $questaoId = $questao['id'] ?? null;

                $textoValue = $this->oldValue("$baseKey.texto", $questao['texto'] ?? '');
                $textoValue = is_string($textoValue) ? trim($textoValue) : '';

                $tipoSelecionado = $this->normalizeTipo(
                    (string) $this->oldValue("$baseKey.tipo", $questao['tipo'] ?? 'texto'),
                    'texto'
                );

                $evidenciaSelecionada = $this->normalizeNullableId(
                    $this->oldValue("$baseKey.evidencia_id", $questao['evidencia_id'] ?? null)
                );

                $escalaSelecionada = $this->normalizeNullableId(
                    $this->oldValue("$baseKey.escala_id", $questao['escala_id'] ?? null)
                );

                $ordemValue = $this->oldValue("$baseKey.ordem", $questao['ordem'] ?? '');
                $ordemValue = is_scalar($ordemValue) ? (string) $ordemValue : '';

                $deleteRaw = $this->oldValue("$baseKey._delete", $questao['_delete'] ?? '0');
                $deleteValue = (string) $deleteRaw === '1' ? '1' : '0';

                return [
                    'index' => $index,
                    'questao' => [
                        'id' => $questaoId,
                        'texto' => $textoValue,
                        'tipo' => $tipoSelecionado,
                        'evidencia_id' => $evidenciaSelecionada,
                        'escala_id' => $escalaSelecionada,
                        'ordem' => $ordemValue,
                        'fixa' => false,
                    ],
                    'delete_value' => $deleteValue,
                    'hidden' => $deleteValue === '1',
                ];
            })
            ->values();

        $visibleCount = $cards
            ->filter(fn (array $card) => ($card['delete_value'] ?? '0') !== '1')
            ->count();

        return [
            'cards' => $cards->all(),
            'empty' => $visibleCount === 0,
            'prototype' => [
                'index' => '__INDEX__',
                'questao' => [
                    'id' => null,
                    'texto' => '',
                    'tipo' => 'texto',
                    'evidencia_id' => null,
                    'escala_id' => null,
                    'ordem' => '',
                    'fixa' => false,
                ],
                'delete_value' => '0',
            ],
        ];
    }

    /**
     * @param array<int|string, string>|array<string, string> $options
     * @return array<int, array<string, string|bool>>
     */
    private function makeOptions(array $options, $selected, bool $withEmpty): array
    {
        $selectedNormalized = $selected === null ? null : (string) $selected;

        $items = [];

        if ($withEmpty) {
            $items[] = [
                'value' => '',
                'label' => 'Selecione...',
                'selected' => $selectedNormalized === null || $selectedNormalized === '',
            ];
        }

        foreach ($options as $value => $label) {
            $items[] = [
                'value' => (string) $value,
                'label' => $label,
                'selected' => $selectedNormalized !== null && $selectedNormalized !== ''
                    ? ((string) $value === $selectedNormalized)
                    : false,
            ];
        }

        return $items;
    }

    /**
     * @return array<int|string|null>
     */
    private function normalizeNullableId($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function normalizeTipo(?string $tipo, string $fallback): string
    {
        $valor = $tipo ?? '';
        if ($valor === '') {
            return $fallback;
        }

        if (! array_key_exists($valor, $this->tiposQuestao)) {
            return $fallback;
        }

        return $valor;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    private function oldValue(string $key, $default = null)
    {
        return Arr::get($this->oldInput, $key, $default);
    }
}
