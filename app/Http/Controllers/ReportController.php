<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->month
            ? Carbon::parse($request->month . '-01')
            : Carbon::now()->startOfMonth();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth   = $month->copy()->endOfMonth();

        // --- Receitas ---
        $closedOrders = Order::with(['items', 'payments'])
            ->where('status', 'closed')
            ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->get();

        $revenueByMethod = Payment::whereHas('order', fn($q) =>
                $q->where('status', 'closed')
                  ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            )
            ->get()
            ->groupBy('method')
            ->map(fn($group) => $group->sum('amount'));

        $revenueServices = $closedOrders->sum(fn($o) =>
            $o->items->where('type', 'service')->sum(fn($i) => $i->subtotal())
        );

        $revenueProducts = $closedOrders->sum(fn($o) =>
            $o->items->where('type', 'product')->sum(fn($i) => $i->subtotal())
        );

        $revenueTotal = $revenueServices + $revenueProducts;

        // --- Despesas ---
        $expenses = Expense::with('category')
            ->whereBetween('due_date', [$startOfMonth, $endOfMonth])
            ->get();

        $expenseTotal = $expenses->sum('amount');
        $expenseByCategory = $expenses
            ->groupBy(fn($e) => $e->category?->name ?? 'Sem categoria')
            ->map(fn($group) => $group->sum('amount'))
            ->sortDesc();

        // --- Resultado ---
        $result = $revenueTotal - $expenseTotal;

        // --- Aniversariantes ---
        $todayMd  = now()->format('m-d');
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
            'month', 'revenueTotal', 'revenueServices', 'revenueProducts',
            'revenueByMethod', 'expenseTotal', 'expenseByCategory',
            'result', 'birthdays', 'upcomingBirthdays'
        ));
    }
}