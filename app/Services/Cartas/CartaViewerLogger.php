<?php

namespace App\Services\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Log em formato de etapas da visualização de uma carta em PDF.
 *
 * Cada etapa vira uma linha única e legível no canal `cartas`, dizendo apenas
 * o que deu certo e o que falhou:
 *
 *   msg#30 etapa 2/6 arquivo: OK — anexo_original, 84.2 KB
 *   msg#30 etapa 6/6 render: FALHOU — InvalidPDFException: Invalid PDF structure
 *
 * As etapas 1 a 4 acontecem no servidor; 5 e 6 são reportadas pelo navegador
 * (pdf.js) através de CartaViewerDiagnosticController.
 */
class CartaViewerLogger
{
    /**
     * Nome do canal de log usado por todo o diagnóstico do visualizador.
     */
    public const CANAL = 'cartas';

    public const ETAPA_AUTORIZACAO = 1;

    public const ETAPA_ARQUIVO = 2;

    public const ETAPA_CONTEUDO = 3;

    public const ETAPA_ENTREGA = 4;

    public const ETAPA_DOWNLOAD = 5;

    public const ETAPA_RENDER = 6;

    private const TOTAL_ETAPAS = 6;

    /**
     * Rótulo de cada etapa, na ordem em que devem aparecer no log.
     */
    private const ROTULOS = [
        self::ETAPA_AUTORIZACAO => 'autorizacao',
        self::ETAPA_ARQUIVO => 'arquivo',
        self::ETAPA_CONTEUDO => 'conteudo',
        self::ETAPA_ENTREGA => 'entrega',
        self::ETAPA_DOWNLOAD => 'download navegador',
        self::ETAPA_RENDER => 'render navegador',
    ];

    /**
     * Registra o resultado de uma etapa. Falhas entram como ERROR para a
     * equipe conseguir filtrar só os problemas com um grep.
     */
    public function passo(int $etapa, bool $sucesso, string $detalhe = '', ?int $mensagemId = null): void
    {
        $rotulo = self::ROTULOS[$etapa] ?? 'desconhecida';
        $identificador = $mensagemId !== null ? "msg#{$mensagemId} " : '';

        $linha = sprintf(
            '%setapa %d/%d %s: %s',
            $identificador,
            $etapa,
            self::TOTAL_ETAPAS,
            $rotulo,
            $sucesso ? 'OK' : 'FALHOU'
        );

        if ($detalhe !== '') {
            $linha .= ' — '.$detalhe;
        }

        $sucesso
            ? Log::channel(self::CANAL)->info($linha)
            : Log::channel(self::CANAL)->error($linha);
    }

    /**
     * Aviso fora da sequência de etapas (ex.: configuração suspeita que ainda
     * não impediu a visualização).
     */
    public function alerta(string $linha): void
    {
        Log::channel(self::CANAL)->warning($linha);
    }

    /**
     * Linha de abertura da carta: quem abriu e qual visualizador cada mensagem
     * vai receber. Serve de cabeçalho para as etapas que vêm em seguida.
     */
    public function aberturaDaCarta(Carta $carta, string $perfil, int $usuarioId): void
    {
        $resumo = $carta->mensagens
            ->map(fn (CartaMensagem $mensagem) => "msg#{$mensagem->id}: ".$this->visualizadorDe($mensagem))
            ->implode(', ');

        Log::channel(self::CANAL)->info(sprintf(
            'carta #%s aberta por %s#%d — %s',
            $carta->codigo ?? $carta->id,
            $perfil,
            $usuarioId,
            $resumo !== '' ? $resumo : 'sem mensagens'
        ));

        // Caso silencioso e comum: o arquivo é um PDF de verdade, mas o mime
        // gravado no banco não é application/pdf, então o pdf.js nem é chamado.
        $carta->mensagens->each(function (CartaMensagem $mensagem) {
            if (! str_starts_with($this->visualizadorDe($mensagem), 'arquivo nao renderizavel')) {
                return;
            }

            if ($this->inspecionarArquivo($mensagem)['parece_pdf']) {
                $this->alerta("msg#{$mensagem->id} ATENCAO: o arquivo e um PDF no disco, mas o mime gravado no banco nao e application/pdf — o pdf.js nao sera acionado");
            }
        });
    }

    /**
     * Qual visualizador a view escolhe para a mensagem — a decisão é feita só
     * pelo mime gravado no banco.
     */
    public function visualizadorDe(CartaMensagem $mensagem): string
    {
        $mime = $mensagem->arquivo_final_mime ?: $mensagem->anexo_original_mime;

        return match (true) {
            ! ($mensagem->anexo_original_path || $mensagem->arquivo_final_path) => 'texto (sem arquivo)',
            str_starts_with((string) $mime, 'image/') => 'imagem',
            $mime === 'application/pdf' => 'pdfjs',
            default => 'arquivo nao renderizavel (mime='.($mime ?: 'vazio').')',
        };
    }

    /**
     * Descrição curta do arquivo da mensagem: origem, tamanho e se o conteúdo
     * no disco realmente começa com a assinatura de um PDF.
     *
     * @return array{existe: bool, detalhe: string, parece_pdf: bool}
     */
    public function inspecionarArquivo(CartaMensagem $mensagem): array
    {
        $path = $mensagem->arquivo_final_path ?: $mensagem->anexo_original_path;
        $origem = $mensagem->arquivo_final_path ? 'arquivo_final' : 'anexo_original';

        if (! $path) {
            return ['existe' => false, 'detalhe' => 'mensagem sem arquivo (nenhum path gravado)', 'parece_pdf' => false];
        }

        $disco = Storage::disk('local');

        try {
            if (! $disco->exists($path)) {
                return ['existe' => false, 'detalhe' => "nao encontrado no disco: {$path}", 'parece_pdf' => false];
            }

            $tamanho = $disco->size($path);
            $pareceP = $this->pareceP($disco->path($path));

            return [
                'existe' => true,
                'detalhe' => sprintf('%s, %s', $origem, $this->formatarTamanho($tamanho)),
                'parece_pdf' => $pareceP,
            ];
        } catch (Throwable $e) {
            return ['existe' => false, 'detalhe' => 'erro ao ler o arquivo: '.$e->getMessage(), 'parece_pdf' => false];
        }
    }

    /**
     * Lê os primeiros bytes para distinguir um PDF íntegro de um arquivo vazio,
     * truncado ou de uma página HTML salva por engano.
     */
    public function pareceP(string $caminhoAbsoluto): bool
    {
        $handle = @fopen($caminhoAbsoluto, 'rb');

        if ($handle === false) {
            return false;
        }

        $cabecalho = (string) fread($handle, 5);
        fclose($handle);

        return str_starts_with($cabecalho, '%PDF-');
    }

    public function formatarTamanho(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1).' MB'
            : round($bytes / 1024, 1).' KB';
    }
}
