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
            if (!Schema::hasColumn('atividades', 'municipio_id')) {
                $table->foreignId('municipio_id')
                    ->nullable()
                    ->after('evento_id')
                    ->constrained('municipios')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('atividades', function (Blueprint $table) {
            if (Schema::hasColumn('atividades', 'municipio_id')) {
                $table->dropForeign(['municipio_id']);
                $table->dropColumn('municipio_id');
            }
        });
    }
};

