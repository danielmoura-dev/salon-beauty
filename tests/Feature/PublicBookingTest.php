<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase, CreatesTenants;

    private Tenant $tenant;
    private Professional $prof;
    private Service $service;
    private string $base;
    private string $day = '2026-09-22'; // terça-feira

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-21 09:00:00'); // segunda-feira, 09:00

        $this->tenant  = $this->makeTenant(['booking_slug' => 'studio-x']);
        $this->prof    = $this->makeProfessional($this->tenant);
        $this->service = $this->makeService($this->tenant, ['duration_min' => 60]);
        $this->prof->services()->attach($this->service->id);
        $this->base = '/agendar/studio-x';
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function asClient(?Client $client = null): Client
    {
        $client ??= $this->makeClient($this->tenant, ['name' => 'Maria da Silva', 'phone' => '11988887777']);
        $this->withSession(["booking_client.{$this->tenant->id}" => $client->id]);

        return $client;
    }

    private function book(array $over = [])
    {
        return $this->postJson("{$this->base}/book", [
            'service_ids' => [$this->service->id], 'professional_id' => $this->prof->id,
            'date' => $this->day, 'start_time' => '10:00', ...$over,
        ]);
    }

    private function slots(array $over = []): array
    {
        return $this->getJson("{$this->base}/slots?" . http_build_query([
            'service_ids' => [$this->service->id], 'professional_id' => $this->prof->id, 'date' => $this->day, ...$over,
        ]))->assertOk()->json('slots');
    }

    // ---------- identificação do cliente ----------

    public function test_new_client_can_identify_and_book(): void
    {
        $this->postJson("{$this->base}/auth/register", ['name' => 'Joana', 'phone' => '(11) 97777-6666'])->assertOk();

        $client = Client::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->firstOrFail();
        $this->assertSame('11977776666', $client->phone);

        $this->book()->assertOk()->assertJson(['ok' => true]);
        $this->assertSame(1, Appointment::withoutGlobalScopes()->where('client_id', $client->id)->count());
    }

    public function test_phone_check_only_reveals_the_first_name(): void
    {
        $this->makeClient($this->tenant, ['name' => 'Maria da Silva', 'phone' => '11988887777']);

        $this->postJson("{$this->base}/auth/check", ['phone' => '(11) 98888-7777'])
            ->assertOk()
            ->assertExactJson(['exists' => true, 'name' => 'Maria']);
    }

    public function test_clients_of_other_salons_are_never_matched(): void
    {
        $other = $this->makeTenant();
        $this->makeClient($other, ['name' => 'Cliente Alheia', 'phone' => '11988887777']);

        $this->postJson("{$this->base}/auth/check", ['phone' => '11988887777'])->assertExactJson(['exists' => false]);
    }

    public function test_booking_requires_identification(): void
    {
        $this->book()->assertStatus(401);
        $this->assertSame(0, Appointment::withoutGlobalScopes()->count());
    }

    public function test_phone_lookup_is_rate_limited(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson("{$this->base}/auth/check", ['phone' => '1198888000' . $i])->assertOk();
        }

        $this->postJson("{$this->base}/auth/check", ['phone' => '11988880099'])->assertStatus(429);
    }

    // ---------- horários ----------

    public function test_slots_follow_the_working_day_and_service_duration(): void
    {
        $slots = $this->slots();

        $this->assertSame('08:00', $slots[0]);
        $this->assertSame('17:00', end($slots), 'serviço de 1h precisa terminar até as 18:00');
        $this->assertCount(19, $slots);
    }

    public function test_booked_time_and_buffer_disappear_from_slots(): void
    {
        $this->asClient();
        $this->book(['start_time' => '10:00'])->assertOk();

        $slots = $this->slots();

        $this->assertContains('09:00', $slots);
        $this->assertNotContains('09:30', $slots);
        $this->assertNotContains('10:00', $slots);
        $this->assertNotContains('11:00', $slots);
        $this->assertContains('11:30', $slots);
    }

    // ---------- reserva ----------

    public function test_booking_creates_a_scheduled_public_link_appointment(): void
    {
        $client = $this->asClient();

        $this->book()->assertOk();

        $apt = Appointment::withoutGlobalScopes()->firstOrFail();
        $this->assertSame($this->tenant->id, $apt->tenant_id);
        $this->assertSame($client->id, $apt->client_id);
        $this->assertSame('scheduled', $apt->status);
        $this->assertSame('public_link', $apt->source);
        $this->assertSame('10:00', substr($apt->start_time, 0, 5));
        $this->assertSame('11:00', substr($apt->end_time, 0, 5));
    }

    public function test_multiple_services_are_chained_back_to_back(): void
    {
        $this->asClient();
        $second = $this->makeService($this->tenant, ['duration_min' => 30]);
        $this->prof->services()->attach($second->id);

        $this->book(['service_ids' => [$this->service->id, $second->id]])->assertOk();

        $apts = Appointment::withoutGlobalScopes()->orderBy('start_time')->get();
        $this->assertCount(2, $apts);
        $this->assertSame(['10:00', '11:00'], [substr($apts[0]->start_time, 0, 5), substr($apts[1]->start_time, 0, 5)]);
        $this->assertSame('11:30', substr($apts[1]->end_time, 0, 5));
    }

    public function test_cannot_book_a_taken_slot(): void
    {
        $this->asClient();
        $this->book()->assertOk();

        $this->book()->assertStatus(422);
        $this->book(['start_time' => '10:30'])->assertStatus(422);
        $this->assertSame(1, Appointment::withoutGlobalScopes()->count());
    }

    public function test_cannot_book_outside_the_slot_grid_or_working_hours(): void
    {
        $this->asClient();

        $this->book(['start_time' => '03:00'])->assertStatus(422);
        $this->book(['start_time' => '07:30'])->assertStatus(422);
        $this->book(['start_time' => '17:30'])->assertStatus(422); // terminaria às 18:30
        $this->book(['start_time' => '10:15'])->assertStatus(422); // fora da grade de 30 min
        $this->assertSame(0, Appointment::withoutGlobalScopes()->count());
    }

    public function test_cannot_book_a_time_that_already_passed_today(): void
    {
        $this->asClient();
        Carbon::setTestNow('2026-09-21 12:00:00');

        $this->book(['date' => '2026-09-21', 'start_time' => '09:00'])->assertStatus(422);
        $this->book(['date' => '2026-09-21', 'start_time' => '14:00'])->assertOk();
    }

    public function test_cannot_book_too_far_in_the_future(): void
    {
        $this->asClient();

        $this->book(['date' => now()->addDays(400)->toDateString()])->assertStatus(422);
    }

    public function test_professional_must_perform_every_selected_service(): void
    {
        $this->asClient();
        $other = $this->makeService($this->tenant); // não vinculado ao profissional

        $this->book(['service_ids' => [$this->service->id, $other->id]])->assertStatus(422);
        $this->assertSame(0, Appointment::withoutGlobalScopes()->count());
    }

    public function test_inactive_and_foreign_services_are_rejected(): void
    {
        $this->asClient();
        $this->service->update(['active' => false]);
        $this->book()->assertStatus(422);

        $this->service->update(['active' => true]);
        $foreign = $this->makeService($this->makeTenant());
        $this->book(['service_ids' => [$foreign->id]])->assertStatus(422);
    }

    public function test_duplicate_service_ids_are_rejected(): void
    {
        $this->asClient();

        $this->book(['service_ids' => [$this->service->id, $this->service->id]])->assertStatus(422);
    }

    public function test_professional_from_another_salon_or_hidden_is_not_found(): void
    {
        $this->asClient();
        $foreign = $this->makeProfessional($this->makeTenant());
        $this->book(['professional_id' => $foreign->id])->assertNotFound();

        $this->prof->update(['show_on_booking' => false]);
        $this->book()->assertNotFound();
    }

    public function test_failed_booking_of_several_services_leaves_nothing_behind(): void
    {
        $this->asClient();
        $second = $this->makeService($this->tenant, ['duration_min' => 30]);
        $this->prof->services()->attach($second->id);

        $calls = 0;
        Appointment::creating(function () use (&$calls) {
            if (++$calls === 2) throw new \RuntimeException('falha simulada');
        });

        $this->book(['service_ids' => [$this->service->id, $second->id]])->assertStatus(500);

        $this->assertSame(0, Appointment::withoutGlobalScopes()->count());
    }

    public function test_inactive_booking_page_is_not_available(): void
    {
        $this->tenant->update(['booking_active' => false]);

        $this->get($this->base)->assertNotFound();
        $this->asClient();
        $this->book()->assertNotFound();
    }

    // ---------- meus agendamentos ----------

    public function test_client_can_cancel_only_their_own_appointment(): void
    {
        $client = $this->asClient();
        $this->book()->assertOk();
        $mine = Appointment::withoutGlobalScopes()->firstOrFail();

        $stranger = $this->makeClient($this->tenant);
        $theirs   = $this->makeFor(Appointment::class, $this->tenant, [
            'client_id' => $stranger->id, 'professional_id' => $this->prof->id, 'service_id' => $this->service->id,
            'date' => $this->day, 'start_time' => '14:00', 'end_time' => '15:00', 'status' => 'scheduled',
        ]);

        $this->post("{$this->base}/agendamentos/{$theirs->id}/cancel")->assertForbidden();
        $this->assertSame('scheduled', $theirs->fresh()->status);

        $this->post("{$this->base}/agendamentos/{$mine->id}/cancel")->assertRedirect();
        $this->assertSame('cancelled', $mine->fresh()->status);
    }

    public function test_my_appointments_lists_only_the_identified_clients_own(): void
    {
        $client = $this->asClient();
        $stranger = $this->makeClient($this->tenant, ['name' => 'Estranha Silva']);
        foreach ([[$client, '09:00'], [$stranger, '15:00']] as [$c, $time]) {
            $this->makeFor(Appointment::class, $this->tenant, [
                'client_id' => $c->id, 'professional_id' => $this->prof->id, 'service_id' => $this->service->id,
                'date' => $this->day, 'start_time' => $time, 'end_time' => '18:00', 'status' => 'scheduled',
            ]);
        }

        $this->get("{$this->base}/agendamentos")->assertOk()->assertSee('09:00')->assertDontSee('15:00');
    }
}
