<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    // Página pública do salão: lista serviços por categoria
    public function show(string $slug)
    {
        $tenant = $this->resolveTenant($slug);

        $professionalIds = Professional::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('show_on_booking', true)
            ->pluck('id');

        $services = Service::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('active', true)
            ->whereHas('professionals', fn($q) => $q->whereIn('professionals.id', $professionalIds))
            ->with('category')
            ->orderBy('name')
            ->get()
            ->groupBy(fn($s) => $s->category?->name ?? 'Outros');

        $client = $this->currentClient($tenant);

        return view('public.booking.show', compact('tenant', 'services', 'client'));
    }

    // Profissionais habilitados que executam todos os serviços escolhidos
    public function professionals(string $slug, Request $request)
    {
        $tenant = $this->resolveTenant($slug);

        $data = $request->validate([
            'service_ids'   => ['required', 'array', 'min:1'],
            'service_ids.*' => ['uuid'],
        ]);

        $serviceIds = $data['service_ids'];

        $professionals = Professional::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('show_on_booking', true)
            ->where(function ($q) use ($serviceIds) {
                foreach ($serviceIds as $id) {
                    $q->whereHas('services', fn($sq) => $sq->where('services.id', $id));
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'photo', 'specialty']);

        return response()->json([
            'professionals' => $professionals->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->name,
                'photo'     => $p->photo ? \Storage::url($p->photo) : null,
                'specialty' => $p->specialty,
            ]),
        ]);
    }

    // Slots disponíveis para profissional + serviços + data
    public function slots(string $slug, Request $request)
    {
        $tenant = $this->resolveTenant($slug);

        $data = $request->validate([
            'service_ids'     => ['required', 'array', 'min:1'],
            'service_ids.*'   => ['uuid'],
            'professional_id' => ['required', 'uuid'],
            'date'            => ['required', 'date'],
        ]);

        $professional = Professional::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('show_on_booking', true)
            ->findOrFail($data['professional_id']);

        $duration = (int) Service::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $data['service_ids'])
            ->sum('duration_min');

        if ($duration === 0) {
            return response()->json(['slots' => []]);
        }

        $date = Carbon::parse($data['date'])->startOfDay();
        $step = (int) ($tenant->booking_interval_min ?? 30);

        // Limites do dia (work_schedule do profissional ou agenda do tenant)
        [$startHour, $startMin, $endHour, $endMin] = $this->dayBounds($tenant, $professional, $date);

        if ($startHour === null) {
            return response()->json(['slots' => []]);
        }

        $dayStart = $date->copy()->setTime($startHour, $startMin);
        $dayEnd   = $date->copy()->setTime($endHour, $endMin);
        $now      = now();

        $existing = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('professional_id', $professional->id)
            ->whereDate('date', $date->toDateString())
            ->where('status', '!=', 'cancelled')
            ->get(['start_time', 'end_time']);

        $slots = [];
        $cursor = $dayStart->copy();
        while ($cursor->copy()->addMinutes($duration)->lte($dayEnd)) {
            $slotStart = $cursor->copy();
            $slotEnd   = $cursor->copy()->addMinutes($duration);

            $isPast = $slotStart->lte($now);

            $hasConflict = $existing->contains(function ($apt) use ($slotStart, $slotEnd, $date, $step) {
                $aStart = $date->copy()->setTimeFromTimeString(substr($apt->start_time, 0, 5));
                $aEnd   = $date->copy()->setTimeFromTimeString(substr($apt->end_time, 0, 5))->addMinutes($step);
                return $slotStart->lt($aEnd) && $slotEnd->gt($aStart);
            });

            if (! $isPast && ! $hasConflict) {
                $slots[] = $slotStart->format('H:i');
            }

            $cursor->addMinutes($step);
        }

        return response()->json(['slots' => $slots]);
    }

    // Cria os agendamentos (um por serviço, encadeados)
    public function book(string $slug, Request $request)
    {
        $tenant = $this->resolveTenant($slug);
        $client = $this->currentClient($tenant);

        if (! $client) {
            return response()->json(['error' => 'É preciso identificar-se pelo WhatsApp.'], 401);
        }

        $data = $request->validate([
            'service_ids'     => ['required', 'array', 'min:1'],
            'service_ids.*'   => ['uuid'],
            'professional_id' => ['required', 'uuid'],
            'date'            => ['required', 'date', 'after_or_equal:today'],
            'start_time'      => ['required', 'date_format:H:i'],
        ]);

        $professional = Professional::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('show_on_booking', true)
            ->findOrFail($data['professional_id']);

        // Load services preserving the client's selected order
        $servicesById = Service::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $data['service_ids'])
            ->get()
            ->keyBy('id');

        $services = collect($data['service_ids'])
            ->map(fn ($id) => $servicesById->get($id))
            ->filter()
            ->values();

        $totalDuration = (int) $services->sum('duration_min');
        $start         = Carbon::parse($data['date'] . ' ' . $data['start_time']);
        $end           = $start->copy()->addMinutes($totalDuration);

        // Validate that the full block is conflict-free
        $conflict = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('professional_id', $professional->id)
            ->whereDate('date', $start->toDateString())
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) use ($start, $end) {
                $q->whereRaw('? < end_time AND ? > start_time', [$start->format('H:i'), $end->format('H:i')]);
            })
            ->exists();

        if ($conflict) {
            return response()->json(['error' => 'Esse horário acabou de ser ocupado. Escolha outro.'], 422);
        }

        $groupId = (string) Str::uuid();
        $cursor  = $start->copy();
        $created = [];

        foreach ($services as $service) {
            $aptEnd = $cursor->copy()->addMinutes((int) $service->duration_min);

            $appointment = Appointment::create([
                'tenant_id'           => $tenant->id,
                'client_id'           => $client->id,
                'professional_id'     => $professional->id,
                'service_id'          => $service->id,
                'date'                => $cursor->toDateString(),
                'start_time'          => $cursor->format('H:i'),
                'end_time'            => $aptEnd->format('H:i'),
                'status'              => 'scheduled',
                'source'              => 'public_link',
                'recurrence'          => 'none',
                'recurrence_group_id' => $groupId,
                'create_order'        => false,
            ]);

            $created[] = $appointment->id;
            $cursor    = $aptEnd;
        }

        return response()->json([
            'ok'              => true,
            'appointment_ids' => $created,
        ]);
    }

    // Histórico de agendamentos do cliente logado
    public function myAppointments(string $slug)
    {
        $tenant = $this->resolveTenant($slug);
        $client = $this->currentClient($tenant);

        if (! $client) {
            return redirect()->route('public.booking.show', $slug);
        }

        $appointments = Appointment::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('client_id', $client->id)
            ->with(['service', 'professional'])
            ->orderByDesc('date')
            ->orderByDesc('start_time')
            ->limit(50)
            ->get();

        return view('public.booking.my-appointments', compact('tenant', 'client', 'appointments'));
    }

    public function cancelAppointment(string $slug, Appointment $appointment)
    {
        $tenant = $this->resolveTenant($slug);
        $client = $this->currentClient($tenant);

        abort_unless(
            $client && $appointment->tenant_id === $tenant->id && $appointment->client_id === $client->id,
            403
        );

        if (in_array($appointment->status, ['scheduled', 'confirmed'])) {
            $appointment->update(['status' => 'cancelled']);
        }

        return back();
    }

    // ── Helpers ────────────────────────────────────────────────────
    protected function resolveTenant(string $slug): Tenant
    {
        $tenant = Tenant::where('booking_slug', $slug)
            ->where('booking_active', true)
            ->first();

        abort_if(! $tenant, 404, 'Página não encontrada.');

        return $tenant;
    }

    protected function currentClient(Tenant $tenant): ?Client
    {
        $clientId = session("booking_client.{$tenant->id}");

        if (! $clientId) return null;

        return Client::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->find($clientId);
    }

    protected function dayBounds(Tenant $tenant, Professional $professional, Carbon $date): array
    {
        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        $dayKey   = $dayNames[$date->dayOfWeek];

        $schedule = $professional->work_schedule ?? null;

        if (is_array($schedule) && isset($schedule[$dayKey])) {
            $day = $schedule[$dayKey];
            if (empty($day['enabled']) && ($day['enabled'] ?? null) !== true) {
                return [null, null, null, null];
            }
            $start = explode(':', $day['start'] ?? '08:00');
            $end   = explode(':', $day['end']   ?? '18:00');
            return [(int) $start[0], (int) ($start[1] ?? 0), (int) $end[0], (int) ($end[1] ?? 0)];
        }

        return [
            (int) ($tenant->agenda_start_hour ?? 8),
            0,
            (int) ($tenant->agenda_end_hour ?? 18),
            0,
        ];
    }
}
