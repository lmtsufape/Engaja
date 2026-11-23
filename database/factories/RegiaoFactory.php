<?php

namespace Database\Factories;

use App\Models\Regiao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Regiao>
 */
class RegiaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->word(),
        ];
    }
}
