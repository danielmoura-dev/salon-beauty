<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\CommissionPayment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Professional;
use App\Models\ProfessionalVoucher;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private Tenant $tenant;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->tenant] = $this->signInTenant();
        $this->client   = $this->makeClient($this->tenant, ['balance' => 0]);
    }

    private function openOrder(): Order
    {
        return $this->makeFor(Order::class, $this->tenant, [
            'client_id' => $this->client->id, 'status' => 'open', 'total' => 0,
        ]);
    }

    private function addItem(Order $order, array $over = [])
    {
        return $this->postJson("/orders/{$order->id}/items", [
            'type' => 'service', 'description' => 'Corte', 'qty' => 1, 'unit_price' => 100, ...$over,
        ]);
    }

    private function pay(Order $order, string $method, float $amount)
    {
        return $this->postJson("/orders/{$order->id}/payments", ['method' => $method, 'amount' => $amount]);
    }

    private function balance(): float
    {
        return (float) Client::find($this->client->id)->balance;
    }

    // ---------- fluxo normal ----------

    public function test_items_recalculate_the_order_total(): void
    {
        $order = $this->openOrder();

        $this->addItem($order, ['qty' => 2, 'unit_price' => 50])->assertOk();
        $this->addItem($order, ['description' => 'Barba', 'unit_price' => 30.5])->assertOk();

        $this->assertEquals(130.5, $order->fresh()->total);
    }

    public function test_tracked_stock_follows_add_update_and_remove_of_items(): void
    {
        $product = $this->makeProduct($this->tenant, ['track_stock' => true, 'stock_qty' => 10]);
        $order   = $this->openOrder();
        $item    = ['type' => 'product', 'description' => 'Shampoo', 'unit_price' => 20, 'product_id' => $product->id];

        $this->addItem($order, $item + ['qty' => 3])->assertOk();
        $this->assertSame(7, (int) $product->fresh()->stock_qty);

        $itemId = OrderItem::where('order_id', $order->id)->value('id');
        $this->patchJson("/orders/{$order->id}/items/{$itemId}", $item + ['qty' => 5])->assertOk();
        $this->assertSame(5, (int) $product->fresh()->stock_qty);

        $this->deleteJson("/orders/{$order->id}/items/{$itemId}")->assertOk();
        $this->assertSame(10, (int) $product->fresh()->stock_qty);
    }

    public function test_full_payment_closes_the_order_and_partial_does_not(): void
    {
        $order = $this->openOrder();
        $this->addItem($order)->assertOk();

        $this->pay($order, 'cash', 40)->assertOk();
        $this->assertSame('open', $order->fresh()->status);

        $this->pay($order, 'pix', 60)->assertOk();
        $this->assertSame('closed', $order->fresh()->status);
        $this->assertEquals(0, $this->balance());
    }

    public function test_overpayment_becomes_client_credit_only_once(): void
    {
        $order = $this->openOrder();
        $this->addItem($order)->assertOk();

        $this->pay($order, 'cash', 120)->assertOk();
        $this->assertEquals(20, $this->balance());

        // pagamento extra: o excesso total passa a ser 25, então o crédito total deve ser 25 (e não 45)
        $this->pay($order, 'cash', 5)->assertOk();
        $this->assertEquals(25, $this->balance());
    }

    public function test_debt_payment_lowers_balance_and_clearing_payments_restores_it(): void
    {
        $order = $this->openOrder();
        $this->addItem($order)->assertOk();

        $this->pay($order, 'debt', 100)->assertOk();
        $this->assertEquals(-100, $this->balance());
        $this->assertSame('closed', $order->fresh()->status);

        $this->postJson("/orders/{$order->id}/payments/clear")->assertOk();
        $this->assertEquals(0, $this->balance());
        $this->assertSame('open', $order->fresh()->status);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    public function test_cancelling_an_order_restores_stock_and_removes_it(): void
    {
        $product = $this->makeProduct($this->tenant, ['track_stock' => true, 'stock_qty' => 10]);
        $order   = $this->openOrder();
        $this->addItem($order, ['type' => 'product', 'product_id' => $product->id, 'qty' => 4, 'unit_price' => 10])->assertOk();
        $this->assertSame(6, (int) $product->fresh()->stock_qty);

        $this->postJson("/orders/{$order->id}/cancel")->assertOk();

        $this->assertSame(10, (int) $product->fresh()->stock_qty);
        $this->assertNull(Order::find($order->id));
    }

    public function test_cancelled_order_rejects_new_items_and_payments(): void
    {
        $order = $this->openOrder();
        $order->update(['status' => 'cancelled']);

        $this->addItem($order)->assertStatus(422);
        $this->pay($order, 'cash', 10)->assertStatus(422);
        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
    }

    // ---------- atomicidade: falha no meio não pode deixar dados pela metade ----------

    public function test_failed_payment_leaves_no_payment_and_no_balance_change(): void
    {
        $order = $this->openOrder();
        $this->addItem($order)->assertOk();

        // falha exatamente quando a comanda tentaria fechar (depois do pagamento e do saldo)
        Order::updating(function (Order $o) {
            if ($o->isDirty('status')) throw new \RuntimeException('falha simulada');
        });

        $this->pay($order, 'debt', 100)->assertStatus(500);

        $this->assertSame(0, Payment::where('order_id', $order->id)->count());
        $this->assertEquals(0, $this->balance());
    }

    public function test_failed_cancel_keeps_stock_unchanged(): void
    {
        $product = $this->makeProduct($this->tenant, ['track_stock' => true, 'stock_qty' => 10]);
        $order   = $this->openOrder();
        $this->addItem($order, ['type' => 'product', 'product_id' => $product->id, 'qty' => 4, 'unit_price' => 10])->assertOk();

        Order::deleting(fn () => throw new \RuntimeException('falha simulada'));

        $this->postJson("/orders/{$order->id}/cancel")->assertStatus(500);

        $this->assertSame(6, (int) $product->fresh()->stock_qty, 'estoque não pode ser devolvido se a comanda não foi apagada');
        $this->assertNotNull(Order::find($order->id));
    }

    public function test_failed_appointment_creation_leaves_no_partial_records(): void
    {
        $prof    = $this->makeProfessional($this->tenant);
        $service = $this->makeService($this->tenant);

        OrderItem::creating(fn () => throw new \RuntimeException('falha simulada'));

        $this->postJson('/appointments', [
            'client_id' => $this->client->id, 'professional_id' => $prof->id, 'service_ids' => [$service->id],
            'date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00',
            'recurrence' => 'weekly', 'create_order' => true,
        ])->assertStatus(500);

        $this->assertSame(0, Appointment::count());
        $this->assertSame(0, Order::count());
    }

    public function test_failed_commission_payment_marks_nothing_as_paid(): void
    {
        $prof  = $this->makeProfessional($this->tenant);
        $order = $this->openOrder();
        $order->update(['status' => 'closed']);
        $item  = OrderItem::create([
            'order_id' => $order->id, 'professional_id' => $prof->id, 'type' => 'service',
            'description' => 'Corte', 'qty' => 1, 'unit_price' => 100, 'commission_pct' => 50, 'has_commission' => true,
        ]);
        $voucher = $this->makeFor(ProfessionalVoucher::class, $this->tenant, [
            'professional_id' => $prof->id, 'amount' => 10, 'description' => 'Vale', 'issued_at' => now()->toDateString(),
        ]);

        ProfessionalVoucher::updating(fn () => throw new \RuntimeException('falha simulada'));

        $this->post("/professionals/{$prof->id}/commissions/pay", [
            'period_type' => 'accumulated', 'date_to' => now()->toDateString(),
            'item_ids' => [$item->id], 'voucher_ids' => [$voucher->id],
        ])->assertStatus(500);

        $this->assertSame(0, CommissionPayment::count());
        $this->assertNull($item->fresh()->commission_paid_at);
    }

    public function test_commission_payment_pays_items_once(): void
    {
        $prof  = $this->makeProfessional($this->tenant);
        $order = $this->openOrder();
        $order->update(['status' => 'closed']);
        $item  = OrderItem::create([
            'order_id' => $order->id, 'professional_id' => $prof->id, 'type' => 'service',
            'description' => 'Corte', 'qty' => 1, 'unit_price' => 100, 'commission_pct' => 50, 'has_commission' => true,
        ]);
        $payload = ['period_type' => 'accumulated', 'date_to' => now()->toDateString(), 'item_ids' => [$item->id]];

        $this->post("/professionals/{$prof->id}/commissions/pay", $payload);
        $this->post("/professionals/{$prof->id}/commissions/pay", $payload); // clique duplo

        $this->assertSame(1, CommissionPayment::count());
        $this->assertEquals(50, CommissionPayment::first()->net_amount);
    }

    public function test_failed_registration_does_not_leave_a_half_created_account(): void
    {
        auth()->logout();
        Professional::creating(fn () => throw new \RuntimeException('falha simulada'));

        $this->post('/register', [
            'name' => 'Ana', 'business_name' => 'Studio Ana', 'email' => 'ana@example.com',
            'password' => 'segredo123', 'password_confirmation' => 'segredo123',
        ])->assertStatus(500);

        $this->assertNull(User::where('email', 'ana@example.com')->first(), 'e-mail ficaria preso numa conta quebrada');
        $this->assertSame(0, Tenant::where('email', 'ana@example.com')->count());
    }

    public function test_professional_custom_commissions_ignore_foreign_services_and_clamp_values(): void
    {
        $other        = $this->makeTenant();
        $foreignSvc   = $this->makeService($other);
        $mySvc        = $this->makeService($this->tenant);

        $this->post('/professionals', [
            'name' => 'Nova',
            'custom_commissions' => [$mySvc->id => '30', $foreignSvc->id => '30'],
        ])->assertRedirect();

        $prof = Professional::where('name', 'Nova')->firstOrFail();
        $this->assertSame([$mySvc->id], $prof->services()->pluck('services.id')->all());

        $this->post('/professionals', ['name' => 'Absurda', 'custom_commissions' => [$mySvc->id => '99999']])
            ->assertSessionHasErrors();
        $this->assertNull(Professional::where('name', 'Absurda')->first());
    }

    public function test_cancelling_an_order_with_debt_reverses_the_client_balance(): void
    {
        $order = $this->openOrder();
        $this->addItem($order)->assertOk();
        $this->pay($order, 'debt', 100)->assertOk();
        $this->assertEquals(-100, $this->balance());

        $this->postJson("/orders/{$order->id}/cancel")->assertOk();

        $this->assertEquals(0, $this->balance(), 'cliente não pode continuar devendo por uma comanda apagada');
    }

    public function test_deleting_an_appointment_restores_order_stock_and_balance(): void
    {
        $prof    = $this->makeProfessional($this->tenant);
        $product = $this->makeProduct($this->tenant, ['track_stock' => true, 'stock_qty' => 10]);
        $order   = $this->openOrder();
        $this->addItem($order, ['type' => 'product', 'product_id' => $product->id, 'qty' => 2, 'unit_price' => 50])->assertOk();
        $this->pay($order, 'debt', 100)->assertOk();

        $apt = $this->makeFor(Appointment::class, $this->tenant, [
            'client_id' => $this->client->id, 'professional_id' => $prof->id, 'order_id' => $order->id,
            'date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00', 'status' => 'scheduled',
        ]);
        $this->assertSame(8, (int) $product->fresh()->stock_qty);

        $this->deleteJson("/appointments/{$apt->id}")->assertOk();

        $this->assertNull(Order::find($order->id));
        $this->assertSame(10, (int) $product->fresh()->stock_qty);
        $this->assertEquals(0, $this->balance());
    }

    public function test_commission_of_an_open_order_cannot_be_paid(): void
    {
        $prof  = $this->makeProfessional($this->tenant);
        $order = $this->openOrder(); // ainda aberta
        $item  = OrderItem::create([
            'order_id' => $order->id, 'professional_id' => $prof->id, 'type' => 'service',
            'description' => 'Corte', 'qty' => 1, 'unit_price' => 100, 'commission_pct' => 50, 'has_commission' => true,
        ]);

        $this->post("/professionals/{$prof->id}/commissions/pay", [
            'period_type' => 'accumulated', 'date_to' => now()->toDateString(), 'item_ids' => [$item->id],
        ])->assertSessionHasErrors('pay');

        $this->assertSame(0, CommissionPayment::count());
        $this->assertNull($item->fresh()->commission_paid_at);
    }
}
