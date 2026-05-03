<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BookingLinkController extends Controller
{
    public const ALLOWED_INTERVALS = [10, 15, 20, 30, 40, 45, 50, 60, 90, 120];

    public function index()
    {
        $tenant = auth()->user()->tenant;

        if (! $tenant->booking_slug) {
            $tenant->update(['booking_slug' => $this->generateUniqueSlug($tenant)]);
            $tenant->refresh();
        }

        $professionals = Professional::with(['services:id,name,category_id'])
            ->orderBy('name')
            ->get();

        $services = Service::where('active', true)
            ->with('category')
            ->orderBy('name')
            ->get();

        $recentAppointments = Appointment::with(['client', 'professional', 'service'])
            ->where('source', 'public_link')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('app.booking-link.index', compact(
            'tenant', 'professionals', 'services', 'recentAppointments'
        ));
    }

    public function update(Request $request)
    {
        $tenant = auth()->user()->tenant;

        $data = $request->validate([
            'banner'               => ['nullable', 'image', 'max:5120'],
            'banner_color'         => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'remove_banner'        => ['boolean'],
            'logo'                 => ['nullable', 'image', 'max:5120'],
            'booking_active'       => ['boolean'],
            'booking_interval_min' => ['required', 'integer', 'in:' . implode(',', self::ALLOWED_INTERVALS)],
            'booking_show_prices'  => ['boolean'],
        ]);

        $payload = [
            'booking_active'       => $request->boolean('booking_active'),
            'booking_interval_min' => (int) $data['booking_interval_min'],
            'booking_show_prices'  => $request->boolean('booking_show_prices'),
        ];

        if ($request->boolean('remove_banner')) {
            if ($tenant->banner) Storage::disk('public')->delete($tenant->banner);
            $payload['banner']               = null;
            $payload['booking_banner_color'] = null;
        }

        if ($request->hasFile('banner')) {
            if ($tenant->banner) Storage::disk('public')->delete($tenant->banner);
            $payload['banner']               = $request->file('banner')->store('banners', 'public');
            $payload['booking_banner_color'] = null;
        } elseif ($request->filled('banner_color')) {
            if ($tenant->banner) Storage::disk('public')->delete($tenant->banner);
            $payload['banner']               = null;
            $payload['booking_banner_color'] = $data['banner_color'];
        }

        if ($request->hasFile('logo')) {
            if ($tenant->logo) Storage::disk('public')->delete($tenant->logo);
            $payload['logo'] = $request->file('logo')->store('logos', 'public');
        }

        $tenant->update($payload);

        return back()->with('success', 'Configurações do link salvas!');
    }

    public function toggleProfessional(Professional $professional)
    {
        $professional->update(['show_on_booking' => ! $professional->show_on_booking]);

        return response()->json([
            'ok'              => true,
            'show_on_booking' => $professional->show_on_booking,
        ]);
    }

    public function updateProfessionalServices(Request $request, Professional $professional)
    {
        $data = $request->validate([
            'service_ids'   => ['array'],
            'service_ids.*' => ['uuid', 'exists:services,id'],
        ]);

        $current  = $professional->services()->get()->keyBy('id');
        $selected = collect($data['service_ids'] ?? []);

        $sync = [];
        foreach ($selected as $id) {
            $sync[$id] = [
                'commission_pct' => $current->get($id)?->pivot?->commission_pct,
            ];
        }

        $professional->services()->sync($sync);

        return response()->json(['ok' => true]);
    }

    private function generateUniqueSlug(Tenant $tenant): string
    {
        $base = Str::slug($tenant->name) ?: 'salao';
        $slug = $base;
        $i    = 2;

        while (Tenant::where('booking_slug', $slug)->where('id', '!=', $tenant->id)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
