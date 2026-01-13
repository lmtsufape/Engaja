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
            $table->foreignId('submissao_avaliacao_id')
                ->nullable()
                ->constrained('submissao_avaliacoes')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            $table->dropForeign(['submissao_avaliacao_id']);
            $table->dropColumn('submissao_avaliacao_id');
        });
    }
};
