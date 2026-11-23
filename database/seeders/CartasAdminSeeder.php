<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CartasAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Criar todas as roles Cartas (infraestrutura)
        Role::firstOrCreate(['name' => 'cartas_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cartas_gestao', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cartas_voluntario', 'guard_name' => 'web']);

        $user = User::withTrashed()
            ->where('email', 'admin.cartas@example.com')
            ->where('sistema_origem', User::SISTEMA_CARTAS)
            ->first();

        if (! $user) {
            $user = new User([
                'email' => 'admin.cartas@example.com',
                'sistema_origem' => User::SISTEMA_CARTAS,
            ]);
        }

        if ($user->trashed()) {
            $user->restore();
        }

        $user->name = 'Administrador Cartas';
        $user->password = Hash::make('password');
        $user->email_verified_at = now();
        $user->cartas_terms_accepted_at = now();
        $user->save();

        $user->syncRoles(['cartas_admin']);
    }
}
