<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('avaliacao_questoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('avaliacao_id')
                ->constrained('avaliacaos')
                ->cascadeOnDelete();
            $table->foreignId('questao_id')
                ->nullable()
                ->constrained('questaos')
                ->nullOnDelete();
            $table->foreignId('indicador_id')
                ->nullable()
                ->constrained('indicadors')
                ->nullOnDelete();
            $table->foreignId('escala_id')
                ->nullable()
                ->constrained('escalas')
                ->nullOnDelete();
            $table->foreignId('evidencia_id')
                ->nullable()
                ->constrained('evidencias')
                ->nullOnDelete();
            $table->string('texto', 1000);
            $table->string('tipo', 50)->default('texto');
            $table->unsignedInteger('ordem')->nullable();
            $table->boolean('fixa')->default(false);
            $table->timestamps();
        });

        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            $table->unsignedBigInteger('avaliacao_questao_id')->nullable()->after('avaliacao_id');
        });

        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            $table->foreignId('inscricao_id')
                ->nullable()
                ->after('avaliacao_questao_id')
                ->constrained('inscricaos')
                ->nullOnDelete();
        });

        DB::statement('ALTER TABLE avaliacaos ALTER COLUMN inscricao_id DROP NOT NULL');

        $avaliacoes = DB::table('avaliacaos')
            ->select('id', 'template_avaliacao_id')
            ->get();

        foreach ($avaliacoes as $avaliacao) {
            if (! $avaliacao->template_avaliacao_id) {
                continue;
            }

            $questoes = DB::table('questaos')
                ->where('template_avaliacao_id', $avaliacao->template_avaliacao_id)
                ->orderBy('ordem')
                ->orderBy('id')
                ->get();

            foreach ($questoes as $questao) {
                $avaliacaoQuestaoId = DB::table('avaliacao_questoes')->insertGetId([
                    'avaliacao_id' => $avaliacao->id,
                    'questao_id'   => $questao->id,
                    'indicador_id' => $questao->indicador_id,
                    'escala_id'    => $questao->escala_id,
                    'evidencia_id' => $questao->evidencia_id,
                    'texto'        => $questao->texto,
                    'tipo'         => $questao->tipo,
                    'ordem'        => $questao->ordem,
                    'fixa'         => (bool) $questao->fixa,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);

                DB::table('resposta_avaliacaos')
                    ->where('avaliacao_id', $avaliacao->id)
                    ->where('questao_id', $questao->id)
                    ->update(['avaliacao_questao_id' => $avaliacaoQuestaoId]);
            }

            DB::table('resposta_avaliacaos')
                ->where('avaliacao_id', $avaliacao->id)
                ->update(['inscricao_id' => $avaliacao->inscricao_id]);
        }

        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            $table->dropForeign(['questao_id']);
            $table->dropColumn('questao_id');
        });

        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            $table->foreign('avaliacao_questao_id')
                ->references('id')
                ->on('avaliacao_questoes')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            $table->dropForeign(['avaliacao_questao_id']);
            $table->unsignedBigInteger('questao_id')->nullable()->after('avaliacao_id');
        });

        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            $table->dropForeign(['inscricao_id']);
            $table->dropColumn('inscricao_id');
        });

        DB::statement('ALTER TABLE avaliacaos ALTER COLUMN inscricao_id SET NOT NULL');

        $respostas = DB::table('resposta_avaliacaos')
            ->select('id', 'avaliacao_questao_id')
            ->get();

        foreach ($respostas as $resposta) {
            $questaoOriginalId = DB::table('avaliacao_questoes')
                ->where('id', $resposta->avaliacao_questao_id)
                ->value('questao_id');

            DB::table('resposta_avaliacaos')
                ->where('id', $resposta->id)
                ->update(['questao_id' => $questaoOriginalId]);
        }

        Schema::table('resposta_avaliacaos', function (Blueprint $table) {
            $table->dropColumn('avaliacao_questao_id');
            $table->foreign('questao_id')
                ->references('id')
                ->on('questaos')
                ->cascadeOnDelete();
        });

        Schema::dropIfExists('avaliacao_questoes');
    }
};
