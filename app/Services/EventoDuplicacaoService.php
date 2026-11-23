<?php

namespace App\Services;

use App\Models\Atividade;
use App\Models\Evento;
use Illuminate\Support\Facades\DB;

/**
Serviço responsável por duplicar uma Ação Pedagógic.

O que é copiado:
-Todos os campos de edição do evento (nome, tipo, datas, objetivos, etc)
-Relações many-to-many de configuração (matrizes, situações desafiadoras)
-Sequências didáticas
-Mmomentos com seus municípios vinculados
*/
class EventoDuplicacaoService
{
    /**
     * Executa a duplicação completa dentro de uma transação atômica.
     *
     * @throws \Throwable
     */
    public function duplicar(Evento $original): Evento
    {
        return DB::transaction(function () use ($original): Evento {
            $copia = $this->duplicarEvento($original);
            $this->duplicarRelacionamentosMany($original, $copia);
            $this->duplicarSequenciasDidaticas($original, $copia);
            $this->duplicarAtividades($original, $copia);

            return $copia;
        });
    }

    private function duplicarEvento(Evento $original): Evento
    {
        $copia = $original->replicate([
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'checklist_planejamento',
        ]);

        $copia->nome                  = '[Cópia] ' . $original->nome;
        $copia->user_id               = auth()->id();
        $copia->checklist_planejamento = [];
        $copia->save();

        return $copia;
    }

    private function duplicarRelacionamentosMany(Evento $original, Evento $copia): void
    {
        $copia->matrizes()->sync(
            $original->matrizes()->pluck('matrizes_aprendizagem.id')
        );

        $copia->situacoesDesafiadoras()->sync(
            $original->situacoesDesafiadoras()->pluck('situacoes_desafiadoras.id')
        );
    }

    /**
     * Duplica as sequências didáticas do evento.
     */
    private function duplicarSequenciasDidaticas(Evento $original, Evento $copia): void
    {
        $original->sequenciasDidaticas()->each(function ($seq) use ($copia): void {
            $copia->sequenciasDidaticas()->create([
                'periodo'   => $seq->periodo,
                'descricao' => $seq->descricao,
            ]);
        });
    }

    /**
     * Duplica os momentos — apenas estrutura de configuração.
     * Inscrições, presenças e avaliações não são copiadas.
     */
    private function duplicarAtividades(Evento $original, Evento $copia): void
    {
        $original->atividades()
            ->with('municipios')
            ->get()
            ->each(function (Atividade $atividade) use ($copia): void {
                $novaAtividade = $atividade->replicate([
                    'id',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'checklist_planejamento',
                    'checklist_encerramento',
                ]);

                $novaAtividade->evento_id             = $copia->id;
                $novaAtividade->checklist_planejamento = [];
                $novaAtividade->checklist_encerramento = [];
                $novaAtividade->save();

                //preserva os municípios vinculados ao momento
                if ($atividade->municipios->isNotEmpty()) {
                    $novaAtividade->municipios()->sync(
                        $atividade->municipios->pluck('id')
                    );
                }
            });
    }
}
