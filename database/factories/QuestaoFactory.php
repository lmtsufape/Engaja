<?php

namespace Database\Factories;

use App\Models\Escala;
use App\Models\Evidencia;
use App\Models\Indicador;
use App\Models\Questao;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Questao>
 */
class QuestaoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'indicador_id' => Indicador::factory(),
            'evidencia_id' => Evidencia::factory(),
            'escala_id' => Escala::factory(),
            'texto' => fake()->unique()->sentence(6),
            'tipo' => 'escala',
            'fixa' => false,
        ];
    }
}
