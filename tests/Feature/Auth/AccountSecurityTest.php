<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class AccountSecurityTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    // ---------- login ----------

    public function test_login_is_locked_after_repeated_failures_even_with_the_right_password(): void
    {
        $this->makeUser($this->makeTenant(), ['email' => 'dono@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'dono@example.com', 'password' => 'errada']);
        }

        $this->post('/login', ['email' => 'dono@example.com', 'password' => 'password123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_failed_logins_for_one_email_do_not_lock_another(): void
    {
        $this->makeUser($this->makeTenant(), ['email' => 'a@example.com']);
        $this->makeUser($this->makeTenant(), ['email' => 'b@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'a@example.com', 'password' => 'errada']);
        }

        $this->post('/login', ['email' => 'b@example.com', 'password' => 'password123'])->assertRedirect();
        $this->assertAuthenticated();
    }

    // ---------- verificação de e-mail ----------

    public function test_email_verification_code_cannot_be_brute_forced(): void
    {
        $user = $this->makeUser($this->makeTenant(), ['email_verified_at' => null]);
        $user->forceFill([
            'email_verification_code'            => '123456',
            'email_verification_code_expires_at' => now()->addHour(),
        ])->save();
        $this->actingAs($user);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/email/verify', ['code' => '00000' . $i]);
        }

        // 6ª tentativa: mesmo com o código certo, fica bloqueada
        $this->post('/email/verify', ['code' => '123456'])->assertStatus(429);
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_correct_email_verification_code_works(): void
    {
        $user = $this->makeUser($this->makeTenant(), ['email_verified_at' => null]);
        $user->forceFill([
            'email_verification_code'            => '123456',
            'email_verification_code_expires_at' => now()->addHour(),
        ])->save();

        $this->actingAs($user)->post('/email/verify', ['code' => '123456'])->assertRedirect(route('onboarding'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    // ---------- recuperação de senha ----------

    private function issueResetToken(string $email, $createdAt = null): string
    {
        $token = 'tok-' . str_repeat('a', 60);
        DB::table('password_reset_tokens')->insert([
            'email' => $email, 'token' => Hash::make($token), 'created_at' => $createdAt ?? now(),
        ]);

        return $token;
    }

    public function test_reset_token_works_once_and_updates_password(): void
    {
        $user  = $this->makeUser($this->makeTenant(), ['email' => 'dono@example.com']);
        $token = $this->issueResetToken('dono@example.com');

        $this->post('/password/reset', [
            'email' => 'dono@example.com', 'token' => $token,
            'password' => 'novasenha123', 'password_confirmation' => 'novasenha123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('novasenha123', $user->fresh()->password));

        // token é de uso único
        $this->post('/password/reset', [
            'email' => 'dono@example.com', 'token' => $token,
            'password' => 'outrasenha123', 'password_confirmation' => 'outrasenha123',
        ])->assertSessionHasErrors('token');
    }

    public function test_expired_reset_token_is_rejected(): void
    {
        $user  = $this->makeUser($this->makeTenant(), ['email' => 'dono@example.com']);
        $token = $this->issueResetToken('dono@example.com', now()->subHours(3));

        $this->post('/password/reset', [
            'email' => 'dono@example.com', 'token' => $token,
            'password' => 'novasenha123', 'password_confirmation' => 'novasenha123',
        ])->assertSessionHasErrors('token');

        $this->assertTrue(Hash::check('password123', $user->fresh()->password), 'senha não pode mudar com token expirado');
    }

    public function test_reset_does_not_reveal_whether_an_email_is_registered(): void
    {
        $payload = ['token' => 'x', 'password' => 'novasenha123', 'password_confirmation' => 'novasenha123'];

        $this->post('/password/reset', ['email' => 'ninguem@example.com'] + $payload)
            ->assertSessionHasErrors('token')
            ->assertSessionDoesntHaveErrors('email');

        $this->makeUser($this->makeTenant(), ['email' => 'dono@example.com']);

        $this->post('/password/reset', ['email' => 'dono@example.com'] + $payload)
            ->assertSessionHasErrors('token')
            ->assertSessionDoesntHaveErrors('email');
    }

    public function test_password_reset_ends_other_sessions_of_that_user(): void
    {
        $user = $this->makeUser($this->makeTenant(), ['email' => 'dono@example.com']);
        DB::table('sessions')->insert([
            'id' => 'sess-1', 'user_id' => $user->id, 'ip_address' => '1.1.1.1',
            'user_agent' => 'x', 'payload' => 'x', 'last_activity' => time(),
        ]);
        $token = $this->issueResetToken('dono@example.com');

        $this->post('/password/reset', [
            'email' => 'dono@example.com', 'token' => $token,
            'password' => 'novasenha123', 'password_confirmation' => 'novasenha123',
        ])->assertRedirect(route('login'));

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
    }

    public function test_forgot_password_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/password/forgot', ['email' => 'alguem@example.com'])->assertSessionHas('success');
        }

        $this->post('/password/forgot', ['email' => 'alguem@example.com'])->assertStatus(429);
    }

    // ---------- Google ----------

    private function fakeGoogle(string $id, string $email, bool $verified = true): void
    {
        $user = (new SocialiteUser())->map(['id' => $id, 'name' => 'Vitima Silva', 'email' => $email, 'avatar' => null]);
        $user->setRaw(['email_verified' => $verified]);

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($user);
    }

    public function test_google_signup_creates_tenant_and_logs_in(): void
    {
        $this->fakeGoogle('g-1', 'nova@example.com');

        $this->get('/auth/google/callback')->assertRedirect(route('onboarding'));

        $user = User::where('email', 'nova@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('g-1', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull(Tenant::find($user->tenant_id));
    }

    public function test_google_login_does_not_let_a_pre_registered_password_survive(): void
    {
        // atacante cadastrou o e-mail da vítima com uma senha que ele conhece, sem nunca confirmar o e-mail
        $attacker = $this->makeUser($this->makeTenant(), [
            'email' => 'vitima@example.com', 'email_verified_at' => null, 'password' => 'senha-do-atacante',
        ]);

        // a vítima (dona real do e-mail) entra com o Google
        $this->fakeGoogle('g-victim', 'vitima@example.com');
        $this->get('/auth/google/callback')->assertRedirect();

        $user = $attacker->fresh();
        $this->assertSame('g-victim', $user->google_id);
        $this->assertFalse(Hash::check('senha-do-atacante', $user->password), 'a senha do atacante precisa ser invalidada');
    }

    public function test_google_login_is_refused_when_google_says_the_email_is_not_verified(): void
    {
        $existing = $this->makeUser($this->makeTenant(), ['email' => 'dono@example.com']);
        $this->fakeGoogle('g-evil', 'dono@example.com', verified: false);

        $this->get('/auth/google/callback')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertNull($existing->fresh()->google_id);
    }

    public function test_verified_existing_account_links_to_google_and_keeps_its_password(): void
    {
        $existing = $this->makeUser($this->makeTenant(), ['email' => 'dono@example.com']);
        $this->fakeGoogle('g-owner', 'dono@example.com');

        $this->get('/auth/google/callback')->assertRedirect();

        $this->assertAuthenticatedAs($existing);
        $this->assertSame('g-owner', $existing->fresh()->google_id);
        $this->assertTrue(Hash::check('password123', $existing->fresh()->password));
    }
}
