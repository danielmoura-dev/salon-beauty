<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class ListingsAndFiltersTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    public function test_client_search_never_leaks_other_tenants_clients(): void
    {
        $victim = $this->makeClient($this->makeTenant(), ['name' => 'Beatriz Alheia', 'phone' => '11955554444']);
        [$tenant] = $this->signInTenant();
        $this->makeClient($tenant, ['name' => 'Beatriz Minha', 'phone' => '11922221111']);

        $this->get('/clients?search=Beatriz')->assertOk()->assertSee('Beatriz Minha')->assertDontSee('Beatriz Alheia');
        $this->get('/clients?search=' . $victim->phone)->assertOk()->assertDontSee('Beatriz Alheia');

        $this->getJson('/clients/search?q=Beatriz')->assertOk()->assertJsonMissing(['name' => 'Beatriz Alheia']);
    }

    public function test_search_and_debtor_filter_apply_together(): void
    {
        [$tenant] = $this->signInTenant();
        $this->makeClient($tenant, ['name' => 'Carla Devedora', 'phone' => '11900000001', 'balance' => -50]);
        $this->makeClient($tenant, ['name' => 'Carla Em Dia', 'phone' => '11900000002', 'balance' => 0]);

        $this->get('/clients?search=Carla&filter=debtors')
            ->assertOk()
            ->assertSee('Carla Devedora')
            ->assertDontSee('Carla Em Dia');
    }

    public static function pagesWithDateParams(): array
    {
        return [
            'agenda date'        => ['/agenda?date=isso-nao-e-data'],
            'orders date'        => ['/orders?date=32/13/2026'],
            'reports day'        => ['/reports?mode=day&date=abc'],
            'reports month'      => ['/reports?mode=month&month=zzzz'],
            'expenses month'     => ['/expenses?month=2026-99'],
        ];
    }

    #[DataProvider('pagesWithDateParams')]
    public function test_invalid_date_params_fall_back_instead_of_erroring(string $url): void
    {
        $this->signInTenant();

        $this->get($url)->assertOk();
    }

    public function test_commission_detail_tolerates_invalid_dates(): void
    {
        [$tenant] = $this->signInTenant();
        $prof = $this->makeProfessional($tenant);

        $this->getJson("/professionals/{$prof->id}/commissions/detail?date_from=abc&date_to=xyz")->assertOk();
    }
}
