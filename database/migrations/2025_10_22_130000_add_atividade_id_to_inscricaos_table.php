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
        Schema::table('inscricaos', function (Blueprint $table) {
            if (!Schema::hasColumn('inscricaos', 'atividade_id')) {
                $table->foreignId('atividade_id')
                    ->nullable()
                    ->after('evento_id')
                    ->constrained()
                    ->cascadeOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inscricaos', function (Blueprint $table) {
            if (Schema::hasColumn('inscricaos', 'atividade_id')) {
                $table->dropForeign(['atividade_id']);
                $table->dropColumn('atividade_id');
            }
        });
    }
};

