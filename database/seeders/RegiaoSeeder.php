<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegiaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $regioes = [
            ['nome' => 'Norte', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Nordeste I', 'created_at' => $now, 'updated_at' => $now],
            ['nome' => 'Nordeste II', 'created_at' => $now, 'updated_at' => $now],
        ];

        foreach ($regioes as $regiao) {
            DB::table('regiaos')->updateOrInsert(
                ['nome' => $regiao['nome']],
                ['updated_at' => $now, 'deleted_at' => null]
            );
        }
    }
}
