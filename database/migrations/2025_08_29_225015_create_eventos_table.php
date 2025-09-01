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
        Schema::create('eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('eixo_id')->constrained()->onDelete('cascade');
            $table->string('nome');
            $table->string('tipo')->nullable();
            $table->dateTime('data_horario')->nullable();
            $table->integer('duracao')->nullable();
            $table->string('modalidade')->nullable();
            $table->string('link')->nullable();
            $table->text('objetivo')->nullable();
            $table->text('resumo')->nullable();
            $table->string('imagem')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('eventos');
    }
};
