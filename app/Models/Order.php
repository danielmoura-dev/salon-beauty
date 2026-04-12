<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_id', 'appointment_id',
        'status', 'notes', 'total',
    ];

    protected $casts = [
        'total' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function recalcTotal(): void
    {
        $this->total = $this->items->sum(fn($i) => $i->subtotal());
        $this->save();
    }

    public function totalPaid(): float
    {
        return (float) $this->payments->sum(fn($p) => $p->effectiveAmount());
    }

    public function balance(): float
    {
        return round($this->totalPaid() - (float) $this->total, 2);
    }

    public function isPaid(): bool
    {
        return $this->totalPaid() >= (float) $this->total;
    }
}