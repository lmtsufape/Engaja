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
        Schema::create('certificado_evento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('certificado_id')->constrained('certificados')->cascadeOnDelete();
            $table->foreignId('evento_id')->constrained('eventos')->cascadeOnDelete();
            $table->timestamps();
        });

        //migrar dados existentes para a tabela nova
        $certificados = \Illuminate\Support\Facades\DB::table('certificados')->whereNotNull('evento_id')->get();
        foreach ($certificados as $certificado) {
            \Illuminate\Support\Facades\DB::table('certificado_evento')->insert([
                'certificado_id' => $certificado->id,
                'evento_id' => $certificado->evento_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        //remover a coluna antiga
        Schema::table('certificados', function (Blueprint $table) {
            $table->dropForeign(['evento_id']);
            $table->dropColumn('evento_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('certificados', function (Blueprint $table) {
            $table->foreignId('evento_id')->nullable()->constrained('eventos');
        });

        $relacionamentos = \Illuminate\Support\Facades\DB::table('certificado_evento')->get();
        foreach ($relacionamentos as $relacao) {
            \Illuminate\Support\Facades\DB::table('certificados')
                ->where('id', $relacao->certificado_id)
                ->update(['evento_id' => $relacao->evento_id]);
        }

        Schema::dropIfExists('certificado_evento');
    }
};
