<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Professional;
use App\Models\Service;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $date   = $request->date ? Carbon::parse($request->date) : Carbon::today();
        $status = $request->status ?? 'open';

        $query = Order::with(['client', 'items', 'payments'])
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($request->boolean('show_pending', true), function ($q) use ($date, $status) {
                // Inclui comandas abertas de dias anteriores se configurado
                if ($status === 'open') {
                    return $q->whereDate('created_at', '<=', $date);
                }
                return $q->whereDate('created_at', $date);
            }, fn($q) => $q->whereDate('created_at', $date))
            ->orderByDesc('created_at');

        $orders = $query->get();

        // Resumo financeiro do dia
        $todayOrders  = Order::with(['items', 'payments'])
            ->whereDate('created_at', $date)
            ->where('status', 'closed')
            ->get();

        $summary = [
            'total'    => $todayOrders->sum(fn($o) => $o->total),
            'services' => $todayOrders->sum(fn($o) =>
                $o->items->where('type', 'service')->sum(fn($i) => $i->subtotal())
            ),
            'products' => $todayOrders->sum(fn($o) =>
                $o->items->where('type', 'product')->sum(fn($i) => $i->subtotal())
            ),
        ];

        $clients       = Client::orderBy('name')->get(['id', 'name', 'phone', 'balance']);
        $professionals = Professional::orderBy('name')->get(['id', 'name']);
        $services      = Service::where('active', true)->orderBy('name')->get();
        $products      = Product::where('active', true)->where('for_sale', true)->orderBy('name')->get();

        return view('app.orders.index', compact(
            'orders', 'date', 'status', 'summary',
            'clients', 'professionals', 'services', 'products'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'uuid', 'exists:clients,id'],
            'notes'     => ['nullable', 'string'],
        ]);

        $order = Order::create([
            ...$data,
            'status' => 'open',
            'total'  => 0,
        ]);

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

    // Adicionar item à comanda
    public function addItem(Request $request, Order $order)
    {
        $data = $request->validate([
            'type'            => ['required', 'in:service,product,other'],
            'description'     => ['required', 'string', 'max:200'],
            'qty'             => ['required', 'integer', 'min:1'],
            'unit_price'      => ['required', 'numeric', 'min:0'],
            'professional_id' => ['nullable', 'uuid', 'exists:professionals,id'],
            'commission_pct'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'has_commission'  => ['boolean'],
        ]);

        $data['has_commission'] = $request->boolean('has_commission', true);

        $order->items()->create($data);
        $order->recalcTotal();

        return back()->with('success', 'Item adicionado!');
    }

    public function removeItem(Order $order, OrderItem $item)
    {
        $item->delete();
        $order->recalcTotal();
        return back()->with('success', 'Item removido.');
    }

    // Registrar pagamento
    public function addPayment(Request $request, Order $order)
    {
        $data = $request->validate([
            'method' => ['required', 'in:pix,credit_card,debit_card,cash,credit,debt'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes'  => ['nullable', 'string'],
        ]);

        $order->payments()->create($data);

        // Ajusta saldo do cliente para fiado ou crédito
        $this->applyClientBalance($order, $data['method'], (float) $data['amount']);

        // Verifica se comanda está totalmente paga
        $order->load('payments');
        if ($order->isPaid()) {
            $order->update(['status' => 'closed']);
        }

        return back()->with('success', 'Pagamento registrado!');
    }

    public function close(Order $order)
    {
        $order->update(['status' => 'closed']);

        // Atualiza agendamento para "completed" se vinculado
        if ($order->appointment) {
            $order->appointment->update(['status' => 'completed']);
        }

        return back()->with('success', 'Comanda fechada!');
    }

    public function reopen(Order $order)
    {
        $order->update(['status' => 'open']);
        return back()->with('success', 'Comanda reaberta.');
    }

    private function applyClientBalance(Order $order, string $method, float $amount): void
    {
        $client = $order->client;

        if ($method === 'debt') {
            // Fiado: subtrai do saldo (fica negativo = dívida)
            $client->decrement('balance', $amount);
            return;
        }

        if ($method === 'credit') {
            // Usa crédito existente do cliente
            $client->decrement('balance', $amount);
            return;
        }

        // Troco/excesso vira crédito
        $order->load('payments', 'items');
        $balance = $order->balance();
        if ($balance > 0) {
            $client->increment('balance', $balance);
        }
    }
}