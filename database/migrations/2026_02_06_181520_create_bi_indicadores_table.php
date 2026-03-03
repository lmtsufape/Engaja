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
        Schema::create('bi_indicadores', function (Blueprint $table) {
            $table->id();

            $table->string('codigo')->unique(); // ex: TAXA_ANALFABETISMO
            $table->string('tipo_valor'); // ABSOLUTO | PERCENTUAL | ETC

            $table->foreignId('fenomeno_id')->nullable()->constrained('bi_fenomenos')->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bi_indicadores');
    }
};
