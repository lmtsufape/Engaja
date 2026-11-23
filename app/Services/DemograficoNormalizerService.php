<?php

namespace App\Services;

/**
 * Normaliza valores de campos demográficos provenientes de planilhas de importação.
 *
 * Para cada campo, compara o texto digitado na planilha (de forma case/acento-insensitive)
 * com as opções válidas definidas em config('engaja.demograficos'). Quando o valor não
 * corresponde a nenhuma opção e o campo aceita "Outro", o texto original é preservado
 * no campo auxiliar (ex: identidade_genero_outro).
 */
class DemograficoNormalizerService
{
    /**
     * Lookups pré-computados: campo → [slug → opção canônica].
     *
     * @var array<string, array<string, string>>
     */
    private array $lookups = [];

    /**
     * Configuração completa dos campos demográficos.
     *
     * @var array<string, array{opcoes: list<string>, campo_outro: ?string, valor_outro?: string}>
     */
    private array $config;

    public function __construct()
    {
        $this->config = config('engaja.demograficos', []);

        foreach ($this->config as $campo => $definicao) {
            $this->lookups[$campo] = [];
            foreach ($definicao['opcoes'] ?? [] as $opcao) {
                $this->lookups[$campo][$this->slugify($opcao)] = $opcao;

                // Para opções que têm explicação em parênteses (ex: "Adulto (18 a 59 anos)"), 
                // registra também a parte antes do parêntese como alias válido ("Adulto")
                if (str_contains($opcao, '(')) {
                    $prefixo = trim(explode('(', $opcao)[0]);
                    $this->lookups[$campo][$this->slugify($prefixo)] = $opcao;
                }
            }
        }
    }

    /**
     * Normaliza um valor demográfico da planilha.
     *
     * @return array{campo: ?string, campo_outro: ?string, ok: bool}
     *   - campo:       valor canônico da opção (ou valor_outro se fallback)
     *   - campo_outro: texto livre original quando cai em "outro", null caso contrário
     *   - ok:          true se normalizou com sucesso (match ou fallback "outro"), false se inválido
     */
    public function normalize(string $campo, ?string $valor): array
    {
        $vazio = ['campo' => null, 'campo_outro' => null, 'ok' => true];

        if ($valor === null || trim($valor) === '') {
            return $vazio;
        }

        $valor = trim($valor);
        $slug = $this->slugify($valor);

        if ($slug === '') {
            return $vazio;
        }

        $lookup = $this->lookups[$campo] ?? [];
        $definicao = $this->config[$campo] ?? [];

        // Match direto com alguma opção conhecida
        if (isset($lookup[$slug])) {
            $opcaoCanonica = $lookup[$slug];
            $campoOutro = $definicao['campo_outro'] ?? null;
            $valorOutro = $definicao['valor_outro'] ?? null;

            // Se o match é com a própria opção "Outro/Outra", campo_outro fica null
            // (o usuário escolheu explicitamente "Outro" sem especificar)
            $ehOpcaoOutro = $valorOutro !== null && $opcaoCanonica === $valorOutro;

            return [
                'campo' => $opcaoCanonica,
                'campo_outro' => $ehOpcaoOutro ? null : null,
                'ok' => true,
            ];
        }

        // Sem match — verificar se o campo aceita "outro"
        $campoOutro = $definicao['campo_outro'] ?? null;
        $valorOutro = $definicao['valor_outro'] ?? null;

        if ($campoOutro !== null && $valorOutro !== null) {
            return [
                'campo' => $valorOutro,
                'campo_outro' => $valor,
                'ok' => true,
            ];
        }

        // Sem match e sem "outro" — valor inválido
        return ['campo' => null, 'campo_outro' => null, 'ok' => false];
    }

    /**
     * Normaliza todos os campos demográficos de uma linha da planilha.
     *
     * @param  array<string, mixed>  $row  Linha da planilha com chaves demográficas
     * @return array<string, mixed>  Dados normalizados para cada campo + flags _ok
     */
    public function normalizeRow(array $row): array
    {
        $resultado = [];

        foreach ($this->config as $campo => $definicao) {
            $valor = $row[$campo] ?? null;
            $normalized = $this->normalize($campo, is_string($valor) ? $valor : null);

            $resultado[$campo] = $normalized['campo'];
            $resultado[$campo.'_ok'] = $normalized['ok'];

            $campoOutro = $definicao['campo_outro'] ?? null;
            if ($campoOutro !== null) {
                // Preserva valor_outro da planilha se já preenchido, senão usa o normalizado
                $resultado[$campoOutro] = $normalized['campo_outro']
                    ?? (isset($row[$campoOutro]) && is_string($row[$campoOutro]) ? trim($row[$campoOutro]) : null);
            }
        }

        return $resultado;
    }

    /**
     * Retorna os aliases de cabeçalho aceitos para cada campo demográfico.
     *
     * @return array<string, list<string>>
     */
    public static function headerAliases(): array
    {
        return [
            'identidade_genero' => [
                'identidade_genero', 'genero', 'identidade de genero',
                'gênero', 'identidade_de_genero', 'identidade de gênero',
            ],
            'raca_cor' => [
                'raca_cor', 'raca', 'raça', 'cor', 'raça/cor', 'raca/cor',
                'raca cor', 'raça cor',
            ],
            'comunidade_tradicional' => [
                'comunidade_tradicional', 'comunidade', 'pertencimento',
                'comunidade tradicional', 'pertencimento a comunidade',
            ],
            'faixa_etaria' => [
                'faixa_etaria', 'faixa etaria', 'faixa etária',
                'idade', 'faixa_de_idade', 'faixa de idade',
            ],
            'pcd' => [
                'pcd', 'deficiencia', 'deficiência',
                'pessoa com deficiencia', 'pessoa com deficiência',
            ],
            'orientacao_sexual' => [
                'orientacao_sexual', 'orientação sexual', 'orientacao',
                'orientação', 'orientacao sexual',
            ],
        ];
    }

    /**
     * Remove acentos, converte para minúsculo e normaliza espaços.
     * Usa Normalizer::NFD (extensão intl) para garantir funcionamento cross-platform.
     */
    private function slugify(string $s): string
    {
        $s = trim(mb_strtolower($s));
        // Decompor caracteres acentuados em base + diacrítico e remover os diacríticos
        $s = preg_replace('/\p{Mn}/u', '', \Normalizer::normalize($s, \Normalizer::NFD) ?: $s);
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);

        return trim((string) $s);
    }
}
