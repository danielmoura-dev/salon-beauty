<?php

namespace App\Http\Controllers;

use App\Models\CommissionPayment;
use App\Models\OrderItem;
use App\Models\Professional;
use App\Models\ProfessionalVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CommissionController extends Controller
{
    public function index()
    {
        $professionals = Professional::orderBy('name')
            ->withCount(['vouchers as pending_vouchers_count' => fn($q) => $q->whereNull('commission_payment_id')])
            ->get()
            ->map(function ($prof) {
                $items = OrderItem::where('professional_id', $prof->id)
                    ->whereNull('commission_paid_at')
                    ->where('has_commission', true)
                    ->with('order')
                    ->get()
                    ->filter(fn($i) => $i->order && $i->order->status === 'closed');

                $prof->pending_commission = $items->sum(fn($i) => $i->commissionValue());
                return $prof;
            });

        $history = CommissionPayment::with('professional')
            ->orderByDesc('created_at')
            ->get();

        return view('app.professionals.commissions', compact('professionals', 'history'));
    }

    public function detail(Request $request, Professional $professional)
    {
        $periodEnd = $request->date_to
            ? Carbon::parse($request->date_to)->endOfDay()
            : now()->endOfDay();

        $query = OrderItem::where('professional_id', $professional->id)
            ->whereNull('commission_paid_at')
            ->where('has_commission', true)
            ->whereHas('order', fn($q) => $q->where('status', 'closed'))
            ->with(['order.client', 'order.payments']);

        if ($request->date_from) {
            $query->whereHas('order', fn($q) => $q->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay()));
        }

        $query->whereHas('order', fn($q) => $q->where('created_at', '<=', $periodEnd));

        $items = $query->get();

        $format = function ($item) {
            $order = $item->order;
            $firstPayment  = $order->payments->first();
            $paymentMethod = $firstPayment ? $firstPayment->methodLabel() : '—';
            return [
                'id'             => $item->id,
                'date'           => $order->created_at->format('d/m/Y'),
                'client'         => $order->client?->name ?? '—',
                'description'    => $item->description,
                'payment_method' => $paymentMethod,
                'commission_pct' => (float) $item->commission_pct,
                'value'          => $item->commissionValue(),
                'subtotal'       => $item->subtotal(),
            ];
        };

        $services = $items->where('type', 'service');
        $products = $items->where('type', 'product');
        $others   = $items->where('type', 'other');

        $vouchers = ProfessionalVoucher::where('professional_id', $professional->id)
            ->whereNull('commission_payment_id')
            ->orderBy('issued_at')
            ->get();

        $totalServices = $services->sum(fn($i) => $i->commissionValue());
        $totalProducts = $products->sum(fn($i) => $i->commissionValue());
        $totalOthers   = $others->sum(fn($i) => $i->commissionValue());
        $totalVouchers = $vouchers->sum('amount');
        $netAmount     = $totalServices + $totalProducts + $totalOthers - $totalVouchers;

        return response()->json([
            'total_services' => $totalServices,
            'total_products' => $totalProducts,
            'total_others'   => $totalOthers,
            'total_vouchers' => $totalVouchers,
            'net_amount'     => max(0, $netAmount),
            'services'       => $services->map($format)->values(),
            'products'       => $products->map($format)->values(),
            'others'         => $others->map($format)->values(),
            'vouchers'       => $vouchers->map(fn($v) => [
                'id'          => $v->id,
                'description' => $v->description,
                'issued_at'   => $v->issued_at->format('d/m/Y'),
                'amount'      => (float) $v->amount,
            ])->values(),
        ]);
    }

    public function pay(Request $request, Professional $professional)
    {
        $data = $request->validate([
            'period_type' => ['required', 'in:accumulated,custom'],
            'date_from'   => ['nullable', 'date', 'required_if:period_type,custom'],
            'date_to'     => ['required', 'date'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ]);

        $periodEnd   = Carbon::parse($data['date_to'])->endOfDay();
        $periodStart = $data['period_type'] === 'custom' && $data['date_from']
            ? Carbon::parse($data['date_from'])->startOfDay()
            : null;

        $query = OrderItem::where('professional_id', $professional->id)
            ->whereNull('commission_paid_at')
            ->where('has_commission', true)
            ->whereHas('order', fn($q) => $q->where('status', 'closed'))
            ->whereHas('order', fn($q) => $q->where('created_at', '<=', $periodEnd));

        if ($periodStart) {
            $query->whereHas('order', fn($q) => $q->where('created_at', '>=', $periodStart));
        }

        $items = $query->get();

        $totalServices = $items->where('type', 'service')->sum(fn($i) => $i->commissionValue());
        $totalProducts = $items->where('type', 'product')->sum(fn($i) => $i->commissionValue());
        $totalOthers   = $items->where('type', 'other')->sum(fn($i) => $i->commissionValue());

        $vouchers = ProfessionalVoucher::where('professional_id', $professional->id)
            ->whereNull('commission_payment_id')
            ->get();

        $totalVouchers = $vouchers->sum('amount');
        $netAmount     = max(0, $totalServices + $totalProducts + $totalOthers - $totalVouchers);

        $payment = CommissionPayment::create([
            'tenant_id'       => auth()->user()->tenant_id,
            'professional_id' => $professional->id,
            'period_start'    => $periodStart?->toDateString(),
            'period_end'      => Carbon::parse($data['date_to'])->toDateString(),
            'total_services'  => $totalServices,
            'total_products'  => $totalProducts,
            'total_others'    => $totalOthers,
            'total_vouchers'  => $totalVouchers,
            'net_amount'      => $netAmount,
            'notes'           => $data['notes'] ?? null,
        ]);

        $items->each(fn($item) => $item->update([
            'commission_paid_at'    => now(),
            'commission_payment_id' => $payment->id,
        ]));

        $vouchers->each(fn($v) => $v->update(['commission_payment_id' => $payment->id]));

        return back()->with('success', "Pagamento de R$ " . number_format($netAmount, 2, ',', '.') . " registrado para {$professional->name}!");
    }
}
