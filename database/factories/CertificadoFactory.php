<?php

namespace Database\Factories;

use App\Models\Certificado;
use App\Models\ModeloCertificado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Certificado>
 */
class CertificadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'modelo_certificado_id' => ModeloCertificado::factory(),
            'evento_nome' => fake()->sentence(3),
            'codigo_validacao' => fake()->uuid(),
            'ano' => now()->year,
            'texto_frente' => fake()->paragraph(),
            'carga_horaria' => fake()->numberBetween(60, 480),
        ];
    }
}
