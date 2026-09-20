<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'logo',
        'banner',
        'plan_status',
        'trial_ends_at',
        'stripe_customer_id',
        'credit_card_fee',
        'debit_card_fee',
        'allow_duplicate_phone',
        'show_pending_orders',
        'agenda_start_hour',
        'agenda_end_hour',
        'affiliate_id',
        'affiliate_discount_months_remaining',
        'booking_slug',
        'booking_active',
        'booking_interval_min',
        'booking_show_prices',
        'booking_banner_color',
    ];

    protected $casts = [
        'trial_ends_at'                       => 'datetime',
        'allow_duplicate_phone'               => 'boolean',
        'show_pending_orders'                 => 'boolean',
        'booking_active'                      => 'boolean',
        'booking_show_prices'                 => 'boolean',
        'booking_interval_min'                => 'integer',
        'credit_card_fee'                     => 'decimal:2',
        'debit_card_fee'                      => 'decimal:2',
        'affiliate_discount_months_remaining' => 'integer',
    ];

    public function subscription(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function affiliate(): BelongsTo
    {
        return $this->belongsTo(Affiliate::class);
    }

    public function isActive(): bool
    {
        if ($this->plan_status === 'active') {
            return $this->hasPaidAccess();
        }

        return $this->plan_status === 'trial' && (bool) $this->trial_ends_at?->isFuture();
    }

    /**
     * `plan_status = active` só vale enquanto houver um pagamento cobrindo o período:
     * - Stripe renova sozinho e avisa por webhook (status da assinatura);
     * - PIX é avulso: o acesso termina no vencimento (current_period_end), nada o desativa depois.
     * Sem nenhuma assinatura registrada (ativação manual) confia-se no plan_status.
     */
    private function hasPaidAccess(): bool
    {
        $subscriptions = Subscription::where('tenant_id', $this->getKey())->get();

        if ($subscriptions->isEmpty()) {
            return true;
        }

        return $subscriptions->contains(fn (Subscription $s) => match ($s->gateway) {
            'stripe'      => $s->status === 'active',
            // pending = novo QR gerado para renovar; o período atual segue valendo até vencer
            'mercadopago' => in_array($s->status, ['active', 'pending'], true)
                && $s->current_period_end !== null
                && $s->current_period_end->gte(today()),
            default       => false,
        });
    }

    /** Assinatura deste tenant em um gateway específico (há no máximo uma por gateway). */
    public function subscriptionFor(string $gateway): ?Subscription
    {
        return Subscription::where('tenant_id', $this->getKey())->where('gateway', $gateway)->first();
    }
}
