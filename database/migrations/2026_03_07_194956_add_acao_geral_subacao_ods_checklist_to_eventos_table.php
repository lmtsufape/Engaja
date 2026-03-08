<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->string('acao_geral')->nullable()->after('nome');
            $table->string('subacao')->nullable()->after('acao_geral');
            $table->jsonb('ods_selecionados')->nullable()->after('subacao');
            $table->jsonb('checklist_planejamento')->nullable()->after('ods_selecionados');
            $table->unsignedBigInteger('eixo_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('eventos', function (Blueprint $table) {
            $table->dropColumn(['acao_geral', 'subacao', 'ods_selecionados', 'checklist_planejamento']);
            $table->unsignedBigInteger('eixo_id')->nullable(false)->change();
        });
    }
};