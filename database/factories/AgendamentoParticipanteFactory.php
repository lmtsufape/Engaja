<?php

namespace Database\Factories;

use App\Models\Agendamento;
use App\Models\AgendamentoParticipante;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AgendamentoParticipante>
 */
class AgendamentoParticipanteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agendamento_id' => Agendamento::factory(),
            'nome' => fake()->name(),
            'cpf' => fake()->numerify('###########'),
            'email' => fake()->safeEmail(),
            'data_nascimento' => fake()->date(),
            'telefone' => fake()->numerify('###########'),
            'sexo' => fake()->randomElement(['M', 'F']),
            'vinculo' => fake()->word(),
            'turma' => fake()->word(),
            'origem' => 'manual',
        ];
    }
}
