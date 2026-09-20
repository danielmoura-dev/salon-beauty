<?php

namespace App\Http\Controllers;

use App\Support\Dates;
use App\Models\CommissionPayment;
use App\Models\OrderItem;
use App\Models\Professional;
use App\Models\ProfessionalVoucher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        $periodEnd = Dates::parse($request->date_to, now())->endOfDay();
        $periodStart = $request->date_from ? Dates::parse($request->date_from)->startOfDay() : null;

        $query = OrderItem::where('professional_id', $professional->id)
            ->whereNull('commission_paid_at')
            ->where('has_commission', true)
            ->whereHas('order', fn($q) => $q->where('status', 'closed'))
            ->with(['order.client', 'order.payments']);

        if ($periodStart) {
            $query->whereHas('order', fn($q) => $q->where('created_at', '>=', $periodStart));
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
            'period_type'   => ['required', 'in:accumulated,custom'],
            'date_from'     => ['nullable', 'date', 'required_if:period_type,custom'],
            'date_to'       => ['required', 'date'],
            'notes'         => ['nullable', 'string', 'max:500'],
            'item_ids'      => ['nullable', 'array'],
            'item_ids.*'    => ['uuid'],
            'voucher_ids'   => ['nullable', 'array'],
            'voucher_ids.*' => ['uuid'],
        ]);

        $periodStart = $data['period_type'] === 'custom' && ($data['date_from'] ?? null)
            ? Carbon::parse($data['date_from'])->toDateString()
            : null;

        $payment = DB::transaction(function () use ($data, $professional, $periodStart) {
            // Trava os itens/vales: um clique duplo não pode pagar a mesma comissão duas vezes
            // e só entram itens elegíveis (mesmo critério da tela: comanda fechada, com comissão).
            $items = OrderItem::whereIn('id', $data['item_ids'] ?? [])
                ->where('professional_id', $professional->id)
                ->where('has_commission', true)
                ->whereNull('commission_paid_at')
                ->whereHas('order', fn($q) => $q->where('status', 'closed'))
                ->lockForUpdate()
                ->get();

            $vouchers = ProfessionalVoucher::whereIn('id', $data['voucher_ids'] ?? [])
                ->where('professional_id', $professional->id)
                ->whereNull('commission_payment_id')
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty() && $vouchers->isEmpty()) {
                return null;
            }

            $totalServices = $items->where('type', 'service')->sum(fn($i) => $i->commissionValue());
            $totalProducts = $items->where('type', 'product')->sum(fn($i) => $i->commissionValue());
            $totalOthers   = $items->where('type', 'other')->sum(fn($i) => $i->commissionValue());
            $totalVouchers = $vouchers->sum('amount');
            $netAmount     = max(0, $totalServices + $totalProducts + $totalOthers - $totalVouchers);

            $payment = CommissionPayment::create([
                'tenant_id'       => auth()->user()->tenant_id,
                'professional_id' => $professional->id,
                'period_start'    => $periodStart,
                'period_end'      => $data['date_to'],
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

            return $payment;
        });

        if (! $payment) {
            return back()->withErrors(['pay' => 'Nenhuma comissão ou vale pendente para pagar.']);
        }

        return back()->with('success', "Pagamento de R$ " . number_format($payment->net_amount, 2, ',', '.') . " registrado para {$professional->name}!");
    }

    public function cancel(CommissionPayment $payment)
    {
        $professional = $payment->professional;

        DB::transaction(function () use ($payment) {
            // Reverte itens de pedido para pendente
            OrderItem::where('commission_payment_id', $payment->id)
                ->update([
                    'commission_paid_at'    => null,
                    'commission_payment_id' => null,
                ]);

            // Reverte vales para pendente
            ProfessionalVoucher::where('commission_payment_id', $payment->id)
                ->update(['commission_payment_id' => null]);

            $payment->delete();
        });

        return back()->with('success', "Pagamento cancelado. Valores de {$professional->name} voltaram para pendente.");
    }
}
