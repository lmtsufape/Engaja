<?php

namespace App\Console\Commands;

use App\Models\Atividade;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SincronizarPresencaAgendada extends Command
{
    protected $signature = 'presenca:sincronizar-agendamentos';

    protected $description = 'Sincroniza e consolida os agendamentos de abertura e fechamento da presença nos momentos.';

    public function handle(): int
    {
        $agora = now();

        // Busca momentos com horários agendados de abertura ou fechamento já atingidos
        $atividades = Atividade::query()
            ->where(function ($q) use ($agora) {
                $q->whereNotNull('presenca_abre_em')
                    ->where('presenca_abre_em', '<=', $agora);
            })
            ->orWhere(function ($q) use ($agora) {
                $q->whereNotNull('presenca_fecha_em')
                    ->where('presenca_fecha_em', '<=', $agora);
            })
            ->get();

        if ($atividades->isEmpty()) {
            $this->info('Nenhum agendamento de presença pendente de consolidação.');

            return self::SUCCESS;
        }

        $total = 0;
        foreach ($atividades as $atividade) {
            $estadoAnterior = $atividade->presenca_ativa;
            $atividade->consolidarAgendamentoPresenca($agora);
            $atividade->save();

            $estadoNovo = $atividade->presenca_ativa ? 'ABERTA' : 'FECHADA';
            $msg = sprintf(
                'Presença do Momento #%d ("%s") sincronizada: %s (anterior: %s)',
                $atividade->id,
                $atividade->descricao,
                $estadoNovo,
                $estadoAnterior ? 'ABERTA' : 'FECHADA'
            );

            $this->line($msg);
            Log::info("Sincronização de presença: {$msg}");
            $total++;
        }

        $this->info("Total de momentos sincronizados: {$total}.");

        return self::SUCCESS;
    }
}
