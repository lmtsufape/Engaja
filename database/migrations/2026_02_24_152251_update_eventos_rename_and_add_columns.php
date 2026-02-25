<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->renameColumn('objetivo', 'objetivos_gerais');
            $table->renameColumn('resumo', 'objetivos_especificos');
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->longText('recursos_materiais_necessarios')->nullable()->after('objetivos_especificos');
            $table->longText('providencias_sme_parceria')->nullable()->after('recursos_materiais_necessarios');
            $table->longText('observacoes_complementares')->nullable()->after('providencias_sme_parceria');
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn([
                'recursos_materiais_necessarios',
                'providencias_sme_parceria',
                'observacoes_complementares',
            ]);
        });

        Schema::table('eventos', function (Blueprint $table) {
            $table->renameColumn('objetivos_gerais', 'objetivo');
            $table->renameColumn('objetivos_especificos', 'resumo');
        });
    }
};