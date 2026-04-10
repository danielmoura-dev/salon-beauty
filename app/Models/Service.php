<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'category_id', 'name', 'price',
        'duration_min', 'commission_pct', 'notes', 'active',
    ];

    protected $casts = [
        'price'          => 'decimal:2',
        'commission_pct' => 'decimal:2',
        'active'         => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function professionals(): BelongsToMany
    {
        return $this->belongsToMany(Professional::class)
                    ->withPivot('commission_pct');
    }

    public function formattedDuration(): string
    {
        $h = intdiv($this->duration_min, 60);
        $m = $this->duration_min % 60;
        return $h > 0 ? "{$h}h" . ($m > 0 ? "{$m}min" : '') : "{$m}min";
    }

    public function formattedPrice(): string
    {
        return 'R$ ' . number_format($this->price, 2, ',', '.');
    }
}