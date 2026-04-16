<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Client;
use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $user   = auth()->user();
        $tenant = $user->tenant;
        $today  = Carbon::today();

        // Agendamentos de hoje
        $appointmentsToday = Appointment::whereDate('date', $today)
            ->whereNotIn('status', ['cancelled'])
            ->count();

        // Comandas abertas
        $openOrders = Order::where('status', 'open')->count();

        // Faturamento do dia (comandas fechadas hoje)
        $revenueToday = Order::where('status', 'closed')
            ->whereDate('updated_at', $today)
            ->sum('total');

        // Clientes novos no mês
        $newClientsMonth = Client::whereMonth('created_at', $today->month)
            ->whereYear('created_at', $today->year)
            ->count();

        // Próximos agendamentos do dia (listinha)
        $nextAppointments = Appointment::with(['client', 'professional'])
            ->whereDate('date', $today)
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        // Aniversariantes hoje
        $todayMd   = $today->format('m-d');
        $birthdays = Client::get()
            ->filter(fn($c) => $c->birthday && $c->birthday->format('m-d') === $todayMd)
            ->values();

        // Produtos com estoque em alerta (chegou ou passou da quantidade mínima)
        $lowStockProducts = Product::where('track_stock', true)
            ->whereNotNull('stock_alert_qty')
            ->whereRaw('stock_qty <= stock_alert_qty')
            ->orderBy('name')
            ->get(['id', 'name', 'stock_qty', 'stock_alert_qty']);

        return view('app.dashboard', compact(
            'user', 'tenant',
            'appointmentsToday', 'openOrders', 'revenueToday', 'newClientsMonth',
            'nextAppointments', 'birthdays', 'lowStockProducts'
        ));
    }
}