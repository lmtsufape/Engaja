<?php

namespace App\Console\Commands\Cartas;

use App\Services\Cartas\CartaTimbradoService;
use Illuminate\Console\Command;
use RuntimeException;

class PreviewTimbrado extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cartas:preview-timbrado
        {texto?* : Texto da carta (várias palavras/argumentos são unidos com espaço)}
        {--arquivo= : Caminho de um .txt para usar como texto da carta}
        {--lorem : Usa um texto de exemplo longo, o suficiente para cruzar a ilustração do modelo e quebrar página}
        {--saida= : Caminho do PDF de saída (padrão: storage/app/cartas-preview.pdf)}
        {--abrir : Tenta abrir o PDF gerado no visualizador padrão do sistema}
        {--open : Alias de --abrir}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera o PDF do papel timbrado de Cartas a partir de um texto avulso, sem passar pelo fluxo de carta';

    public function handle(CartaTimbradoService $timbrado): int
    {
        $texto = $this->resolverTexto();

        if (trim($texto) === '') {
            $this->error('Informe um texto (argumento, --arquivo ou --lorem).');

            return self::FAILURE;
        }

        try {
            $conteudo = $timbrado->render($texto);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $saida = $this->option('saida') ?: storage_path('app/cartas-preview.pdf');
        file_put_contents($saida, $conteudo);

        $this->info("PDF gerado em: {$saida}");

        if ($this->option('abrir') || $this->option('open')) {
            $this->abrir($saida);
        }

        return self::SUCCESS;
    }

    private function resolverTexto(): string
    {
        if ($arquivo = $this->option('arquivo')) {
            if (! is_file($arquivo)) {
                throw new RuntimeException("Arquivo não encontrado: {$arquivo}");
            }

            return (string) file_get_contents($arquivo);
        }

        if ($this->option('lorem')) {
            return $this->textoLorem();
        }

        return implode(' ', $this->argument('texto'));
    }

    private function textoLorem(): string
    {
        $paragrafo = 'Esta é uma carta de teste gerada pelo comando cartas:preview-timbrado, usada para '.
            'conferir se o texto se ajusta corretamente ao papel timbrado, inclusive contornando a '.
            'ilustração do canto inferior direito e quebrando para uma nova página quando necessário.';

        return implode("\n\n", array_fill(0, 4, str_repeat("{$paragrafo} ", 6)));
    }

    private function abrir(string $caminho): void
    {
        $comando = match (true) {
            PHP_OS_FAMILY === 'Darwin' => 'open',
            PHP_OS_FAMILY === 'Windows' => 'start',
            default => 'xdg-open',
        };

        exec(escapeshellcmd($comando).' '.escapeshellarg($caminho).' > /dev/null 2>&1 &');
    }
}
