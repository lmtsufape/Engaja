<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cartas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40);
            $table->foreignId('evento_id')->nullable()->constrained('eventos')->nullOnDelete();
            $table->foreignId('atividade_id')->nullable()->constrained('atividades')->nullOnDelete();
            $table->foreignId('educando_participante_id')->constrained('participantes')->cascadeOnDelete();
            $table->foreignId('voluntario_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('municipio_id')->nullable()->constrained('municipios')->nullOnDelete();
            $table->string('turma')->nullable();
            $table->string('status', 40)->default('aguardando_distribuicao');
            $table->timestamp('distribuida_em')->nullable();
            $table->timestamp('encerrada_em')->nullable();
            $table->foreignId('criada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('atualizada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['evento_id', 'codigo'], 'cartas_evento_codigo_unique');
            $table->index('status', 'cartas_status_index');
            $table->index(['voluntario_user_id', 'status'], 'cartas_voluntario_status_index');
            $table->index('educando_participante_id', 'cartas_educando_index');
            $table->index(['municipio_id', 'turma'], 'cartas_municipio_turma_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cartas');
    }
};
