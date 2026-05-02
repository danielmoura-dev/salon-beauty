<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    use HasUuids;

    protected $fillable = [
        'affiliate_id',
        'tenant_id',
        'subscription_amount',
        'discount_amount',
        'charged_amount',
        'commission_amount',
        'gateway',
        'gateway_payment_id',
        'period',
        'status',
        'paid_at',
    ];

    protected $casts = [
        'subscription_amount' => 'decimal:2',
        'discount_amount'     => 'decimal:2',
        'charged_amount'      => 'decimal:2',
        'commission_amount'   => 'decimal:2',
        'paid_at'             => 'datetime',
    ];

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
