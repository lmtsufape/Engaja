<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carta_mensagens', function (Blueprint $table) {
            $table->timestamp('lida_em')->nullable()->after('enviada_em');
            $table->index(['destinatario_user_id', 'lida_em'], 'carta_mensagens_dest_lida_index');
        });
    }

    public function down(): void
    {
        Schema::table('carta_mensagens', function (Blueprint $table) {
            $table->dropIndex('carta_mensagens_dest_lida_index');
            $table->dropColumn('lida_em');
        });
    }
};
