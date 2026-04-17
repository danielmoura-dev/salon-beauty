<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Console\Command;

class SeedExpenseCategories extends Command
{
    protected $signature   = 'seed:expense-categories';
    protected $description = 'Adiciona categorias de despesa padrão para tenants que ainda não as têm';

    public function handle(): void
    {
        $names = [
            'Aluguel / Contas fixas',
            'Compras de produtos',
            'Contabilidade / Impostos',
            'Manutenção / Limpeza',
            'Salários / Funcionários',
        ];

        $tenants = Tenant::all();
        $count   = 0;

        foreach ($tenants as $tenant) {
            foreach ($names as $name) {
                $exists = Category::where('tenant_id', $tenant->id)
                    ->where('type', 'expense')
                    ->where('name', $name)
                    ->exists();

                if (! $exists) {
                    Category::create([
                        'tenant_id' => $tenant->id,
                        'type'      => 'expense',
                        'name'      => $name,
                    ]);
                    $count++;
                }
            }
        }

        $this->info("Criadas {$count} categorias de despesa.");
    }
}
