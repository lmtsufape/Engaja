<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atividades', function (Blueprint $table) {
            // Armazenados em UTC (timezone da aplicação). A interface converte de/para America/Sao_Paulo.
            $table->timestamp('presenca_abre_em')->nullable()->after('presenca_ativa');
            $table->timestamp('presenca_fecha_em')->nullable()->after('presenca_abre_em');
        });
    }

    public function down(): void
    {
        Schema::table('atividades', function (Blueprint $table) {
            $table->dropColumn(['presenca_abre_em', 'presenca_fecha_em']);
        });
    }
};
