<?php

namespace Tests\Feature\Auth;

use App\Models\Category;
use App\Models\Professional;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private function payload(array $over = []): array
    {
        return [
            'name'                  => 'Ana Souza',
            'business_name'         => 'Studio Ana',
            'email'                 => 'ana@example.com',
            'password'              => 'segredo123',
            'password_confirmation' => 'segredo123',
            ...$over,
        ];
    }

    public function test_register_creates_tenant_owner_professional_and_default_categories(): void
    {
        $this->post('/register', $this->payload())->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'ana@example.com')->firstOrFail();
        $this->assertSame('owner', $user->role);
        $this->assertNull($user->email_verified_at);

        $tenant = Tenant::findOrFail($user->tenant_id);
        $this->assertSame('trial', $tenant->plan_status);
        $this->assertTrue($tenant->trial_ends_at->isFuture());

        $this->assertSame(1, Professional::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertGreaterThan(0, Category::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }

    public function test_register_rejects_duplicate_email_and_weak_password(): void
    {
        $this->makeUser($this->makeTenant(), ['email' => 'ana@example.com']);

        $this->post('/register', $this->payload())->assertSessionHasErrors('email');
        $this->post('/register', $this->payload(['email' => 'novo@example.com', 'password' => '123', 'password_confirmation' => '123']))
            ->assertSessionHasErrors('password');
    }

    public function test_unverified_user_cannot_reach_the_app(): void
    {
        $user = $this->makeUser($this->makeTenant(), ['email_verified_at' => null]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_login_with_valid_and_invalid_credentials(): void
    {
        $user = $this->makeUser($this->makeTenant(), ['email' => 'dono@example.com']);

        $this->post('/login', ['email' => 'dono@example.com', 'password' => 'errada'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/login', ['email' => 'dono@example.com', 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_trial_blocks_writes_but_allows_reads(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'trial', 'trial_ends_at' => now()->subDay()]);
        $this->actingAs($this->makeUser($tenant));

        $this->get('/clients')->assertOk();
        $this->post('/clients', ['name' => 'Fulana'])->assertRedirect();
        $this->assertSame(0, \App\Models\Client::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }
}
