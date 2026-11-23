<?php

namespace Database\Factories;

use App\Models\Participante;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participante>
 */
class ParticipanteFactory extends Factory
{
    protected $model = Participante::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'municipio_id' => null,
            'telefone' => $this->faker->numerify('###########'),
        ];
    }
}
