<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('origem_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('origem');
            $table->timestamps();

            $table->unique(['evento_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('origem_usuario');
    }
};
