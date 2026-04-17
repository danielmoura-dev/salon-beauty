<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Professional;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class AuthService
{
    public function registerWithTenant(array $data): User
    {
        $tenant = Tenant::create([
            'name'          => $data['business_name'],
            'slug'          => Str::slug($data['business_name']) . '-' . Str::random(6),
            'email'         => $data['email'],
            'plan_status'   => 'trial',
            'trial_ends_at' => now()->addDays(14),
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
            'Compras de produtos',
            'Contabilidade / Impostos',
            'Manutenção / Limpeza',
        ];

        foreach ($expenseCategories as $name) {
            Category::create(['tenant_id' => $tenantId, 'type' => 'expense', 'name' => $name]);
        }
    }

    public function findOrCreateFromGoogle(SocialiteUser $socialUser): User
    {
        // Tenta encontrar usuário existente pelo google_id ou email
        $user = User::where('google_id', $socialUser->getId())
                    ->orWhere('email', $socialUser->getEmail())
                    ->first();

        if ($user) {
            $user->update([
                'google_id' => $socialUser->getId(),
                'avatar'    => $user->avatar ?? $socialUser->getAvatar(),
            ]);
            return $user;
        }

        // Novo usuário via Google — cria tenant junto
        return $this->registerWithTenant([
            'name'          => $socialUser->getName(),
            'business_name' => $socialUser->getName() . "'s Salão",
            'email'         => $socialUser->getEmail(),
            'password'      => null,
        ]);
    }
}