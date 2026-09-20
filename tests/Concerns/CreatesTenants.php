<?php

namespace Tests\Concerns;

use App\Models\Client;
use App\Models\Product;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Helpers para montar salões (tenants) isolados nos testes.
 * Usa forceFill para não depender do $fillable nem do usuário autenticado.
 */
trait CreatesTenants
{
    protected function makeTenant(array $attrs = []): Tenant
    {
        $name = $attrs['name'] ?? 'Salão ' . Str::random(5);

        $tenant = new Tenant();
        $tenant->forceFill([
            'name'                 => $name,
            'slug'                 => Str::slug($name) . '-' . Str::random(6),
            'email'                => Str::random(8) . '@salao.test',
            'plan_status'          => 'active',
            'booking_slug'         => Str::slug($name) . '-' . Str::random(4),
            'booking_active'       => true,
            'booking_interval_min' => 30,
            'agenda_start_hour'    => 8,
            'agenda_end_hour'      => 18,
            ...$attrs,
        ])->save();

        return $tenant;
    }

    protected function makeUser(Tenant $tenant, array $attrs = []): User
    {
        $user = new User();
        $user->forceFill([
            'tenant_id'            => $tenant->id,
            'name'                 => 'Dono ' . Str::random(4),
            'email'                => Str::random(8) . '@user.test',
            'password'             => 'password123',
            'role'                 => 'owner',
            'email_verified_at'    => now(),
            'onboarding_completed' => true,
            ...$attrs,
        ])->save();

        return $user;
    }

    /** Cria um salão com dono e já autentica como ele. */
    protected function signInTenant(array $tenantAttrs = []): array
    {
        $tenant = $this->makeTenant($tenantAttrs);
        $user   = $this->makeUser($tenant);
        $this->actingAs($user);

        return [$tenant, $user];
    }

    protected function makeFor(string $model, Tenant $tenant, array $attrs): Model
    {
        /** @var Model $m */
        $m = new $model();
        $m->forceFill(['tenant_id' => $tenant->id, ...$attrs])->save();

        return $m;
    }

    protected function makeClient(Tenant $tenant, array $attrs = []): Client
    {
        return $this->makeFor(Client::class, $tenant, [
            'name'  => 'Cliente ' . Str::random(4),
            'phone' => '119' . random_int(10000000, 99999999),
            ...$attrs,
        ]);
    }

    protected function makeProfessional(Tenant $tenant, array $attrs = []): Professional
    {
        return $this->makeFor(Professional::class, $tenant, [
            'name'            => 'Profissional ' . Str::random(4),
            'show_on_booking' => true,
            ...$attrs,
        ]);
    }

    protected function makeService(Tenant $tenant, array $attrs = []): Service
    {
        return $this->makeFor(Service::class, $tenant, [
            'name'         => 'Serviço ' . Str::random(4),
            'price'        => 100,
            'duration_min' => 60,
            'active'       => true,
            ...$attrs,
        ]);
    }

    protected function makeProduct(Tenant $tenant, array $attrs = []): Product
    {
        return $this->makeFor(Product::class, $tenant, [
            'name'      => 'Produto ' . Str::random(4),
            'for_sale'  => true,
            'price'     => 50,
            'active'    => true,
            ...$attrs,
        ]);
    }
}
