<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Atividade>
 */
class AtividadeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'descricao' => $this->faker->sentence(),
            'dia' => $this->faker->date(),
            'hora_inicio' => $this->faker->time(),
            'hora_fim' => $this->faker->time(),
            'publico_esperado' => $this->faker->numberBetween(5, 200),
            'carga_horaria' => $this->faker->numberBetween(1, 12),
            'presenca_ativa' => $this->faker->boolean(),
        ];
    }
}
