<?php

namespace Tests\Feature;

use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

/**
 * Comportamentos que diferem entre SQLite, MySQL e PostgreSQL.
 * Rode a suíte nos três (ver README) para pegar regressões.
 */
class CompatibilidadeBancoTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_busca_de_cliente_ignora_maiusculas_e_minusculas(): void
    {
        [$tenant] = $this->signInTenant();
        $this->makeClient($tenant, ['name' => 'Maria Souza', 'phone' => '11911112222']);

        $this->get('/clients?search=maria')->assertOk()->assertSee('Maria Souza');
        $this->get('/clients?search=SOUZA')->assertOk()->assertSee('Maria Souza');
        $this->getJson('/clients/search?q=mArIa')->assertOk()->assertJsonFragment(['name' => 'Maria Souza']);
    }

    public function test_busca_de_profissional_ignora_maiusculas_e_minusculas(): void
    {
        [$tenant] = $this->signInTenant();
        $this->makeProfessional($tenant, ['name' => 'Carlos Barbeiro']);

        $this->get('/professionals?search=carlos')->assertOk()->assertSee('Carlos Barbeiro');
    }

    public function test_ids_invalidos_viram_erro_de_validacao_e_nao_erro_500(): void
    {
        [$tenant] = $this->signInTenant();
        $prof = $this->makeProfessional($tenant);

        $this->postJson('/orders', ['client_id' => 'lixo'])->assertStatus(422);
        $this->postJson('/appointments', [
            'client_id' => 'lixo', 'professional_id' => 'lixo', 'date' => now()->toDateString(),
            'start_time' => '10:00', 'end_time' => '11:00',
        ])->assertStatus(422);
        $this->postJson('/expenses', [
            'description' => 'x', 'amount' => 1, 'payment_type' => 'one_time',
            'due_date' => now()->toDateString(), 'category_id' => 'lixo',
        ])->assertStatus(422);

        $this->postJson("/professionals/{$prof->id}/commissions/pay", [
            'period_type' => 'accumulated', 'date_to' => now()->toDateString(),
            'item_ids' => ['lixo'], 'voucher_ids' => ['lixo'],
        ])->assertStatus(422);

        $this->postJson("/booking-link/professionals/{$prof->id}/services", ['service_ids' => ['lixo']])->assertStatus(422);
    }

    public function test_rotas_com_id_invalido_na_url_retornam_404(): void
    {
        $this->signInTenant();

        $this->getJson('/orders/lixo/data')->assertNotFound();
        $this->deleteJson('/clients/lixo')->assertNotFound();
        $this->getJson('/professionals/lixo/commissions/detail')->assertNotFound();
    }

    public function test_comissao_personalizada_com_chave_invalida_e_ignorada_sem_erro(): void
    {
        [$tenant] = $this->signInTenant();
        $servico = $this->makeService($tenant);

        $this->post('/professionals', [
            'name' => 'Novo', 'custom_commissions' => ['lixo' => '10', $servico->id => '20'],
        ])->assertRedirect();

        $prof = \App\Models\Professional::where('name', 'Novo')->firstOrFail();
        $this->assertSame([$servico->id], $prof->services()->pluck('services.id')->all());
    }

    public function test_agendamento_publico_com_id_invalido_retorna_422(): void
    {
        $tenant = $this->makeTenant(['booking_slug' => 'barbearia-x']);

        $this->getJson('/agendar/barbearia-x/slots?professional_id=lixo&date=2030-01-01&service_ids[]=lixo')->assertStatus(422);
    }

    public function test_painel_admin_com_afiliados_comissoes_e_assinaturas_renderiza(): void
    {
        $tenant = $this->makeTenant(['plan_status' => 'active']);
        $admin  = $this->makeUser($tenant);
        $admin->forceFill(['is_admin' => true])->save();

        $afiliado = Affiliate::create(['name' => 'Afiliado', 'email' => 'af@example.com', 'code' => 'AFI10', 'commission_pct' => 10, 'discount_pct' => 10]);
        foreach (['pending' => 'p1', 'paid' => 'p2'] as $status => $pagamento) {
            AffiliateCommission::create([
                'affiliate_id' => $afiliado->id, 'tenant_id' => $tenant->id, 'subscription_amount' => 57.9,
                'discount_amount' => 0, 'charged_amount' => 57.9, 'commission_amount' => 5.79,
                'gateway' => 'stripe', 'gateway_payment_id' => $pagamento, 'period' => '2026-09', 'status' => $status,
            ]);
        }
        Subscription::create(['tenant_id' => $tenant->id, 'gateway' => 'stripe', 'status' => 'active', 'current_period_end' => now()->addMonth()]);

        $this->actingAs($admin);
        $this->get('/admin')->assertOk();
        $this->get('/admin/affiliates')->assertOk();
        $this->get("/admin/affiliates/{$afiliado->id}")->assertOk();
    }

    public function test_assinatura_aceita_todos_os_status_usados_pelo_codigo(): void
    {
        $tenant = $this->makeTenant();

        foreach (['trialing', 'active', 'pending', 'past_due', 'cancelled'] as $i => $status) {
            $sub = Subscription::create(['tenant_id' => $tenant->id, 'gateway' => $i % 2 ? 'stripe' : 'mercadopago', 'status' => $status]);
            $this->assertSame($status, $sub->fresh()->status);
        }
    }

    public function test_tenant_retorna_a_assinatura_mais_recente(): void
    {
        $tenant = $this->makeTenant();
        Subscription::create(['tenant_id' => $tenant->id, 'gateway' => 'mercadopago', 'status' => 'cancelled']);
        $recente = Subscription::create(['tenant_id' => $tenant->id, 'gateway' => 'stripe', 'status' => 'active']);
        $recente->forceFill(['updated_at' => now()->addMinute()])->save();

        $this->assertSame($recente->id, $tenant->fresh()->subscription->id);
    }
}
