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
        Schema::table('submissao_avaliacoes', function (Blueprint $table) {
            $table->foreignId('avaliacao_id')
                ->nullable()
                ->after('atividade_id')
                ->constrained('avaliacaos')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('submissao_avaliacoes', function (Blueprint $table) {
            $table->dropForeign(['avaliacao_id']);
            $table->dropColumn('avaliacao_id');
        });
    }
};
