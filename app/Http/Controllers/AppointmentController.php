<?php

namespace App\Http\Controllers;

use App\Rules\TenantExists;
use App\Models\Appointment;
use App\Models\Category;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Professional;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request)
    {
        $date = $request->date
            ? Carbon::parse($request->date)
            : Carbon::today();

        // Profissionais visíveis na agenda
        $professionals = Professional::where('show_on_agenda', true)
            ->orderBy('name')
            ->get();

        // Agendamentos do dia, agrupados por profissional
        $appointments = Appointment::with(['client', 'professional', 'service', 'order.items'])
            ->whereDate('date', $date)
            ->whereIn('professional_id', $professionals->pluck('id'))

            ->get()
            ->groupBy('professional_id');

        // Dados para os modais
        $clients    = Client::orderBy('name')->get(['id', 'name', 'phone']);
        $services   = Service::where('active', true)->orderBy('name')->get(['id', 'name', 'price', 'duration_min']);
        $categories = Category::where('type', 'service')->orderBy('name')->get(['id', 'name']);

        $tenant    = auth()->user()->tenant;
        $startHour = $tenant->agenda_start_hour ?? 8;
        $endHour   = $tenant->agenda_end_hour   ?? 22;

        $dayNames  = ['sunday','monday','tuesday','wednesday','thursday','friday','saturday'];
        $dayOfWeek = $dayNames[$date->dayOfWeek];

        // Gera slots de 30 em 30 minutos (do horário de início até meia-noite)
        $slots = [];
        for ($h = $startHour; $h < 24; $h++) {
            $slots[] = sprintf('%02d:00', $h);
            $slots[] = sprintf('%02d:30', $h);
        }

        return view('app.agenda.index', compact(
            'date', 'professionals', 'appointments',
            'clients', 'services', 'categories', 'slots', 'startHour', 'endHour', 'dayOfWeek'
        ));
    }

    public function store(Request $request)
    {
        // Normaliza horários para H:i (remove segundos se o browser enviar H:i:s)
        $request->merge([
            'start_time' => substr($request->input('start_time', ''), 0, 5),
            'end_time'   => substr($request->input('end_time', ''), 0, 5),
        ]);

        $data = $request->validate([
            'client_id'       => ['required', 'uuid', new TenantExists('clients')],
            'professional_id' => ['required', 'uuid', new TenantExists('professionals')],
            'service_id'      => ['nullable', 'uuid', new TenantExists('services')],
            'service_ids'     => ['nullable', 'array'],
            'service_ids.*'   => ['uuid', new TenantExists('services')],
            'date'            => ['required', 'date'],
            'start_time'      => ['required', 'date_format:H:i'],
            'end_time'        => ['required', 'date_format:H:i', 'after:start_time'],
            'recurrence'      => ['in:none,weekly,biweekly,monthly'],
            'create_order'    => ['boolean'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ]);

        // Resolve list of service IDs (prefer service_ids array, fall back to single service_id)
        $serviceIds = $data['service_ids'] ?? ($data['service_id'] ? [$data['service_id']] : []);
        $primaryServiceId = $serviceIds[0] ?? null;

        $recurrenceGroupId = Str::uuid();
        $dates = $this->buildRecurrenceDates($data['date'], $data['recurrence'] ?? 'none');

        DB::transaction(function () use ($dates, $data, $serviceIds, $primaryServiceId, $recurrenceGroupId, $request) {
            foreach ($dates as $date) {
                $appointment = Appointment::create([
                    ...$data,
                    'service_id'          => $primaryServiceId,
                    'date'                => $date,
                    'recurrence_group_id' => $recurrenceGroupId,
                    'create_order'        => $request->boolean('create_order', true),
                ]);

                // Cria comanda automaticamente se solicitado
                if ($appointment->create_order) {
                    $services = Service::whereIn('id', $serviceIds)->get();
                    $total = $services->sum('price');

                    $order = Order::create([
                        'client_id'      => $data['client_id'],
                        'appointment_id' => $appointment->id,
                        'status'         => 'open',
                        'total'          => $total,
                    ]);

                    foreach ($services as $service) {
                        OrderItem::create([
                            'order_id'        => $order->id,
                            'professional_id' => $data['professional_id'],
                            'type'            => 'service',
                            'description'     => $service->name,
                            'qty'             => 1,
                            'unit_price'      => $service->price,
                            'commission_pct'  => $service->commission_pct ?? 0,
                            'has_commission'  => ($service->commission_pct ?? 0) > 0,
                        ]);
                    }

                    $appointment->update(['order_id' => $order->id]);
                }
            }
        });

        return response()->json(['success' => true]);
    }

    public function update(Request $request, Appointment $appointment)
    {
        // Normaliza horários para H:i (remove segundos se o browser enviar H:i:s)
        if ($request->has('start_time')) {
            $request->merge(['start_time' => substr($request->input('start_time'), 0, 5)]);
        }
        if ($request->has('end_time')) {
            $request->merge(['end_time' => substr($request->input('end_time'), 0, 5)]);
        }

        $data = $request->validate([
            'client_id'       => ['sometimes', 'uuid', new TenantExists('clients')],
            'professional_id' => ['sometimes', 'uuid', new TenantExists('professionals')],
            'service_id'      => ['sometimes', 'uuid', new TenantExists('services')],
            'date'            => ['sometimes', 'date'],
            'start_time'      => ['sometimes', 'date_format:H:i'],
            'end_time'        => ['sometimes', 'date_format:H:i'],
            'status'          => ['sometimes', 'in:scheduled,confirmed,completed,cancelled'],
            'recurrence'      => ['sometimes', 'in:none,weekly,biweekly,monthly'],
            'notes'           => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($appointment, $data) {
            $oldStatus = $appointment->status;
            $appointment->update($data);

            // Sincroniza status da comanda ao mudar status do agendamento
            if (isset($data['status']) && $data['status'] !== $oldStatus && $appointment->order_id) {
                $order = $appointment->order;
                if ($order) {
                    if ($data['status'] === 'cancelled') {
                        // Cancela a comanda (se não estiver já fechada/paga)
                        if ($order->status !== 'closed') {
                            $order->update(['status' => 'cancelled']);
                        }
                    } elseif ($order->status === 'cancelled') {
                        // Restaura a comanda cancelada ao reativar o agendamento
                        $order->update(['status' => 'open']);
                    }
                }
            }
        });

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, Appointment $appointment)
    {
        $deleteAll = $request->boolean('delete_recurrence');

        DB::transaction(function () use ($appointment, $deleteAll) {
            if ($deleteAll && $appointment->recurrence_group_id) {
                $appointments = Appointment::where('recurrence_group_id', $appointment->recurrence_group_id)
                    ->where('date', '>=', today())
                    ->get();

                foreach ($appointments as $apt) {
                    $this->deleteOrderForAppointment($apt);
                    $apt->delete();
                }
            } else {
                $this->deleteOrderForAppointment($appointment);
                $appointment->delete();
            }
        });

        return response()->json(['success' => true]);
    }

    private function deleteOrderForAppointment(Appointment $appointment): void
    {
        if (! $appointment->order_id) return;

        $order = $appointment->order;
        if (! $order) return;

        // devolve estoque e reverte o saldo do cliente, em vez de apenas apagar as linhas
        $this->orders->delete($order);
    }

    // Gera as datas de recorrência (máximo 12 ocorrências)
    private function buildRecurrenceDates(string $startDate, string $recurrence): array
    {
        $date  = Carbon::parse($startDate);
        $dates = [$date->toDateString()];

        if ($recurrence === 'none') return $dates;

        $intervals = ['weekly' => 1, 'biweekly' => 2, 'monthly' => 4];
        $weeks     = $intervals[$recurrence] ?? 1;

        for ($i = 1; $i < 12; $i++) {
            $dates[] = $date->copy()->addWeeks($weeks * $i)->toDateString();
        }

        return $dates;
    }
}