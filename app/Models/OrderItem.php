<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasUuids;

    protected $fillable = [
        'order_id', 'professional_id', 'type',
        'description', 'qty', 'unit_price',
        'commission_pct', 'has_commission',
        'commission_paid_at', 'commission_payment_id',
    ];

    protected $casts = [
        'unit_price'         => 'decimal:2',
        'commission_pct'     => 'decimal:2',
        'has_commission'     => 'boolean',
        'commission_paid_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function subtotal(): float
    {
        return (float) ($this->qty * $this->unit_price);
    }

    public function commissionValue(): float
    {
        if (! $this->has_commission) return 0;
        return round($this->subtotal() * ($this->commission_pct / 100), 2);
    }
}