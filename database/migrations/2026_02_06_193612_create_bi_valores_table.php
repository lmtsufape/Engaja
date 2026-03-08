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
        Schema::create('bi_valores', function (Blueprint $table) {
            $table->id();

            $table->year('ano');
            $table->decimal('valor', 14, 4);

            $table->foreignId('indicador_id')->constrained('bi_indicadores')->cascadeOnDelete();
            $table->foreignId('municipio_id')->constrained('municipios');
            $table->foreignId('dimensao_valor_id')->nullable()->constrained('bi_dimensao_valores');

            $table->timestamps();

            $table->unique(
                ['indicador_id', 'municipio_id', 'ano', 'dimensao_valor_id'],
                'bi_valores_unq'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bi_valores');
    }
};
