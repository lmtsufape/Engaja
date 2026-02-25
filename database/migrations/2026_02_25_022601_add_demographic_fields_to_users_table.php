<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('identidade_genero')->nullable()->after('email');
            $table->string('identidade_genero_outro')->nullable()->after('identidade_genero');
            $table->string('raca_cor')->nullable()->after('identidade_genero_outro');
            $table->string('comunidade_tradicional')->nullable()->after('raca_cor');
            $table->string('comunidade_tradicional_outro')->nullable()->after('comunidade_tradicional');
            $table->string('faixa_etaria')->nullable()->after('comunidade_tradicional_outro');
            $table->string('pcd')->nullable()->after('faixa_etaria');
            $table->string('orientacao_sexual')->nullable()->after('pcd');
            $table->string('orientacao_sexual_outra')->nullable()->after('orientacao_sexual');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'identidade_genero', 'identidade_genero_outro',
                'raca_cor',
                'comunidade_tradicional', 'comunidade_tradicional_outro',
                'faixa_etaria',
                'pcd',
                'orientacao_sexual', 'orientacao_sexual_outra',
            ]);
        });
    }
};