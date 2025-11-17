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
        Schema::create('template_avaliacaos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->timestamps();
        });

        Schema::table('questaos', function (Blueprint $table) {
            $table->foreign('template_avaliacao_id')
                ->references('id')
                ->on('template_avaliacaos')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questaos', function (Blueprint $table) {
            if (Schema::hasColumn('questaos', 'template_avaliacao_id')) {
                $table->dropForeign(['template_avaliacao_id']);
            }
        });
        Schema::dropIfExists('template_avaliacaos');
    }
};
