<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matrizes_aprendizagem', function (Blueprint $table) {
            $table->id();
            $table->string('nome'); 
            $table->text('descricao'); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrizes_aprendizagem');
    }
};