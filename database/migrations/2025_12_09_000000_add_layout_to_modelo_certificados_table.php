<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modelo_certificados', function (Blueprint $table) {
            if (!Schema::hasColumn('modelo_certificados', 'layout_frente')) {
                $table->json('layout_frente')->nullable()->after('texto_verso');
            }
            if (!Schema::hasColumn('modelo_certificados', 'layout_verso')) {
                $table->json('layout_verso')->nullable()->after('layout_frente');
            }
        });
    }

    public function down(): void
    {
        Schema::table('modelo_certificados', function (Blueprint $table) {
            if (Schema::hasColumn('modelo_certificados', 'layout_frente')) {
                $table->dropColumn('layout_frente');
            }
            if (Schema::hasColumn('modelo_certificados', 'layout_verso')) {
                $table->dropColumn('layout_verso');
            }
        });
    }
};

