<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasUuids;

    protected $fillable = ['order_id', 'method', 'amount', 'notes'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

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