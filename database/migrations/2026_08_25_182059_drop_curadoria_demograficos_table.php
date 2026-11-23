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
        Schema::dropIfExists('curadoria_demograficos');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('curadoria_demograficos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('identidade_genero')->nullable();
            $table->string('identidade_genero_outro')->nullable();
            $table->string('raca_cor')->nullable();
            $table->string('comunidade_tradicional')->nullable();
            $table->string('comunidade_tradicional_outro')->nullable();
            $table->string('faixa_etaria')->nullable();
            $table->string('pcd')->nullable();
            $table->string('orientacao_sexual')->nullable();
            $table->string('orientacao_sexual_outra')->nullable();
            $table->boolean('vinculado')->default(false);
            $table->timestamps();
        });
    }
};
