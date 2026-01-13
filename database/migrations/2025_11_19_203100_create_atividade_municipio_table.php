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
        Schema::create('atividade_municipio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atividade_id')->constrained('atividades')->cascadeOnDelete();
            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['atividade_id', 'municipio_id']);
        });

        // Replica dados existentes do campo municipio_id para a nova relação N:N
        $rows = DB::table('atividades')
            ->whereNotNull('municipio_id')
            ->select('id as atividade_id', 'municipio_id')
            ->get();

        if ($rows->isNotEmpty()) {
            $agora = now();
            $payload = $rows->map(fn ($r) => [
                'atividade_id' => $r->atividade_id,
                'municipio_id' => $r->municipio_id,
                'created_at'   => $agora,
                'updated_at'   => $agora,
            ])->toArray();

            DB::table('atividade_municipio')->insertOrIgnore($payload);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('atividade_municipio');
    }
};
