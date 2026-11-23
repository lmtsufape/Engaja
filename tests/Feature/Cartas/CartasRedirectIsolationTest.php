<?php

namespace Tests\Feature\Cartas;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\User;
use App\Notifications\Cartas\CartasVerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Garante que nenhum fluxo do Cartas termine em uma tela do Engaja (e vice-versa).
 */
class CartasRedirectIsolationTest extends TestCase
{
    use RefreshDatabase;

    private function cartasUser(array $attributes = []): User
    {
        return User::factory()->unverified()->create(array_merge([
            'sistema_origem' => User::SISTEMA_CARTAS,
            'cartas_terms_accepted_at' => now(),
        ], $attributes));
    }

    private function signedVerificationUrl(User $user, string $routeName = 'cartas.verification.verify'): string
    {
        return URL::temporarySignedRoute($routeName, now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);
    }

    public function test_cartas_verification_email_links_to_a_cartas_url(): void
    {
        $user = $this->cartasUser();

        $html = (new CartasVerifyEmailNotification)->toMail($user)->render();

        $this->assertStringContainsString(url('/cartas/verificar-email/'.$user->id), $html);
        $this->assertStringNotContainsString(url('/verify-email/'), $html);
    }

    public function test_guest_verifying_cartas_email_lands_on_cartas_login_and_not_engaja_login(): void
    {
        $user = $this->cartasUser();

        $response = $this->get($this->signedVerificationUrl($user));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('cartas.login'));
        $response->assertSessionHas('status');
        $this->assertNotSame(route('login'), $response->headers->get('Location'));
    }

    public function test_guest_verifying_through_the_legacy_engaja_url_still_lands_on_cartas_login(): void
    {
        $user = $this->cartasUser();

        $response = $this->get($this->signedVerificationUrl($user, 'verification.verify'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('cartas.login'));
    }

    public function test_guest_verifying_an_engaja_email_lands_on_engaja_login(): void
    {
        $user = User::factory()->unverified()->create(['sistema_origem' => User::SISTEMA_ENGAJA]);

        $response = $this->get($this->signedVerificationUrl($user, 'verification.verify'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_cartas_user_verifying_lands_on_cartas_dashboard(): void
    {
        $user = $this->cartasUser();

        $response = $this->actingAs($user)->get($this->signedVerificationUrl($user));

        $response->assertRedirect(route('cartas.dashboard', ['verified' => 1]));
    }

    public function test_verification_link_with_wrong_hash_is_rejected(): void
    {
        $user = $this->cartasUser();

        $url = URL::temporarySignedRoute('cartas.verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1('outro@email.test'),
        ]);

        $this->get($url)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_link_without_a_valid_signature_is_rejected(): void
    {
        $user = $this->cartasUser();

        $this->get('/cartas/verificar-email/'.$user->id.'/'.sha1($user->email))
            ->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_guest_on_cartas_route_goes_to_cartas_login_and_guest_on_engaja_route_goes_to_engaja_login(): void
    {
        $this->get('/cartas/dashboard')->assertRedirect(route('cartas.login'));
        $this->get('/dashboards/presencas')->assertRedirect(route('login'));
    }

    public function test_cartas_user_resends_verification_through_the_cartas_route(): void
    {
        $user = $this->cartasUser();

        $response = $this->from(route('cartas.verification.notice'))
            ->actingAs($user)
            ->post(route('cartas.verification.send'));

        $response->assertRedirect(route('cartas.verification.notice'));
        $response->assertSessionHas('status', 'verification-link-sent');
    }

    public function test_engaja_user_cannot_use_the_cartas_resend_route(): void
    {
        $user = User::factory()->create(['sistema_origem' => User::SISTEMA_ENGAJA]);

        $this->actingAs($user)->post(route('cartas.verification.send'))->assertForbidden();
    }

    public function test_cartas_user_cannot_use_the_engaja_resend_route(): void
    {
        $user = $this->cartasUser();

        $this->actingAs($user)->post(route('verification.send'))->assertForbidden();
    }

    public function test_authenticated_cartas_user_hitting_a_guest_route_goes_to_the_cartas_dashboard(): void
    {
        $user = $this->cartasUser(['email_verified_at' => now()]);

        $this->actingAs($user)->get(route('cartas.login'))
            ->assertRedirect(route('cartas.dashboard'));
    }

    /**
     * Cenário exato relatado: cadastro novo no Cartas e clique no link de
     * confirmação sem sessão ativa (e-mail aberto em outro navegador).
     */
    public function test_registration_then_verifying_while_logged_out_never_reaches_the_engaja_login(): void
    {
        Notification::fake();

        $estado = Estado::factory()->create();
        $municipio = Municipio::factory()->create(['estado_id' => $estado->id]);

        $this->post(route('cartas.register.store'), [
            'name' => 'Voluntária Teste',
            'email' => 'voluntaria@example.test',
            'password' => 'Senha!Forte123',
            'password_confirmation' => 'Senha!Forte123',
            'cpf' => '52998224725',
            'telefone' => '11987654321',
            'estado_id' => $estado->id,
            'municipio_id' => $municipio->id,
            'termos_aceitos' => '1',
            'cartas_tipo_vinculo' => \App\Models\User::VINCULO_PETROBRAS,
            'cartas_limite_respostas' => 3,
        ])->assertRedirect(route('cartas.verification.notice'));

        $user = User::where('email', 'voluntaria@example.test')->firstOrFail();

        $url = null;
        Notification::assertSentTo($user, CartasVerifyEmailNotification::class, function ($notification) use ($user, &$url) {
            $url = $notification->toMail($user)->viewData['url'];

            return true;
        });

        $this->assertStringContainsString('/cartas/verificar-email/', $url);

        $this->post(route('logout'));

        $response = $this->get($url);

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $response->assertRedirect(route('cartas.login'));
    }

    public function test_cartas_user_logout_returns_to_the_cartas_login(): void
    {
        $user = $this->cartasUser(['email_verified_at' => now()]);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('cartas.login'));
    }
}
