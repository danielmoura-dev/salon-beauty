<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Category;
use App\Models\Client;
use App\Models\CommissionPayment;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProfessionalVoucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * Um salão nunca pode ler, alterar ou referenciar dados de outro salão.
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    /** Cria um conjunto completo de dados pertencentes ao salão "vítima". */
    private function victimData(): array
    {
        $tenant       = $this->makeTenant(['name' => 'Salão Vitima']);
        $client       = $this->makeClient($tenant);
        $professional = $this->makeProfessional($tenant);
        $service      = $this->makeService($tenant);
        $product      = $this->makeProduct($tenant, ['track_stock' => true, 'stock_qty' => 10]);
        $category     = $this->makeFor(Category::class, $tenant, ['type' => 'service', 'name' => 'Cat']);

        $order = $this->makeFor(Order::class, $tenant, ['client_id' => $client->id, 'status' => 'open', 'total' => 100]);
        $item  = OrderItem::create([
            'order_id' => $order->id, 'professional_id' => $professional->id, 'type' => 'service',
            'description' => 'Corte', 'qty' => 1, 'unit_price' => 100, 'commission_pct' => 10, 'has_commission' => true,
        ]);
        Payment::create(['order_id' => $order->id, 'method' => 'cash', 'amount' => 100]);

        $expense = $this->makeFor(Expense::class, $tenant, [
            'description' => 'Aluguel', 'amount' => 500, 'payment_type' => 'one_time', 'due_date' => now()->toDateString(),
        ]);
        $appointment = $this->makeFor(Appointment::class, $tenant, [
            'client_id' => $client->id, 'professional_id' => $professional->id, 'service_id' => $service->id,
            'date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00', 'status' => 'scheduled',
        ]);
        $voucher = $this->makeFor(ProfessionalVoucher::class, $tenant, [
            'professional_id' => $professional->id, 'amount' => 20, 'description' => 'Vale', 'issued_at' => now()->toDateString(),
        ]);
        $commission = $this->makeFor(CommissionPayment::class, $tenant, [
            'professional_id' => $professional->id, 'period_end' => now()->toDateString(), 'net_amount' => 10,
        ]);

        return compact('tenant', 'client', 'professional', 'service', 'product', 'category',
            'order', 'item', 'expense', 'appointment', 'voucher', 'commission');
    }

    public static function foreignResourceRoutes(): array
    {
        return [
            'client update'           => ['PUT',    '/clients/{client}'],
            'client delete'           => ['DELETE', '/clients/{client}'],
            'professional update'     => ['PUT',    '/professionals/{professional}'],
            'professional delete'     => ['DELETE', '/professionals/{professional}'],
            'service update'          => ['PUT',    '/services/{service}'],
            'service delete'          => ['DELETE', '/services/{service}'],
            'product update'          => ['PUT',    '/products/{product}'],
            'product delete'          => ['DELETE', '/products/{product}'],
            'expense update'          => ['PUT',    '/expenses/{expense}'],
            'expense delete'          => ['DELETE', '/expenses/{expense}'],
            'expense toggle'          => ['POST',   '/expenses/{expense}/toggle'],
            'expense cancel recur.'   => ['DELETE', '/expenses/{expense}/cancel-recurrence'],
            'appointment update'      => ['PATCH',  '/appointments/{appointment}'],
            'appointment delete'      => ['DELETE', '/appointments/{appointment}'],
            'order show'              => ['GET',    '/orders/{order}'],
            'order data'              => ['GET',    '/orders/{order}/data'],
            'order add item'          => ['POST',   '/orders/{order}/items'],
            'order add payment'       => ['POST',   '/orders/{order}/payments'],
            'order clear payments'    => ['POST',   '/orders/{order}/payments/clear'],
            'order close'             => ['POST',   '/orders/{order}/close'],
            'order reopen'            => ['POST',   '/orders/{order}/reopen'],
            'order cancel'            => ['POST',   '/orders/{order}/cancel'],
            'category delete'         => ['DELETE', '/categories/{category}'],
            'voucher delete'          => ['DELETE', '/professionals/vouchers/{voucher}'],
            'voucher create'          => ['POST',   '/professionals/{professional}/vouchers'],
            'commission detail'       => ['GET',    '/professionals/{professional}/commissions/detail'],
            'commission pay'          => ['POST',   '/professionals/{professional}/commissions/pay'],
            'commission cancel'       => ['DELETE', '/professionals/commissions/{commission}/cancel'],
            'booking toggle prof.'    => ['POST',   '/booking-link/professionals/{professional}/toggle'],
            'booking prof. services'  => ['POST',   '/booking-link/professionals/{professional}/services'],
        ];
    }

    #[DataProvider('foreignResourceRoutes')]
    public function test_foreign_resources_are_not_found(string $method, string $template): void
    {
        $victim = $this->victimData();
        $this->signInTenant(['name' => 'Salão Atacante']);

        $uri = preg_replace_callback('/\{(\w+)\}/', fn($m) => $victim[$m[1]]->id, $template);

        $this->json($method, $uri, [])->assertNotFound();
    }

    public function test_cannot_modify_or_delete_an_item_of_another_tenants_order(): void
    {
        $victim = $this->victimData();
        [$tenant] = $this->signInTenant();
        $client = $this->makeClient($tenant);
        $mine   = $this->makeFor(Order::class, $tenant, ['client_id' => $client->id, 'status' => 'open', 'total' => 0]);

        $payload = ['type' => 'service', 'description' => 'HACK', 'qty' => 99, 'unit_price' => 0.01];

        $this->patchJson("/orders/{$mine->id}/items/{$victim['item']->id}", $payload)->assertNotFound();
        $this->deleteJson("/orders/{$mine->id}/items/{$victim['item']->id}")->assertNotFound();

        $item = $victim['item']->fresh();
        $this->assertNotNull($item, 'item da vítima não pode ser apagado');
        $this->assertSame('Corte', $item->description);
        $this->assertSame(1, (int) $item->qty);
    }

    public function test_item_must_belong_to_the_order_in_the_url(): void
    {
        [$tenant] = $this->signInTenant();
        $client = $this->makeClient($tenant);
        $orderA = $this->makeFor(Order::class, $tenant, ['client_id' => $client->id, 'status' => 'open', 'total' => 0]);
        $orderB = $this->makeFor(Order::class, $tenant, ['client_id' => $client->id, 'status' => 'open', 'total' => 0]);
        $itemB  = OrderItem::create(['order_id' => $orderB->id, 'type' => 'other', 'description' => 'B', 'qty' => 1, 'unit_price' => 10]);

        $this->deleteJson("/orders/{$orderA->id}/items/{$itemB->id}")->assertNotFound();
        $this->assertNotNull($itemB->fresh());
    }

    public function test_own_item_can_still_be_updated_and_removed(): void
    {
        [$tenant] = $this->signInTenant();
        $client = $this->makeClient($tenant);
        $order  = $this->makeFor(Order::class, $tenant, ['client_id' => $client->id, 'status' => 'open', 'total' => 0]);
        $item   = OrderItem::create(['order_id' => $order->id, 'type' => 'other', 'description' => 'X', 'qty' => 1, 'unit_price' => 10]);

        $this->patchJson("/orders/{$order->id}/items/{$item->id}", ['type' => 'other', 'description' => 'Y', 'qty' => 2, 'unit_price' => 15])
            ->assertOk();
        $this->assertEquals(30, $order->fresh()->total);

        $this->deleteJson("/orders/{$order->id}/items/{$item->id}")->assertOk();
        $this->assertNull($item->fresh());
    }

    public function test_cannot_reference_another_tenants_records_in_payloads(): void
    {
        $victim = $this->victimData();
        [$tenant] = $this->signInTenant();
        $myClient = $this->makeClient($tenant);
        $myOrder  = $this->makeFor(Order::class, $tenant, ['client_id' => $myClient->id, 'status' => 'open', 'total' => 0]);
        $myProf   = $this->makeProfessional($tenant);

        // comanda para cliente de outro salão
        $this->postJson('/orders', ['client_id' => $victim['client']->id])->assertStatus(422);

        // item referenciando produto / profissional de outro salão
        $base = ['type' => 'product', 'description' => 'p', 'qty' => 1, 'unit_price' => 5];
        $this->postJson("/orders/{$myOrder->id}/items", $base + ['product_id' => $victim['product']->id])->assertStatus(422);
        $this->postJson("/orders/{$myOrder->id}/items", $base + ['professional_id' => $victim['professional']->id])->assertStatus(422);
        $this->assertSame(10, (int) $victim['product']->fresh()->stock_qty, 'estoque da vítima intacto');

        // agendamento com cliente / profissional / serviço de outro salão
        $apt = ['date' => now()->addDay()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00'];
        $this->postJson('/appointments', $apt + ['client_id' => $victim['client']->id, 'professional_id' => $myProf->id])->assertStatus(422);
        $this->postJson('/appointments', $apt + ['client_id' => $myClient->id, 'professional_id' => $victim['professional']->id])->assertStatus(422);
        $this->postJson('/appointments', $apt + ['client_id' => $myClient->id, 'professional_id' => $myProf->id, 'service_id' => $victim['service']->id])->assertStatus(422);

        // categorias de outro salão
        $this->postJson('/services', ['name' => 'S', 'price' => 10, 'duration_min' => 30, 'category_id' => $victim['category']->id])->assertStatus(422);
        $this->postJson('/products', ['name' => 'P', 'category_id' => $victim['category']->id])->assertStatus(422);
        $this->postJson('/expenses', ['description' => 'E', 'amount' => 5, 'payment_type' => 'one_time', 'due_date' => now()->toDateString(), 'category_id' => $victim['category']->id])->assertStatus(422);

        // serviços de outro salão no link de agendamento
        $this->postJson("/booking-link/professionals/{$myProf->id}/services", ['service_ids' => [$victim['service']->id]])->assertStatus(422);
    }

    public function test_commission_payment_ignores_items_and_vouchers_of_other_tenants(): void
    {
        $victim = $this->victimData();
        [$tenant] = $this->signInTenant();
        $myProf = $this->makeProfessional($tenant);

        $this->post("/professionals/{$myProf->id}/commissions/pay", [
            'period_type' => 'accumulated',
            'date_to'     => now()->toDateString(),
            'item_ids'    => [$victim['item']->id],
            'voucher_ids' => [$victim['voucher']->id],
        ]);

        $this->assertNull($victim['item']->fresh()->commission_paid_at);
        $this->assertNull($victim['voucher']->fresh()->commission_payment_id);
    }

    public function test_lists_only_show_own_tenant_data(): void
    {
        $victim = $this->victimData();
        [$tenant] = $this->signInTenant();
        $mine = $this->makeClient($tenant, ['name' => 'Cliente Minha Unica']);

        $response = $this->get('/clients')->assertOk();
        $response->assertSee('Cliente Minha Unica');
        $response->assertDontSee($victim['client']->name);

        $this->assertSame(1, Client::count());
    }
}
