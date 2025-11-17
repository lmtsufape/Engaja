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
        Schema::create('questaos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indicador_id')->nullable()->constrained('indicadors');
            $table->foreignId('escala_id')->nullable()->constrained('escalas');
            $table->foreignId('evidencia_id')->nullable()->constrained('evidencias');
            $table->unsignedBigInteger('template_avaliacao_id')->nullable();
            $table->string('texto');
            $table->string('tipo')->default('texto');
            $table->unsignedInteger('ordem')->nullable();
            $table->boolean('fixa')->default(false);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questaos');
    }
};
