<?php

namespace Database\Factories;

use App\Models\Dimensao;
use App\Models\Indicador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Indicador>
 */
class IndicadorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dimensao_id' => Dimensao::factory(),
            'descricao' => fake()->unique()->sentence(3),
        ];
    }
}
