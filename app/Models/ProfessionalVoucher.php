<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfessionalVoucher extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'professional_id',
        'amount', 'description', 'issued_at',
        'commission_payment_id',
    ];

    protected $casts = [
        'amount'    => 'decimal:2',
        'issued_at' => 'date',
    ];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function commissionPayment(): BelongsTo
    {
        return $this->belongsTo(CommissionPayment::class);
    }

    public function isPending(): bool
    {
        return is_null($this->commission_payment_id);
    }
}
