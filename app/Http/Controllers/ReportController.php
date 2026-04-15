<?php

namespace App\Http\Controllers;

use App\Models\CommissionPayment;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ProfessionalVoucher;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $mode = $request->mode === 'day' ? 'day' : 'month';

        if ($mode === 'day') {
            $date  = $request->date ? Carbon::parse($request->date) : Carbon::today();
            $start = $date->copy()->startOfDay();
            $end   = $date->copy()->endOfDay();
        } else {
            $date  = null;
            $month = $request->month
                ? Carbon::parse($request->month . '-01')
                : Carbon::now()->startOfMonth();
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();
        }

        // --- Pedidos fechados no período ---
        $closedOrders = Order::with(['items.professional', 'payments'])
            ->where('status', 'closed')
            ->whereBetween('updated_at', [$start, $end])
            ->get();

        // --- Vendas por tipo + por profissional (com comissão calculada) ---
        $revenueServices = 0;
        $revenueProducts = 0;
        $revenueOthers   = 0;
        $byProfessional  = [];

        foreach ($closedOrders as $order) {
            foreach ($order->items as $item) {
                $sub = $item->subtotal();
                $com = $item->commissionValue();

                match ($item->type) {
                    'service' => $revenueServices += $sub,
                    'product' => $revenueProducts += $sub,
                    default   => $revenueOthers   += $sub,
                };

                $key  = $item->professional_id ?? 'none';
                $name = $item->professional?->name ?? 'Sem profissional';

                if (! isset($byProfessional[$key])) {
                    $byProfessional[$key] = ['name' => $name, 'total' => 0, 'commission' => 0];
                }
                $byProfessional[$key]['total']      += $sub;
                $byProfessional[$key]['commission']  += $com;
            }
        }

        $revenueTotal           = $revenueServices + $revenueProducts + $revenueOthers;
        $totalCommissionCalc    = array_sum(array_column($byProfessional, 'commission'));

        uasort($byProfessional, fn($a, $b) => $b['total'] <=> $a['total']);

        // --- Total recebido (soma dos pagamentos) ---
        $totalReceived = 0;
        $revenueByMethod = collect();
        $allMethods = ['pix', 'credit_card', 'debit_card', 'cash', 'credit', 'debt'];
        $methodTotals = array_fill_keys($allMethods, 0.0);

        foreach ($closedOrders as $order) {
            foreach ($order->payments as $payment) {
                $totalReceived += (float) $payment->amount;
                if (isset($methodTotals[$payment->method])) {
                    $methodTotals[$payment->method] += (float) $payment->amount;
                }
            }
        }

        $revenueByMethod = collect($methodTotals)->filter(fn($v) => $v > 0);

        // --- Comissões pagas no período (CommissionPayment registrados) ---
        $commissionsPaid = (float) CommissionPayment::whereBetween('created_at', [$start, $end])
            ->sum('net_amount');

        // --- Vales emitidos no período ---
        $vouchersPeriod = (float) ProfessionalVoucher::whereBetween('issued_at', [$start, $end])
            ->sum('amount');

        // --- Despesas ---
        $expenses = Expense::with('category')
            ->whereBetween('due_date', [$start, $end])
            ->get();

        $expenseTotal      = (float) $expenses->sum('amount');
        $expenseByCategory = $expenses
            ->groupBy(fn($e) => $e->category?->name ?? 'Sem categoria')
            ->map(fn($group) => (float) $group->sum('amount'))
            ->sortDesc();

        // --- Resultados ---
        // Visão 1: vendas - comissões calculadas - despesas
        $result1 = $revenueTotal - $totalCommissionCalc - $expenseTotal;
        // Visão 2: vendas - comissões pagas - vales - despesas
        $result2 = $revenueTotal - $commissionsPaid - $vouchersPeriod - $expenseTotal;

        // --- Aniversariantes ---
        $todayMd   = now()->format('m-d');
        $birthdays = \App\Models\Client::get()
            ->filter(fn($c) => $c->birthday && $c->birthday->format('m-d') === $todayMd)
            ->values();

        $upcomingBirthdays = \App\Models\Client::get()
            ->filter(function ($c) {
                if (! $c->birthday) return false;
                $next = Carbon::createFromFormat('m-d', $c->birthday->format('m-d'));
                if ($next->isPast()) $next->addYear();
                return $next->diffInDays(now()) <= 7 && $next->diffInDays(now()) > 0;
            })->values();

        return view('app.reports.index', compact(
            'mode', 'date', 'start', 'end',
            'revenueTotal', 'revenueServices', 'revenueProducts', 'revenueOthers',
            'revenueByMethod', 'byProfessional',
            'totalReceived', 'totalCommissionCalc',
            'commissionsPaid', 'vouchersPeriod',
            'expenseTotal', 'expenseByCategory',
            'result1', 'result2',
            'birthdays', 'upcomingBirthdays'
        ) + ($mode === 'month' ? ['month' => $month] : []));
    }
}
