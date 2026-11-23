<?php

namespace Tests\Feature\Cartas;

use App\Models\User;
use App\Notifications\Cartas\CadastroRealizadoComSucessoNotification;
use App\Notifications\Cartas\CartasVerifyEmailNotification;
use App\Notifications\CartasResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CartasEmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    private function cartasUser(array $attributes = []): User
    {
        return User::factory()->unverified()->create(array_merge([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'cartas_terms_accepted_at' => now(),
        ], $attributes));
    }

    public function test_verify_email_screen_shows_resend_and_logout_actions(): void
    {
        $user = $this->cartasUser();

        $response = $this->actingAs($user)->get(route('cartas.verification.notice'));

        $response->assertStatus(200);
        $response->assertSee(route('cartas.verification.send'), false);
        $response->assertSee(route('logout'), false);
    }

    public function test_cartas_user_email_can_be_verified_and_redirects_to_cartas_dashboard(): void
    {
        $user = $this->cartasUser();

        $verificationUrl = URL::temporarySignedRoute(
            'cartas.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('cartas.dashboard', ['verified' => 1]));
    }

    public function test_already_verified_cartas_user_resend_request_redirects_to_cartas_dashboard(): void
    {
        $user = $this->cartasUser(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->post(route('cartas.verification.send'));

        $response->assertRedirect(route('cartas.dashboard'));
    }

    public function test_registering_cartas_user_is_sent_cartas_verify_email_notification(): void
    {
        Notification::fake();

        $user = $this->cartasUser();
        $user->sendEmailVerificationNotification();

        Notification::assertSentTo($user, CartasVerifyEmailNotification::class);
    }

    public function test_cartas_verify_email_mail_does_not_mention_engaja(): void
    {
        $user = $this->cartasUser();

        $mail = (new CartasVerifyEmailNotification)->toMail($user);
        $html = $mail->render();

        $this->assertStringNotContainsString('Engaja', $html);
        $this->assertStringNotContainsString('engaja-favicon.png', $html);
    }

    public function test_cartas_reset_password_mail_does_not_mention_engaja(): void
    {
        $user = $this->cartasUser(['email_verified_at' => now()]);

        $token = Password::broker()->createToken($user);

        $mail = (new CartasResetPasswordNotification($token))->toMail($user);
        $html = $mail->render();

        $this->assertStringNotContainsString('Engaja', $html);
        $this->assertStringNotContainsString('engaja-favicon.png', $html);
    }

    public function test_cartas_user_email_verification_triggers_cadastro_realizado_notification(): void
    {
        Notification::fake();

        $user = $this->cartasUser();

        $verificationUrl = URL::temporarySignedRoute(
            'cartas.verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $this->actingAs($user)->get($verificationUrl);

        Notification::assertSentTo($user, CadastroRealizadoComSucessoNotification::class);

        $mail = (new CadastroRealizadoComSucessoNotification)->toMail($user);
        $html = $mail->render();

        $this->assertStringContainsString('Seu cadastro no Cartas para Esperançar está confirmado', $html);
        $this->assertStringNotContainsString('Engaja', $html);
    }
}
