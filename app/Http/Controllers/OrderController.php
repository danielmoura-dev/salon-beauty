<?php

namespace App\Http\Controllers;

use App\Support\Dates;
use App\Rules\TenantExists;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Product;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders) {}

    public function index(Request $request)
    {
        $date = Dates::parse($request->date);

        // Carrega tudo de uma vez: todas as comandas do dia + abertas de dias anteriores
        // O filtro de status (Abertas/Fechadas/Todas) é feito client-side pelo Alpine
        $orders = Order::with(['client', 'items', 'payments'])
            ->where(function ($q) use ($date) {
                $q->whereDate('created_at', $date)
                  ->orWhere(fn($q2) => $q2->where('status', 'open')->whereDate('created_at', '<', $date));
            })
            ->orderByDesc('created_at')
            ->get();

        // Resumo financeiro do dia
        $allTodayOrders = Order::with(['items', 'payments'])
            ->whereDate('created_at', $date)
            ->whereIn('status', ['open', 'closed'])
            ->get();

        $closedToday = $allTodayOrders->where('status', 'closed');
        $openToday   = $allTodayOrders->where('status', 'open');

        // Vendas por tipo (apenas comandas fechadas)
        $salesByType = [
            'services' => $closedToday->sum(fn($o) => $o->items->where('type', 'service')->sum(fn($i) => $i->subtotal())),
            'products' => $closedToday->sum(fn($o) => $o->items->where('type', 'product')->sum(fn($i) => $i->subtotal())),
            'others'   => $closedToday->sum(fn($o) => $o->items->where('type', 'other')->sum(fn($i) => $i->subtotal())),
        ];

        // Vendas por forma de pagamento (apenas comandas fechadas)
        $salesByPayment = [];
        foreach ($closedToday as $order) {
            foreach ($order->payments as $payment) {
                $m = $payment->method;
                $salesByPayment[$m] = round(($salesByPayment[$m] ?? 0) + $payment->effectiveAmount(), 2);
            }
        }
        arsort($salesByPayment);

        $summary = [
            'openCount'      => $openToday->count(),
            'closedCount'    => $closedToday->count(),
            'expectedTotal'  => $allTodayOrders->sum('total'),
            'actualTotal'    => $closedToday->sum('total'),
            'salesByType'    => $salesByType,
            'salesByPayment' => $salesByPayment,
        ];

        $professionals = Professional::orderBy('name')->get(['id', 'name']);
        $services      = Service::where('active', true)->orderBy('name')->get();
        $products      = Product::where('active', true)->where('for_sale', true)->orderBy('name')->get();

        $openCount   = $orders->where('status', 'open')->count();
        $closedCount = $orders->where('status', 'closed')->count();

        return view('app.orders.index', compact(
            'orders', 'date', 'summary',
            'openCount', 'closedCount',
            'professionals', 'services', 'products'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'uuid', new TenantExists('clients')],
            'notes'     => ['nullable', 'string'],
        ]);

        $order = Order::create([
            ...$data,
            'status' => 'open',
            'total'  => 0,
        ]);

        if ($request->expectsJson()) {
            $order->load(['client', 'items.professional', 'payments', 'appointment']);
            return response()->json($order);
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Comanda aberta!');
    }

    public function show(Order $order)
    {
        $order->load(['client', 'items.professional', 'payments', 'appointment']);
        $professionals = Professional::orderBy('name')->get(['id', 'name']);
        $services      = Service::where('active', true)->orderBy('name')->get();
        $products      = Product::where('active', true)->where('for_sale', true)->orderBy('name')->get();

        return view('app.orders.show', compact('order', 'professionals', 'services', 'products'));
    }

    /** JSON: dados completos da comanda (para o modal) */
    public function data(Order $order)
    {
        $order->load(['client', 'items.professional', 'payments', 'appointment']);
        return response()->json($order);
    }

    /** JSON: listas de profissionais, serviços e produtos (para o modal) */
    public function formData()
    {
        $tenant = auth()->user()->tenant;

        return response()->json([
            'professionals' => Professional::orderBy('name')->get(['id', 'name']),
            'services'      => Service::where('active', true)->orderBy('name')->get(['id', 'name', 'price', 'commission_pct']),
            'products'      => Product::where('active', true)->where('for_sale', true)->orderBy('name')->get(['id', 'name', 'price', 'commission_pct', 'track_stock', 'stock_qty', 'stock_alert_qty']),
            'fees'          => [
                'credit_card' => (float) ($tenant->credit_card_fee ?? 0),
                'debit_card'  => (float) ($tenant->debit_card_fee ?? 0),
            ],
        ]);
    }

    // Adicionar item à comanda
    public function addItem(Request $request, Order $order)
    {
        $order = $this->orders->addItem($order, $this->validatedItem($request));

        if ($request->expectsJson()) {
            return response()->json($order);
        }
        return back()->with('success', 'Item adicionado!');
    }

    public function updateItem(Request $request, Order $order, OrderItem $item)
    {
        $order = $this->orders->updateItem($order, $item, $this->validatedItem($request));

        return response()->json($order);
    }

    public function removeItem(Request $request, Order $order, OrderItem $item)
    {
        $order = $this->orders->removeItem($order, $item);

        if ($request->expectsJson()) {
            return response()->json($order);
        }
        return back()->with('success', 'Item removido.');
    }

    // Registrar pagamento
    public function addPayment(Request $request, Order $order)
    {
        $data = $request->validate([
            'method'       => ['required', 'in:pix,credit_card,debit_card,cash,credit,debt'],
            'installments' => ['nullable', 'integer', 'min:1', 'max:12'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'fee_pct'      => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fee_amount'   => ['nullable', 'numeric', 'min:0'],
            'notes'        => ['nullable', 'string'],
        ]);

        // Garante que fee_pct e fee_amount batem (frontend envia os dois, mas recalcula para segurança)
        $feePct          = (float) ($data['fee_pct'] ?? 0);
        $data['fee_pct'] = $feePct;
        $data['fee_amount'] = $feePct > 0
            ? round((float) $data['amount'] - (float) $data['amount'] / (1 + $feePct / 100), 2)
            : 0;

        $order = $this->orders->addPayment($order, $data);

        if ($request->expectsJson()) {
            return response()->json($order);
        }
        return back()->with('success', 'Pagamento registrado!');
    }

    public function close(Request $request, Order $order)
    {
        $order = $this->orders->close($order);

        if ($request->expectsJson()) {
            return response()->json($order);
        }
        return back()->with('success', 'Comanda fechada!');
    }

    public function reopen(Request $request, Order $order)
    {
        $order = $this->orders->reopen($order);

        if ($request->expectsJson()) {
            return response()->json($order);
        }
        return back()->with('success', 'Comanda reaberta.');
    }

    public function cancel(Request $request, Order $order)
    {
        $this->orders->delete($order);

        if ($request->expectsJson()) {
            return response()->json(['deleted' => true]);
        }
        return back()->with('success', 'Comanda excluída.');
    }

    public function clearPayments(Request $request, Order $order)
    {
        return response()->json($this->orders->clearPayments($order));
    }

    private function validatedItem(Request $request): array
    {
        $data = $request->validate([
            'type'            => ['required', 'in:service,product,other'],
            'description'     => ['required', 'string', 'max:200'],
            'qty'             => ['required', 'integer', 'min:1'],
            'unit_price'      => ['required', 'numeric', 'min:0'],
            'product_id'      => ['nullable', 'uuid', new TenantExists('products')],
            'professional_id' => ['nullable', 'uuid', new TenantExists('professionals')],
            'commission_pct'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'has_commission'  => ['boolean'],
        ]);

        $data['has_commission'] = $request->boolean('has_commission', true);

        return $data;
    }
}
