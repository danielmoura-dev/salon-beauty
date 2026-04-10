<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Professional extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'user_id', 'name', 'photo', 'specialty',
        'birthday', 'show_on_agenda', 'receives_commission',
        'commission_pct', 'work_schedule',
    ];

    protected $casts = [
        'birthday'            => 'date',
        'show_on_agenda'      => 'boolean',
        'receives_commission' => 'boolean',
        'commission_pct'      => 'decimal:2',
        'work_schedule'       => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class)
                    ->withPivot('commission_pct');
    }

    // Retorna a comissão para um serviço específico
    public function commissionForService(Service $service): float
    {
        $pivot = $this->services->find($service->id)?->pivot;

        if ($pivot && $pivot->commission_pct !== null) {
            return (float) $pivot->commission_pct;
        }

        if ($this->receives_commission && $this->commission_pct > 0) {
            return (float) $this->commission_pct;
        }

        return (float) $service->commission_pct;
    }
}