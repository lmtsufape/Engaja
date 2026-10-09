<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cartas', function (Blueprint $table) {
            $table->uuid('envio_token')->nullable();
            $table->char('envio_hash', 64)->nullable();
            $table->unique(['criada_por', 'envio_token'], 'cartas_criador_envio_unique');
        });
    }

    public function down(): void
    {
        Schema::table('cartas', function (Blueprint $table) {
            $table->dropUnique('cartas_criador_envio_unique');
            $table->dropColumn(['envio_token', 'envio_hash']);
        });
    }
};
