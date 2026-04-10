<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'name', 'phone', 'email',
        'birthday', 'photo', 'balance', 'notes',
    ];

    protected $casts = [
        'birthday' => 'date',
        'balance'  => 'decimal:2',
    ];

    // Crédito disponível (saldo positivo)
    public function hasCredit(): bool
    {
        return $this->balance > 0;
    }

    // Dívida (saldo negativo)
    public function hasDebt(): bool
    {
        return $this->balance < 0;
    }

    public function formattedBalance(): string
    {
        return 'R$ ' . number_format(abs($this->balance), 2, ',', '.');
    }
}