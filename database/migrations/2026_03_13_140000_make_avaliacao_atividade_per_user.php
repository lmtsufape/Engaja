<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avaliacao_atividades', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('atividade_id')
                ->constrained()
                ->nullOnDelete();
        });

        DB::statement('ALTER TABLE avaliacao_atividades DROP CONSTRAINT IF EXISTS avaliacao_atividades_atividade_id_unique');

        Schema::table('avaliacao_atividades', function (Blueprint $table) {
            $table->unique(['atividade_id', 'user_id'], 'avaliacao_atividades_atividade_user_unique');
        });
    }

    public function down(): void
    {
        Schema::table('avaliacao_atividades', function (Blueprint $table) {
            $table->dropUnique('avaliacao_atividades_atividade_user_unique');
            $table->dropConstrainedForeignId('user_id');
            $table->unique('atividade_id');
        });
    }
};