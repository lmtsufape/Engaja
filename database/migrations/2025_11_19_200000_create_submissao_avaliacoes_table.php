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
        Schema::create('submissao_avaliacoes', function (Blueprint $table) {
            $table->id();
            // ULID curto para identificar a submissão de forma única.
            $table->string('codigo', 26)->unique();
            $table->foreignId('atividade_id')->constrained('atividades')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submissao_avaliacoes');
    }
};
