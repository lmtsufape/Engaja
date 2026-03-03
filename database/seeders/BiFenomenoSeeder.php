<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BiFenomeno;

class BiFenomenoSeeder extends Seeder
{
    public function run(): void
    {
        $fenomenos = [
            ['codigo' => 'ANALFABETISMO'],
            ['codigo' => 'EJA'],
        ];

        foreach ($fenomenos as $fenomeno) {
            BiFenomeno::updateOrCreate(
                ['codigo' => $fenomeno['codigo']],
                $fenomeno
            );
        }
    }
}
