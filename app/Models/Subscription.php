<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasUuids;

    protected $fillable = [
        'tenant_id', 'gateway', 'gateway_subscription_id',
        'gateway_customer_id', 'status', 'current_period_end',
        'cancel_at_period_end',
    ];

    protected $casts = [
        'current_period_end'  => 'date',
        'cancel_at_period_end' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['active', 'trialing']);
    }
}