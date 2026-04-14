<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = ['order_id', 'method', 'installments', 'amount', 'fee_pct', 'fee_amount', 'notes'];

    protected $casts = [
        'amount'     => 'decimal:2',
        'fee_pct'    => 'decimal:2',
        'fee_amount' => 'decimal:2',
    ];

    /** Valor que efetivamente conta para quitar a comanda (descontada a taxa da maquininha). */
    public function effectiveAmount(): float
    {
        return round((float) $this->amount - (float) $this->fee_amount, 2);
    }

    public static array $methodLabels = [
        'pix'         => 'Pix',
        'credit_card' => 'Cartão de Crédito',
        'debit_card'  => 'Cartão de Débito',
        'cash'        => 'Dinheiro',
        'credit'      => 'Crédito do Cliente',
        'debt'        => 'Fiado',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function methodLabel(): string
    {
        return self::$methodLabels[$this->method] ?? $this->method;
    }
}