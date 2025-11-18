<?php

namespace Database\Factories;

use App\Models\Evento;
use App\Models\Eixo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class EventoFactory extends Factory
{
    protected $model = Evento::class;

    public function definition()
    {
        $inicio = Carbon::instance($this->faker->dateTimeBetween('-1 year', '+1 year'))->startOfDay();
        $dias = $this->faker->numberBetween(0, 5);
        $fim = (clone $inicio)->addDays($dias);

        return [
            'user_id'    => User::factory(),
            'eixo_id'    => Eixo::inRandomOrder()->value('id'),
            'nome'       => $this->faker->sentence(3),
            'tipo'       => $this->faker->randomElement(['Forma??uo', 'Oficina', 'Reuniuo', 'Live']),
            'data_inicio' => $inicio->format('Y-m-d'),
            'data_fim'    => $fim->format('Y-m-d'),
            'modalidade' => $this->faker->randomElement(['Presencial', 'Online', 'H??brido']),
            'objetivo'   => $this->faker->sentence(8),
            'resumo'     => $this->faker->paragraph(),
        ];
    }
}
