<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'logo',
        'plan_status',
        'trial_ends_at',
        'stripe_customer_id',
        'credit_card_fee',
        'debit_card_fee',
        'allow_duplicate_phone',
        'show_pending_orders',
        'agenda_start_hour',
        'agenda_end_hour',
        'affiliate_id',
        'affiliate_discount_months_remaining',
    ];

    protected $casts = [
        'trial_ends_at'                       => 'datetime',
        'allow_duplicate_phone'               => 'boolean',
        'show_pending_orders'                 => 'boolean',
        'credit_card_fee'                     => 'decimal:2',
        'debit_card_fee'                      => 'decimal:2',
        'affiliate_discount_months_remaining' => 'integer',
    ];

    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function isActive(): bool
    {
        if ($this->plan_status === 'active')
            return true;
        if ($this->plan_status === 'trial' && $this->trial_ends_at?->isFuture())
            return true;
        return false;
    }
}