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
        Schema::table('atividades', function (Blueprint $table) {
            $table->unsignedInteger('publico_esperado')->nullable()->after('hora_fim');
            $table->unsignedInteger('carga_horaria')->nullable()->after('publico_esperado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atividades', function (Blueprint $table) {
            $table->dropColumn(['publico_esperado', 'carga_horaria']);
        });
    }
};
