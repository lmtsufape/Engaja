<?php

namespace Database\Factories;

use App\Models\Agendamento;
use App\Models\AtividadeAcao;
use App\Models\Municipio;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agendamento>
 */
class AgendamentoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'atividade_acao_id' => AtividadeAcao::factory(),
            'municipio_id' => Municipio::factory(),
            'user_id' => User::factory(),
            'data_horario' => fake()->dateTimeBetween('now', '+1 month'),
            'publico_participante' => fake()->sentence(3),
            'local_acao' => fake()->address(),
            'efetivado' => false,
        ];
    }
}
