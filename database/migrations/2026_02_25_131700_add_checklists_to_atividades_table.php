<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atividades', function (Blueprint $table) {
            $table->jsonb('checklist_planejamento')->nullable()->after('presenca_ativa');
            $table->jsonb('checklist_encerramento')->nullable()->after('checklist_planejamento');
        });
    }

    public function down(): void
    {
        Schema::table('atividades', function (Blueprint $table) {
            $table->dropColumn(['checklist_planejamento', 'checklist_encerramento']);
        });
    }
};