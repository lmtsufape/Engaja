<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vincula automaticamente todos os registros pendentes da tabela
 * curadoria_demograficos à tabela users, copiando os dados demográficos
 * diretamente para o usuário.
 *
 * A tabela curadoria_demograficos é mantida intacta (apenas os registros
 * são marcados como vinculado = true).
 */
return new class extends Migration
{
    /**
     * Campos demográficos que serão copiados de curadoria_demograficos → users.
     */
    private const CAMPOS = [
        'identidade_genero',
        'identidade_genero_outro',
        'raca_cor',
        'comunidade_tradicional',
        'comunidade_tradicional_outro',
        'faixa_etaria',
        'pcd',
        'orientacao_sexual',
        'orientacao_sexual_outra',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('curadoria_demograficos')) {
            return;
        }

        // Pega todos os registros ainda não vinculados, priorizando o mais
        // recente por usuário (caso haja duplicatas).
        $pendentes = DB::table('curadoria_demograficos')
            ->where('vinculado', false)
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupa por user_id e pega apenas o primeiro (mais recente) de cada
        $porUsuario = $pendentes->groupBy('user_id')->map->first();

        foreach ($porUsuario as $curadoria) {
            // Monta apenas os campos que realmente têm valor preenchido
            $dados = [];
            foreach (self::CAMPOS as $campo) {
                if (! empty($curadoria->{$campo})) {
                    $dados[$campo] = $curadoria->{$campo};
                }
            }

            if (empty($dados)) {
                // Nada para copiar, apenas marca como vinculado
                DB::table('curadoria_demograficos')
                    ->where('user_id', $curadoria->user_id)
                    ->update(['vinculado' => true]);
                continue;
            }

            // Atualiza o usuário somente se ele ainda não tiver dados completos
            $usuario = DB::table('users')->where('id', $curadoria->user_id)->first();
            if (! $usuario) {
                continue;
            }

            // Verifica se o usuário já possui todos os campos obrigatórios preenchidos
            $jaCompleto = ! empty($usuario->identidade_genero)
                && ! empty($usuario->raca_cor)
                && ! empty($usuario->comunidade_tradicional)
                && ! empty($usuario->faixa_etaria)
                && ! empty($usuario->pcd)
                && ! empty($usuario->orientacao_sexual);

            if ($jaCompleto) {
                // Já está completo, apenas marca como vinculado
                DB::table('curadoria_demograficos')
                    ->where('user_id', $curadoria->user_id)
                    ->update(['vinculado' => true]);
                continue;
            }

            // Copia os dados demográficos para o usuário
            DB::table('users')
                ->where('id', $curadoria->user_id)
                ->update($dados);

            // Marca todos os registros deste usuário como vinculados
            DB::table('curadoria_demograficos')
                ->where('user_id', $curadoria->user_id)
                ->update(['vinculado' => true]);
        }
    }

    public function down(): void
    {
        // Apenas reseta o flag de vinculação — os dados copiados para
        // a tabela users permanecem (não há perda).
        if (Schema::hasTable('curadoria_demograficos')) {
            DB::table('curadoria_demograficos')->update(['vinculado' => false]);
        }
    }
};

