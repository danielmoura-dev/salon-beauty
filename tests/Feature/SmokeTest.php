<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_root_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_health_endpoint_is_up(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_unknown_public_booking_slug_is_404(): void
    {
        $this->get('/agendar/nao-existe')->assertNotFound();
    }

    public function test_app_routes_require_authentication(): void
    {
        foreach (['/dashboard', '/agenda', '/orders', '/clients', '/reports'] as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }
}
