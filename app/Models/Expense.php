<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'category_id', 'description', 'amount',
        'payment_type', 'installments', 'current_installment',
        'recurrence_group_id', 'due_date', 'is_paid', 'paid_at', 'notes',
    ];

    protected $casts = [
        'amount'  => 'decimal:2',
        'is_paid' => 'boolean',
        'due_date'=> 'date',
        'paid_at' => 'date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public static array $paymentTypeLabels = [
        'one_time'    => 'À vista',
        'installment' => 'Parcelado',
        'recurring'   => 'Recorrente',
    ];
}