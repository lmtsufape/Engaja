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
        Schema::table('users', function (Blueprint $table) {
            $table->smallInteger('cartas_limite_respostas')->default(1)->after('cartas_welcome_seen_at');
            $table->string('cartas_tipo_vinculo')->nullable()->after('cartas_limite_respostas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['cartas_limite_respostas', 'cartas_tipo_vinculo']);
        });
    }
};
