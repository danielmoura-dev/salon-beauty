<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'category_id', 'name', 'brand',
        'photo', 'for_sale', 'price', 'commission_pct', 'active',
    ];

    protected $casts = [
        'for_sale'       => 'boolean',
        'active'         => 'boolean',
        'price'          => 'decimal:2',
        'commission_pct' => 'decimal:2',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}