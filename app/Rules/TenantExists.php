<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Equivalente ao `exists:tabela,coluna`, mas só aceita registros do salão
 * do usuário autenticado. O `exists` puro enxerga todos os salões e deixaria
 * referenciar (e alterar estoque de) dados de outro tenant.
 */
class TenantExists implements ValidationRule
{
    public function __construct(
        private readonly string $table,
        private readonly string $column = 'id',
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $tenantId = auth()->user()?->tenant_id;

        // Só consulta com UUID válido: o PostgreSQL lança erro (500) em vez de "não encontrado"
        $exists = $tenantId
            && is_string($value)
            && Str::isUuid($value)
            && DB::table($this->table)
                ->where($this->column, $value)
                ->where('tenant_id', $tenantId)
                ->exists();

        if (! $exists) {
            $fail('validation.exists')->translate();
        }
    }
}
