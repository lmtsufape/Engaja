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
        Schema::create('bi_dimensao_valores', function (Blueprint $table) {
            $table->id();

            $table->string('codigo'); // MAS, FEM, BRANCA, PRETA, etc

            $table->foreignId('dimensao_id')->constrained('bi_dimensoes')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['dimensao_id', 'codigo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bi_dimensao_valores');
    }
};
