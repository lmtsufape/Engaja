<?php

namespace Database\Factories\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartaMensagem>
 */
class CartaMensagemFactory extends Factory
{
    protected $model = CartaMensagem::class;

    public function definition(): array
    {
        return [
            'carta_id' => Carta::factory(),
            'rodada' => 1,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
            'canal_entrada' => CartaMensagem::CANAL_ANEXO_DIGITALIZADO,
            'status' => CartaMensagem::STATUS_APROVADA,
            'enviada_em' => now(),
        ];
    }

    public function doEducando(): static
    {
        return $this->state(['tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO]);
    }

    public function doVoluntario(): static
    {
        return $this->state(['tipo_remetente' => CartaMensagem::TIPO_REMETENTE_VOLUNTARIO]);
    }

    public function aguardandoVerificacao(): static
    {
        return $this->state(['status' => CartaMensagem::STATUS_AGUARDANDO_VERIFICACAO]);
    }

    public function ajusteSolicitado(): static
    {
        return $this->state(['status' => CartaMensagem::STATUS_AJUSTE_SOLICITADO]);
    }
}
