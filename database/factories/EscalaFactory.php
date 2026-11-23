<?php

namespace Database\Factories;

use App\Models\Escala;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Escala>
 */
class EscalaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'descricao' => fake()->unique()->sentence(3),
            'opcao1' => 'Concordo totalmente',
            'opcao2' => 'Concordo',
            'opcao3' => 'Neutro',
            'opcao4' => 'Discordo',
            'opcao5' => 'Discordo totalmente',
        ];
    }
}
