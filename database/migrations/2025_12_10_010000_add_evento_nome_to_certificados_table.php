<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('certificados', function (Blueprint $table) {
            $table->string('evento_nome')->nullable()->after('participante_id');
        });
    }

    public function down(): void
    {
        Schema::table('certificados', function (Blueprint $table) {
            $table->dropColumn('evento_nome');
        });
    }
};
