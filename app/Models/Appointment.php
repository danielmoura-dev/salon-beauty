<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'client_id', 'professional_id', 'service_id', 'order_id',
        'date', 'start_time', 'end_time',
        'status', 'recurrence', 'recurrence_group_id',
        'create_order', 'notes',
    ];

    protected $casts = [
        'date'         => 'date',
        'create_order' => 'boolean',
    ];

    // Status label e cor para a view
    public static array $statusConfig = [
        'scheduled'   => ['label' => 'Agendado',       'bg' => 'bg-blue-100',   'text' => 'text-blue-700',   'border' => 'border-blue-300'],
        'confirmed'   => ['label' => 'Confirmado',      'bg' => 'bg-green-100',  'text' => 'text-green-700',  'border' => 'border-green-300'],
        'in_progress' => ['label' => 'Em atendimento',  'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700', 'border' => 'border-yellow-300'],
        'completed'   => ['label' => 'Finalizado',      'bg' => 'bg-gray-100',   'text' => 'text-gray-600',   'border' => 'border-gray-300'],
        'cancelled'   => ['label' => 'Cancelado',       'bg' => 'bg-red-100',    'text' => 'text-red-600',    'border' => 'border-red-300'],
        'no_show'     => ['label' => 'Não compareceu',  'bg' => 'bg-orange-100', 'text' => 'text-orange-700', 'border' => 'border-orange-300'],
    ];

    public function statusConfig(): array
    {
        return self::$statusConfig[$this->status];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function professional(): BelongsTo
    {
        return $this->belongsTo(Professional::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    // Duração em minutos
    public function durationMinutes(): int
    {
        return (int) \Carbon\Carbon::parse($this->start_time)
            ->diffInMinutes(\Carbon\Carbon::parse($this->end_time));
    }

    // Posição em pixels na grade (cada 30min = 64px)
    public function gridTop(int $startHour = 8): int
    {
        [$h, $m] = explode(':', $this->start_time);
        $minutesFromStart = (((int)$h - $startHour) * 60) + (int)$m;
        return ($minutesFromStart / 30) * 64;
    }

    public function gridHeight(): int
    {
        return ($this->durationMinutes() / 30) * 64;
    }
}