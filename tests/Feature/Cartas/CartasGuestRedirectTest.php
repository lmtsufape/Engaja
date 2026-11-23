<?php

namespace Tests\Feature\Cartas;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartasGuestRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_accessing_cartas_route_is_redirected_to_cartas_login(): void
    {
        $response = $this->get('/cartas/dashboard');

        $response->assertRedirect(route('cartas.login'));
    }

    public function test_guest_accessing_engaja_route_is_redirected_to_engaja_login(): void
    {
        $response = $this->get('/dashboards/presencas');

        $response->assertRedirect(route('login'));
    }
}
