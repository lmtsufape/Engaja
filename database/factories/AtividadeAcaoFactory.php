<?php

namespace Database\Factories;

use App\Models\AtividadeAcao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AtividadeAcao>
 */
class AtividadeAcaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => fake()->sentence(3),
            'detalhe' => fake()->paragraph(),
            'usa_turmas' => false,
            'turmas' => [],
        ];
    }
}
