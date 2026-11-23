<?php

namespace App\Console\Commands;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use App\Models\Evento;
use App\Models\Inscricao;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Popula a caixa de um voluntario com muitas cartas, para avaliar o
 * carrossel da tela "Suas cartas" em volume (navegacao, desempenho das
 * transicoes, ordenacao por prioridade).
 *
 * Cria apenas educandos marcados como de volume, o que permite remover
 * tudo depois com --limpar sem tocar no restante dos dados.
 */
class CartasGerarVolumeTeste extends Command
{
    protected $signature = 'cartas:gerar-volume-teste
        {--voluntario=voluntario0.cartas.test@example.com : E-mail do voluntario que recebera as cartas}
        {--quantidade=30 : Quantas cartas gerar}
        {--limpar : Remove as cartas de volume geradas anteriormente e encerra}';

    protected $description = 'Gera cartas de teste em volume para um voluntario, para avaliar o carrossel da tela do voluntario';

    /** Prefixo de e-mail que identifica os educandos criados por este comando. */
    private const PREFIXO_EMAIL = 'volume-teste-';

    public function handle(): int
    {
        $voluntario = User::where('email', $this->option('voluntario'))->first();

        if (! $voluntario) {
            $this->error("Voluntario nao encontrado: {$this->option('voluntario')}");

            return self::FAILURE;
        }

        if ($this->option('limpar')) {
            return $this->limpar();
        }

        $quantidade = max(1, (int) $this->option('quantidade'));

        $evento = Evento::where('is_cartas', true)->first();

        if (! $evento) {
            $this->error('Nenhum evento de Cartas encontrado. Rode `php artisan db:seed --class=CartasTestSeeder` antes.');

            return self::FAILURE;
        }

        // Alterna entre os quatro status que a tela do voluntario reconhece,
        // para que os selos/carimbos apareçam variados no carrossel.
        $status = [
            Carta::STATUS_AGUARDANDO_VOLUNTARIO,
            Carta::STATUS_RESPONDIDA,
            Carta::STATUS_AGUARDANDO_VERIFICACAO,
            Carta::STATUS_AGUARDANDO_AJUSTE,
        ];

        $barra = $this->output->createProgressBar($quantidade);
        $barra->start();

        DB::transaction(function () use ($quantidade, $voluntario, $evento, $status, $barra) {
            foreach (range(1, $quantidade) as $i) {
                $educando = $this->educandoDeVolume($i);

                Inscricao::firstOrCreate(
                    ['evento_id' => $evento->id, 'participante_id' => $educando->id],
                    []
                );

                $carta = Carta::factory()->create([
                    'educando_participante_id' => $educando->id,
                    'voluntario_user_id' => $voluntario->id,
                    'evento_id' => $evento->id,
                    'status' => $status[$i % count($status)],
                    'distribuida_em' => now()->subDays($quantidade - $i),
                ]);

                $this->criarMensagens($carta);
                $barra->advance();
            }
        });

        $barra->finish();
        $this->newLine(2);

        $total = Carta::doVoluntario($voluntario)->count();
        $this->info("✓ {$quantidade} cartas geradas para {$voluntario->name} ({$voluntario->email}).");
        $this->line("  Total de cartas desse voluntario agora: {$total}");
        $this->line('  Entre com esse e-mail em /cartas/login para ver o carrossel.');
        $this->line('  Para remover depois: php artisan cartas:gerar-volume-teste --limpar');

        return self::SUCCESS;
    }

    /**
     * Cria (ou reaproveita) um educando dedicado ao teste de volume.
     */
    private function educandoDeVolume(int $indice): Participante
    {
        $user = User::firstOrCreate(
            ['email' => self::PREFIXO_EMAIL."{$indice}@example.com"],
            [
                'name' => "Educando Volume Teste {$indice}",
                'sistema_origem' => User::SISTEMA_ENGAJA,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]
        );

        return Participante::firstOrCreate(
            ['user_id' => $user->id],
            ['municipio_id' => null, 'telefone' => '11999999999']
        );
    }

    /**
     * Monta mensagens coerentes com o status da carta: a rodada 1 vem sempre
     * do educando; a rodada 2 so existe quando o voluntario ja respondeu.
     */
    private function criarMensagens(Carta $carta): void
    {
        CartaMensagem::factory()->create([
            'carta_id' => $carta->id,
            'rodada' => 1,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
            'remetente_participante_id' => $carta->educando_participante_id,
            'destinatario_user_id' => $carta->voluntario_user_id,
            'status' => CartaMensagem::STATUS_APROVADA,
            'canal_entrada' => CartaMensagem::CANAL_ANEXO_DIGITALIZADO,
        ]);

        $statusResposta = match ($carta->status) {
            Carta::STATUS_RESPONDIDA => CartaMensagem::STATUS_APROVADA,
            Carta::STATUS_AGUARDANDO_VERIFICACAO => CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO,
            Carta::STATUS_AGUARDANDO_AJUSTE => CartaMensagem::STATUS_AJUSTE_SOLICITADO,
            default => null,
        };

        if ($statusResposta === null) {
            return;
        }

        CartaMensagem::factory()->create([
            'carta_id' => $carta->id,
            'rodada' => 2,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO,
            'remetente_user_id' => $carta->voluntario_user_id,
            'destinatario_participante_id' => $carta->educando_participante_id,
            'status' => $statusResposta,
            'canal_entrada' => CartaMensagem::CANAL_DIGITADA,
            'texto' => 'Mensagem gerada para teste de volume do carrossel.',
        ]);
    }

    /**
     * Remove somente o que este comando criou (cartas dos educandos de volume).
     */
    private function limpar(): int
    {
        $participanteIds = Participante::whereHas(
            'user',
            fn ($q) => $q->where('email', 'like', self::PREFIXO_EMAIL.'%')
        )->pluck('id');

        if ($participanteIds->isEmpty()) {
            $this->info('Nenhuma carta de volume encontrada para remover.');

            return self::SUCCESS;
        }

        $cartaIds = Carta::whereIn('educando_participante_id', $participanteIds)->pluck('id');

        DB::transaction(function () use ($cartaIds, $participanteIds) {
            CartaMensagem::whereIn('carta_id', $cartaIds)->delete();
            Carta::whereIn('id', $cartaIds)->delete();
            Inscricao::whereIn('participante_id', $participanteIds)->delete();
        });

        $this->info("✓ {$cartaIds->count()} cartas de volume removidas.");
        $this->line('  Os educandos "Volume Teste" foram mantidos para reuso.');

        return self::SUCCESS;
    }
}
