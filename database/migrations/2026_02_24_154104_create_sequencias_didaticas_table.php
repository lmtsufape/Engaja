<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sequencias_didaticas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')
                  ->constrained('eventos')
                  ->cascadeOnDelete();
            $table->string('periodo');
            $table->longText('descricao');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequencias_didaticas');
    }
};