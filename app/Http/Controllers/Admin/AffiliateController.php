<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Affiliate;
use App\Models\AffiliateCommission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AffiliateController extends Controller
{
    public function index()
    {
        $affiliates = Affiliate::withoutGlobalScopes()
            ->withCount('tenants')
            ->with(['commissions' => fn($q) => $q->where('status', 'pending')])
            ->orderByDesc('created_at')
            ->get()
            ->map(function (Affiliate $affiliate) {
                $affiliate->active_tenants_count = $affiliate->tenants()
                    ->whereIn('plan_status', ['active', 'trial'])
                    ->count();
                $affiliate->paying_tenants_count = $affiliate->tenants()
                    ->where('plan_status', 'active')
                    ->count();
                $affiliate->pending_commission   = $affiliate->commissions
                    ->sum('commission_amount');
                $affiliate->total_commission     = (float) AffiliateCommission::where('affiliate_id', $affiliate->id)
                    ->sum('commission_amount');
                return $affiliate;
            });

        $stats = [
            'total_affiliates'    => $affiliates->count(),
            'active_affiliates'   => $affiliates->where('is_active', true)->count(),
            'total_referred'      => $affiliates->sum('tenants_count'),
            'total_pending'       => $affiliates->sum('pending_commission'),
        ];

        return view('admin.affiliates.index', compact('affiliates', 'stats'));
    }

    public function show(Affiliate $affiliate)
    {
        $tenants = $affiliate->tenants()
            ->withoutGlobalScopes()
            ->orderByDesc('created_at')
            ->get();

        $commissionsByPeriod = AffiliateCommission::where('affiliate_id', $affiliate->id)
            ->selectRaw("period, COUNT(*) as payment_count, SUM(commission_amount) as total, SUM(CASE WHEN status = 'paid' THEN commission_amount ELSE 0 END) as paid, SUM(CASE WHEN status = 'pending' THEN commission_amount ELSE 0 END) as pending")
            ->groupBy('period')
            ->orderByDesc('period')
            ->get();

        $pendingTotal = AffiliateCommission::where('affiliate_id', $affiliate->id)
            ->where('status', 'pending')
            ->sum('commission_amount');

        return view('admin.affiliates.show', compact('affiliate', 'tenants', 'commissionsByPeriod', 'pendingTotal'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'email'          => ['required', 'email'],
            'whatsapp'       => ['nullable', 'string', 'max:20'],
            'code'           => ['required', 'string', 'max:30', 'unique:affiliates,code', 'regex:/^[A-Z0-9_-]+$/'],
            'commission_pct' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'discount_pct'   => ['required', 'numeric', 'min:0.01', 'max:100'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $data['code'] = strtoupper($data['code']);

        Affiliate::create($data);

        return redirect()->route('admin.affiliates.index')->with('success', 'Afiliado criado com sucesso.');
    }

    public function update(Request $request, Affiliate $affiliate)
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:150'],
            'email'          => ['required', 'email'],
            'whatsapp'       => ['nullable', 'string', 'max:20'],
            'commission_pct' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'discount_pct'   => ['required', 'numeric', 'min:0.01', 'max:100'],
            'is_active'      => ['boolean'],
            'notes'          => ['nullable', 'string', 'max:500'],
        ]);

        $affiliate->update($data);

        return response()->json(['ok' => true]);
    }

    public function toggleActive(Affiliate $affiliate)
    {
        $affiliate->update(['is_active' => ! $affiliate->is_active]);

        return response()->json(['ok' => true, 'is_active' => $affiliate->is_active]);
    }

    public function markPaid(Affiliate $affiliate)
    {
        $count = AffiliateCommission::where('affiliate_id', $affiliate->id)
            ->where('status', 'pending')
            ->update(['status' => 'paid', 'paid_at' => now()]);

        return response()->json(['ok' => true, 'count' => $count]);
    }
}
