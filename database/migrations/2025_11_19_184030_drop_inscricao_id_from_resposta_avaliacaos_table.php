<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            if (Schema::hasColumn('resposta_avaliacaos', 'inscricao_id')) {
                $table->dropForeign(['inscricao_id']);
                $table->dropColumn('inscricao_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            if (!Schema::hasColumn('resposta_avaliacaos', 'inscricao_id')) {
                $table->foreignId('inscricao_id')
                    ->nullable()
                    ->after('avaliacao_questao_id')
                    ->constrained('inscricaos')
                    ->nullOnDelete();
            }
        });
    }
};
