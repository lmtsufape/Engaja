<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sistema_origem')->default('engaja')->after('password');
            $table->timestamp('cartas_terms_accepted_at')->nullable()->after('sistema_origem');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sistema_origem', 'cartas_terms_accepted_at']);
        });
    }
};
