<?php

namespace Database\Factories;

use App\Models\ModeloCertificado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ModeloCertificado>
 */
class ModeloCertificadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->unique()->sentence(3),
            'descricao' => fake()->sentence(),
        ];
    }
}
