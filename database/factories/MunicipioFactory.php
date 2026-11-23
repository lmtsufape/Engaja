<?php

namespace Database\Factories;

use App\Models\Estado;
use App\Models\Municipio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Municipio>
 */
class MunicipioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'estado_id' => Estado::factory(),
            'nome' => fake()->unique()->city(),
        ];
    }
}
