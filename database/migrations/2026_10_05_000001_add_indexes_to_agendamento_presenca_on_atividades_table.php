<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atividades', function (Blueprint $table) {
            $table->index('presenca_abre_em');
            $table->index('presenca_fecha_em');
        });
    }

    public function down(): void
    {
        Schema::table('atividades', function (Blueprint $table) {
            $table->dropIndex(['presenca_abre_em']);
            $table->dropIndex(['presenca_fecha_em']);
        });
    }
};
