<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissao_avaliacoes', function (Blueprint $table) {
            $table->foreignId('presenca_id')->nullable()->after('avaliacao_id')->constrained('presencas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submissao_avaliacoes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('presenca_id');
        });
    }
};
