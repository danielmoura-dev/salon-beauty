<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Affiliate extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'code',
        'commission_pct',
        'discount_pct',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'commission_pct' => 'decimal:2',
        'discount_pct'   => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }

    public function pendingCommissionsAmount(): float
    {
        return (float) $this->commissions()->where('status', 'pending')->sum('commission_amount');
    }
}
