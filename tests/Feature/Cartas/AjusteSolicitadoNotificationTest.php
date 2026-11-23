<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use App\Models\User;
use App\Notifications\Cartas\AjusteSolicitadoNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AjusteSolicitadoNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_mail_renders_correct_subject_and_content(): void
    {
        $voluntario = User::factory()->create(['name' => 'Ana Voluntária']);

        $carta = Carta::factory()->create([
            'voluntario_user_id' => $voluntario->id,
        ]);

        $mensagem = CartaMensagem::factory()->create([
            'carta_id' => $carta->id,
            'parecer_verificacao' => 'Por favor, ajuste o final da carta.',
        ]);

        $notification = new AjusteSolicitadoNotification($mensagem);
        $mail = $notification->toMail($voluntario);
        $html = $mail->render();

        $this->assertEquals('Cartas para Esperançar - Precisamos de um ajuste no seu envio.', $mail->subject);
        $this->assertStringContainsString('Precisamos de um ajuste no seu envio', $html);
        $this->assertStringContainsString('Recebemos seu envio no', $html);
        $this->assertStringContainsString('Motivo do ajuste', $html);
        $this->assertStringContainsString('Por favor, ajuste o final da carta.', $html);
        $this->assertStringContainsString('O que fazer agora:', $html);
        $this->assertStringContainsString('FAZER O AJUSTE', $html);
        $this->assertStringContainsString('Antes de enviar, confira:', $html);
        $this->assertStringContainsString('Projeto ALFA-EJA Brasil | Cartas para Esperançar', $html);
    }
}
