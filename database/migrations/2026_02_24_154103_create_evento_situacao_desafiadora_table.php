<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evento_situacao_desafiadora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')
                  ->constrained('eventos')
                  ->cascadeOnDelete();
            $table->foreignId('situacao_desafiadora_id')
                  ->constrained('situacoes_desafiadoras')
                  ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evento_situacao_desafiadora');
    }
};