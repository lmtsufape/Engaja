<?php

namespace Database\Factories;

use App\Models\Evidencia;
use App\Models\Indicador;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Evidencia>
 */
class EvidenciaFactory extends Factory
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
            'descricao' => fake()->unique()->sentence(4),
        ];
    }
}
