<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * Renderiza cada tela principal com um salão populado (comanda paga, despesa, agendamento...).
 * Pega erros de Blade/consulta que páginas vazias escondem.
 */
class AppPagesTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public static function pages(): array
    {
        return [
            'dashboard'            => ['/dashboard'],
            'agenda'               => ['/agenda'],
            'orders'               => ['/orders'],
            'clients'              => ['/clients'],
            'professionals'        => ['/professionals'],
            'commissions'          => ['/professionals/commissions'],
            'vouchers'             => ['/professionals/vouchers'],
            'services'             => ['/services'],
            'products'             => ['/products'],
            'expenses'             => ['/expenses'],
            'reports (month)'      => ['/reports'],
            'reports (day)'        => ['/reports?mode=day'],
            'booking link'         => ['/booking-link'],
            'settings'             => ['/settings'],
            'subscription'         => ['/subscription'],
        ];
    }

    #[DataProvider('pages')]
    public function test_page_renders_with_real_data(string $url): void
    {
        [$tenant] = $this->signInTenant();
        $client  = $this->makeClient($tenant, ['balance' => -20]);
        $prof    = $this->makeProfessional($tenant);
        $service = $this->makeService($tenant);
        $product = $this->makeProduct($tenant, ['track_stock' => true, 'stock_qty' => 1, 'stock_alert_qty' => 5]);
        $prof->services()->attach($service->id);

        $order = $this->makeFor(Order::class, $tenant, ['client_id' => $client->id, 'status' => 'closed', 'total' => 100]);
        OrderItem::create([
            'order_id' => $order->id, 'professional_id' => $prof->id, 'type' => 'service', 'description' => 'Corte',
            'qty' => 1, 'unit_price' => 100, 'commission_pct' => 30, 'has_commission' => true,
        ]);
        Payment::create(['order_id' => $order->id, 'method' => 'pix', 'amount' => 100]);

        $this->makeFor(Expense::class, $tenant, [
            'description' => 'Aluguel', 'amount' => 500, 'payment_type' => 'one_time', 'due_date' => now()->toDateString(),
        ]);
        $this->makeFor(Category::class, $tenant, ['type' => 'service', 'name' => 'Cabelo']);
        $this->makeFor(Appointment::class, $tenant, [
            'client_id' => $client->id, 'professional_id' => $prof->id, 'service_id' => $service->id, 'order_id' => $order->id,
            'date' => now()->toDateString(), 'start_time' => '10:00', 'end_time' => '11:00', 'status' => 'scheduled',
        ]);

        $this->get($url)->assertOk();
    }

    public function test_reports_page_shows_the_closed_order_revenue(): void
    {
        [$tenant] = $this->signInTenant();
        $client = $this->makeClient($tenant);
        $prof   = $this->makeProfessional($tenant, ['name' => 'Ana Profissional']);
        $order  = $this->makeFor(Order::class, $tenant, ['client_id' => $client->id, 'status' => 'closed', 'total' => 250]);
        OrderItem::create(['order_id' => $order->id, 'professional_id' => $prof->id, 'type' => 'service', 'description' => 'Corte', 'qty' => 1, 'unit_price' => 250, 'commission_pct' => 0, 'has_commission' => false]);
        Payment::create(['order_id' => $order->id, 'method' => 'cash', 'amount' => 250]);

        $this->get('/reports')->assertOk()->assertSee('Ana Profissional')->assertSee('250');
    }

    public function test_hostile_professional_name_cannot_break_out_of_the_report_script(): void
    {
        [$tenant] = $this->signInTenant();
        $client = $this->makeClient($tenant);
        $prof   = $this->makeProfessional($tenant, ['name' => '</script><script>alert(1)</script>']);
        $order  = $this->makeFor(Order::class, $tenant, ['client_id' => $client->id, 'status' => 'closed', 'total' => 10]);
        OrderItem::create(['order_id' => $order->id, 'professional_id' => $prof->id, 'type' => 'service', 'description' => 'x', 'qty' => 1, 'unit_price' => 10, 'commission_pct' => 0, 'has_commission' => false]);
        Payment::create(['order_id' => $order->id, 'method' => 'cash', 'amount' => 10]);

        $this->get('/reports')->assertOk()->assertDontSee('</script><script>alert(1)</script>', false);
    }
}
