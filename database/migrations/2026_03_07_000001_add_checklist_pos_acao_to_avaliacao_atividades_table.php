<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avaliacao_atividades', function (Blueprint $table) {
            $table->jsonb('checklist_pos_acao')->nullable()->after('avaliacao_atuacao_equipe');
        });
    }

    public function down(): void
    {
        Schema::table('avaliacao_atividades', function (Blueprint $table) {
            $table->dropColumn('checklist_pos_acao');
        });
    }
};
