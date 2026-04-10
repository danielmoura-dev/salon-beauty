<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'name', 'slug', 'email', 'phone', 'logo', 'plan_status', 'trial_ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isActive(): bool
    {
        if ($this->plan_status === 'active') return true;
        if ($this->plan_status === 'trial' && $this->trial_ends_at?->isFuture()) return true;
        return false;
    }
}