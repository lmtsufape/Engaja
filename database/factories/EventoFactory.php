<?php

namespace Database\Factories;

use App\Models\Evento;
use App\Models\Eixo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventoFactory extends Factory
{
    protected $model = Evento::class;

    public function definition()
    {
        return [
            'user_id'    => User::factory(),
            'eixo_id'    => Eixo::inRandomOrder()->value('id'),
            'nome'       => $this->faker->sentence(3),
            'tipo'       => $this->faker->randomElement(['Formação', 'Oficina', 'Reunião', 'Live']),
            'data_horario' => $this->faker->dateTimeBetween('-1 year', '+1 year'),
            'duracao'    => $this->faker->numberBetween(1, 8),
            'modalidade' => $this->faker->randomElement(['Presencial', 'Online', 'Híbrido']),
            'objetivo'   => $this->faker->sentence(8),
            'resumo'     => $this->faker->paragraph(),
        ];
    }
}
