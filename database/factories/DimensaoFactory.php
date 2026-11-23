<?php

namespace Database\Factories;

use App\Models\Dimensao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Dimensao>
 */
class DimensaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'descricao' => fake()->unique()->sentence(3),
        ];
    }
}
