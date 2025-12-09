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
        Schema::create('modelo_certificados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eixo_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('imagem_frente')->nullable();
            $table->string('imagem_verso')->nullable();
            $table->longText('texto_frente')->nullable();
            $table->longText('texto_verso')->nullable();
            $table->json('layout_frente')->nullable();
            $table->json('layout_verso')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('modelo_certificados');
    }
};
