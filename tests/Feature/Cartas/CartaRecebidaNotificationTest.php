<?php

namespace Tests\Feature\Cartas;

use App\Models\Cartas\Carta;
use App\Models\Cartas\CartaMensagem;
use App\Models\User;
use App\Notifications\Cartas\CartaRecebidaNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartaRecebidaNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_mail_when_primeira_vez_true(): void
    {
        $voluntario = User::factory()->create(['name' => 'Maria Voluntária']);
        $educandoUser = User::factory()->create(['name' => 'João Educando']);

        $carta = Carta::factory()->create([
            'voluntario_user_id' => $voluntario->id,
        ]);

        CartaMensagem::factory()->create([
            'carta_id' => $carta->id,
            'rodada' => 1,
            'remetente_user_id' => $educandoUser->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
        ]);

        $notification = new CartaRecebidaNotification($carta);
        $mail = $notification->toMail($voluntario);
        $html = $mail->render();

        $this->assertEquals('Cartas para Esperançar - Chegou uma carta para você!', $mail->subject);
        $this->assertStringContainsString('Uma carta acabou de chegar na plataforma', $html);
        $this->assertStringContainsString('ACESSAR MINHA CARTA', $html);
        $this->assertStringContainsString('Antes de responder, algumas orientações:', $html);
        $this->assertStringContainsString('Projeto ALFA-EJA Brasil | Cartas para Esperançar', $html);
    }

    public function test_to_mail_when_primeira_vez_false(): void
    {
        $voluntario = User::factory()->create(['name' => 'Maria Voluntária']);
        $educandoUser = User::factory()->create(['name' => 'João Educando']);

        $carta = Carta::factory()->create([
            'voluntario_user_id' => $voluntario->id,
        ]);

        CartaMensagem::factory()->create([
            'carta_id' => $carta->id,
            'rodada' => 1,
            'remetente_user_id' => $educandoUser->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
        ]);

        CartaMensagem::factory()->create([
            'carta_id' => $carta->id,
            'rodada' => 2,
            'remetente_user_id' => $educandoUser->id,
            'tipo_remetente' => CartaMensagem::TIPO_REMETENTE_EDUCANDO,
        ]);

        $notification = new CartaRecebidaNotification($carta);
        $mail = $notification->toMail($voluntario);
        $html = $mail->render();

        $this->assertEquals('Cartas para Esperançar - Sua carta foi respondida!', $mail->subject);
        $this->assertStringContainsString('A conversa continua: chegou uma resposta à carta que você enviou', $html);
        $this->assertStringContainsString('LER A RESPOSTA', $html);
        $this->assertStringNotContainsString('Antes de responder, algumas orientações:', $html);
        $this->assertStringContainsString('Projeto ALFA-EJA Brasil | Cartas para Esperançar', $html);
    }
}
