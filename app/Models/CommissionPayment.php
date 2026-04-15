<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionPayment extends \Illuminate\Database\Eloquent\Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'professional_id',
        'period_start', 'period_end',
        'total_services', 'total_products', 'total_others', 'total_vouchers',
        'net_amount', 'notes',
    ];

    protected $casts = [
        'period_start'   => 'date',
        'period_end'     => 'date',
        'total_services' => 'decimal:2',
        'total_products' => 'decimal:2',
        'total_others'   => 'decimal:2',
        'total_vouchers' => 'decimal:2',
        'net_amount'     => 'decimal:2',
    ];

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(ProfessionalVoucher::class);
    }
}
