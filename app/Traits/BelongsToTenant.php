<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

trait BelongsToTenant
{
    use HasUuids;

    public static function bootBelongsToTenant(): void
    {
        // Aplica o scope automaticamente em toda query
        static::addGlobalScope(new TenantScope());

        // Injeta tenant_id automaticamente ao criar qualquer registro
        static::creating(function ($model) {
            if (auth()->check() && empty($model->tenant_id)) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}