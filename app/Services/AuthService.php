<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Category;
use App\Models\Professional;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class AuthService
{
    public function registerWithTenant(array $data): User
    {
        // Conta, salão, categorias e profissional nascem juntos ou não nascem (senão o e-mail fica preso a uma conta quebrada)
        return DB::transaction(fn () => $this->createTenantAccount($data));
    }

    private function createTenantAccount(array $data): User
    {
        $affiliate = null;
        if (! empty($data['affiliate_code'])) {
            $affiliate = Affiliate::where('code', strtoupper(trim($data['affiliate_code'])))
                ->where('is_active', true)
                ->first();
        }

        $tenant = Tenant::create([
            'name'                                => $data['business_name'],
            'slug'                                => Str::slug($data['business_name']) . '-' . Str::random(6),
            'email'                               => $data['email'],
            'plan_status'                         => 'trial',
            'trial_ends_at'                       => now()->addDays(30),
            'affiliate_id'                        => $affiliate?->id,
            'affiliate_discount_months_remaining' => $affiliate ? 3 : 0,
        ]);

        $user = User::create([
            'tenant_id' => $tenant->id,
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'role'      => 'owner',
        ]);

        $this->seedDefaultCategories($tenant->id);

        Professional::create([
            'tenant_id' => $tenant->id,
            'name'      => $data['name'],
        ]);

        return $user;
    }

    private function seedDefaultCategories(string $tenantId): void
    {
        $serviceCategories = [
            'Cabelo', 'Depilação', 'Estética Facial', 'Estética Corporal',
            'Mãos e Pés', 'Maquiagem', 'Sobrancelhas',
        ];

        $productCategories = [
            'Produtos para Cabelo', 'Produtos para Depilação',
            'Produtos para Estética Facial', 'Produtos para Estética Corporal',
            'Produtos para Mãos e Pés', 'Produtos para Maquiagem', 'Acessórios',
        ];

        foreach ($serviceCategories as $name) {
            Category::create(['tenant_id' => $tenantId, 'type' => 'service', 'name' => $name]);
        }

        foreach ($productCategories as $name) {
            Category::create(['tenant_id' => $tenantId, 'type' => 'product', 'name' => $name]);
        }

        $expenseCategories = [
            'Aluguel / Contas fixas',
            'Compras de produtos',
            'Contabilidade / Impostos',
            'Manutenção / Limpeza',
            'Salários / Funcionários',
        ];

        foreach ($expenseCategories as $name) {
            Category::create(['tenant_id' => $tenantId, 'type' => 'expense', 'name' => $name]);
        }
    }

    /**
     * @throws \DomainException quando o Google não garante que o e-mail é da pessoa
     */
    public function findOrCreateFromGoogle(SocialiteUser $socialUser): User
    {
        $googleId = $socialUser->getId();
        $email    = $socialUser->getEmail();

        $user = User::where('google_id', $googleId)->first();

        if (! $user) {
            // Vincular/criar por e-mail só é seguro se o Google confirma que o e-mail pertence à pessoa
            $raw      = (array) ($socialUser->user ?? []);
            $verified = (bool) ($raw['email_verified'] ?? $raw['verified_email'] ?? false);

            if (! $email || ! $verified) {
                throw new \DomainException('O Google não confirmou o e-mail desta conta.');
            }

            $user = User::where('email', $email)->first();

            if ($user && ! $user->email_verified_at) {
                // Conta criada com senha e nunca confirmada: quem cadastrou pode não ser o dono do e-mail.
                // A senha é descartada para o cadastro pré-existente não virar uma porta dos fundos.
                $user->password = Str::random(64);
            }
        }

        if ($user) {
            $user->forceFill([
                'google_id'         => $googleId,
                'avatar'            => $user->avatar ?? $socialUser->getAvatar(),
                'email_verified_at' => $user->email_verified_at ?? now(),
            ])->save();

            return $user;
        }

        // Novo usuário via Google — cria tenant junto
        $user = $this->registerWithTenant([
            'name'          => $socialUser->getName(),
            'business_name' => $socialUser->getName() . "'s Salão",
            'email'         => $email,
            'password'      => null,
        ]);

        $user->forceFill([
            'google_id'         => $googleId,
            'avatar'            => $socialUser->getAvatar(),
            'email_verified_at' => now(),
        ])->save();

        return $user;
    }
}
