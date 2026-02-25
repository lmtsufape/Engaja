<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avaliacao_atividades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atividade_id')
                ->unique()
                ->constrained('atividades')
                ->cascadeOnDelete();

            $table->string('nome_educador')->nullable();
            $table->unsignedSmallInteger('qtd_participantes_prefeitura')->nullable();
            $table->unsignedSmallInteger('qtd_participantes_movimentos_sociais')->nullable();
            $table->text('avaliacao_logistica')->nullable();
            $table->text('avaliacao_acolhimento_sme')->nullable();
            $table->text('avaliacao_recursos_materiais')->nullable();
            $table->text('avaliacao_planejamento')->nullable();
            $table->text('avaliacao_links_presenca')->nullable();
            $table->text('avaliacao_destaques')->nullable();
            $table->text('avaliacao_atuacao_equipe')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacao_atividades');
    }
};