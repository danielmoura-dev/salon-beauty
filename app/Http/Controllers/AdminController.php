<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\Professional;
use App\Models\Tenant;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        $tenants = Tenant::withoutGlobalScopes()
            ->with(['subscription'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Tenant $tenant) {
                $tenant->client_count       = Client::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
                $tenant->professional_count = Professional::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
                $tenant->order_count        = Order::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count();
                $tenant->revenue            = Order::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('status', 'closed')
                    ->sum('total');
                return $tenant;
            });

        $stats = [
            'total_tenants'  => $tenants->count(),
            'active_tenants' => $tenants->filter(fn($t) => $t->isActive())->count(),
            'trial_tenants'  => $tenants->where('plan_status', 'trial')->count(),
            'paid_tenants'   => $tenants->where('plan_status', 'active')->count(),
            'total_revenue'  => $tenants->sum('revenue'),
        ];

        return view('admin.dashboard', compact('tenants', 'stats'));
    }

    public function extendTrial(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        $base = ($tenant->trial_ends_at && $tenant->trial_ends_at->isFuture())
            ? $tenant->trial_ends_at->copy()
            : now();

        $newDate = $base->addDays((int) $data['days']);

        $tenant->update([
            'plan_status'   => 'trial',
            'trial_ends_at' => $newDate,
        ]);

        return response()->json([
            'ok'            => true,
            'trial_ends_at' => $tenant->fresh()->trial_ends_at->format('d/m/Y'),
        ]);
    }
}
