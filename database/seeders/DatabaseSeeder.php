<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name'  => 'Admin Engaja',
            'email' => 'admin@engaja.local',
            
        ]);

        $this->call([
            EixoSeeder::class,
        ]);
    }
}
