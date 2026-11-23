<?php

namespace Database\Factories;

use App\Models\Estado;
use App\Models\Regiao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Estado>
 */
class EstadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'regiao_id' => Regiao::factory(),
            'nome' => fake()->unique()->city(),
            'sigla' => fake()->unique()->lexify('??'),
        ];
    }
}
