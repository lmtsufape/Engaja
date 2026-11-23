<?php

namespace App\Http\Controllers\Concerns;

trait ResolvesPdfBrandMargin
{
    /**
     * Calcula a margem (em mm) necessária para acomodar uma imagem de
     * cabeçalho/rodapé que é renderizada a 100% da largura da página pelo
     * Puppeteer. A altura renderizada = largura da página × (altura/largura da
     * imagem); somamos uma folga de 3mm. Cai no fallback se a imagem não existir
     * ou não puder ser lida, mantendo o comportamento correto para qualquer
     * proporção de imagem.
     */
    protected function brandImageMarginMm(string $relativePath, float $pageWidthMm, int $fallback): int
    {
        $path = public_path($relativePath);

        if (! is_file($path)) {
            return $fallback;
        }

        $dimensoes = @getimagesize($path);
        if ($dimensoes === false || empty($dimensoes[0])) {
            return $fallback;
        }

        [$largura, $altura] = $dimensoes;
        $alturaMm = $pageWidthMm * ($altura / $largura);

        return (int) ceil($alturaMm + 3);
    }
}
