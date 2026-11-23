<?php

namespace Database\Factories\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Participante;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Carta>
 */
class CartaFactory extends Factory
{
    protected $model = Carta::class;

    public function definition(): array
    {
        return [
            'codigo' => str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 3, '0', STR_PAD_LEFT),
            'educando_participante_id' => Participante::factory(),
            'voluntario_user_id' => User::factory()->state(['sistema_origem' => User::SISTEMA_CARTAS]),
            'status' => Carta::STATUS_AGUARDANDO_VOLUNTARIO,
            'distribuida_em' => now(),
        ];
    }
}
