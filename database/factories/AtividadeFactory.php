<?php

namespace Database\Factories;

use App\Models\Atividade;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Atividade>
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
            'carga_horaria' => $this->faker->numberBetween(1, 12) * 60,
            'presenca_ativa' => $this->faker->boolean(),
            'presenca_abre_em' => null,
            'presenca_fecha_em' => null,
        ];
    }

    public function presencaAberta(): static
    {
        return $this->state(fn () => [
            'presenca_ativa' => true,
            'presenca_abre_em' => null,
            'presenca_fecha_em' => null,
        ]);
    }

    public function presencaFechada(): static
    {
        return $this->state(fn () => [
            'presenca_ativa' => false,
            'presenca_abre_em' => null,
            'presenca_fecha_em' => null,
        ]);
    }

    public function comPresencaAgendada(?\DateTimeInterface $abreEm = null, ?\DateTimeInterface $fechaEm = null): static
    {
        return $this->state(fn () => [
            'presenca_abre_em' => $abreEm,
            'presenca_fecha_em' => $fechaEm,
        ]);
    }
}
