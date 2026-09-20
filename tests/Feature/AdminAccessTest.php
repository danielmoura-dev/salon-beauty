<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_guest_is_sent_to_login_for_admin_pages(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/affiliates')->assertRedirect(route('login'));
    }

    public function test_regular_salon_owner_cannot_open_admin_pages(): void
    {
        [$tenant] = $this->signInTenant();

        $this->get('/admin')->assertRedirect(route('dashboard'));
        $this->get('/admin/affiliates')->assertRedirect(route('dashboard'));
        $this->post('/admin/affiliates', ['name' => 'X', 'email' => 'x@x.com', 'code' => 'HACK'])->assertRedirect(route('dashboard'));
        $this->patch("/admin/tenants/{$tenant->id}/trial", ['days' => 999])->assertRedirect(route('dashboard'));
    }

    public function test_is_admin_flag_cannot_be_set_through_mass_assignment(): void
    {
        $user = $this->makeUser($this->makeTenant());
        $user->update(['is_admin' => true]);

        $this->assertFalse((bool) $user->fresh()->is_admin);
    }

    public function test_admin_can_open_admin_pages(): void
    {
        $tenant = $this->makeTenant();
        $admin  = $this->makeUser($tenant);
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->get('/admin/affiliates')->assertOk();
    }

    public function test_logout_ends_the_session(): void
    {
        $this->signInTenant();

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_expired_subscription_page_renders(): void
    {
        $this->signInTenant();

        $this->get('/subscription/expired')->assertOk();
    }
}
