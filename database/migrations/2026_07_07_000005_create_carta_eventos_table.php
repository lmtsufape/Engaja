<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carta_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carta_id')->constrained('cartas')->cascadeOnDelete();
            $table->foreignId('carta_mensagem_id')->nullable()->constrained('carta_mensagens')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipo', 60);
            $table->json('dados_antes')->nullable();
            $table->json('dados_depois')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['carta_id', 'created_at'], 'carta_eventos_carta_created_index');
            $table->index(['user_id', 'created_at'], 'carta_eventos_user_created_index');
            $table->index('tipo', 'carta_eventos_tipo_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carta_eventos');
    }
};
