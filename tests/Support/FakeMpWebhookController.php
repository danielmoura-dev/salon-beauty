<?php

namespace Tests\Support;

use App\Http\Controllers\WebhookController;

/** Troca as chamadas HTTP ao Mercado Pago por dados controlados pelo teste. */
class FakeMpWebhookController extends WebhookController
{
    public static ?array $payment = null;
    public static ?array $preApproval = null;
    public static bool $apiDown = false;

    protected function fetchMpPayment(string $id): object
    {
        if (self::$apiDown) {
            throw new \RuntimeException('Mercado Pago API indisponível');
        }

        return (object) self::$payment;
    }

    protected function fetchMpPreApproval(string $id): object
    {
        if (self::$apiDown) {
            throw new \RuntimeException('Mercado Pago API indisponível');
        }

        return (object) self::$preApproval;
    }
}
