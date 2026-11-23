<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carta_mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carta_id')->constrained('cartas')->cascadeOnDelete();
            $table->unsignedInteger('rodada');
            $table->foreignId('remetente_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('remetente_participante_id')->nullable()->constrained('participantes')->nullOnDelete();
            $table->foreignId('destinatario_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('destinatario_participante_id')->nullable()->constrained('participantes')->nullOnDelete();
            $table->string('tipo_remetente', 30);
            $table->string('canal_entrada', 40)->default('anexo_digitalizado');
            $table->string('status', 40)->default('rascunho');
            $table->longText('texto')->nullable();
            $table->text('texto_resumo')->nullable();
            $table->string('anexo_original_path')->nullable();
            $table->string('anexo_original_nome')->nullable();
            $table->string('anexo_original_mime', 120)->nullable();
            $table->unsignedBigInteger('anexo_original_tamanho')->nullable();
            $table->string('arquivo_final_path')->nullable();
            $table->string('arquivo_final_nome')->nullable();
            $table->string('arquivo_final_mime', 120)->nullable();
            $table->unsignedBigInteger('arquivo_final_tamanho')->nullable();
            $table->timestamp('timbrado_aplicado_em')->nullable();
            $table->timestamp('enviada_em')->nullable();
            $table->foreignId('verificada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verificada_em')->nullable();
            $table->text('parecer_verificacao')->nullable();
            $table->foreignId('criada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('atualizada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['carta_id', 'rodada'], 'carta_mensagens_carta_rodada_unique');
            $table->index(['carta_id', 'status'], 'carta_mensagens_carta_status_index');
            $table->index('remetente_user_id', 'carta_mensagens_rem_user_index');
            $table->index('destinatario_user_id', 'carta_mensagens_dest_user_index');
            $table->index(['status', 'verificada_em'], 'carta_mensagens_verificacao_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carta_mensagens');
    }
};
