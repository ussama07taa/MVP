<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends Controller 
{
    public function index(Request $request, \App\Services\FinancialStatsService $service) 
    {
        $tenant_id = auth()->user()->tenant_id;
        
        $month = $request->query('month', \Carbon\Carbon::now()->month);
        $year = $request->query('year', \Carbon\Carbon::now()->year);

        $coreStats = $service->getMonthlyStats($month, $year, $tenant_id);
        
        // Additional breakdowns (kept here because they are UI-specific or too detailed for the base service)
        // Revenue Breakdown by Item Type
        $revenueBreakdown = DB::table('order_lines')
            ->join('orders', 'order_lines.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenant_id)
            ->where('orders.status', '!=', 'devis')
            ->whereMonth('orders.created_at', $month)
            ->whereYear('orders.created_at', $year)
            ->selectRaw('item_type, SUM(total_line_sell) as total')
            ->groupBy('item_type')
            ->get()
            ->map(function($item) {
                $type = str_replace(['App\Models\Stock', 'App\Models\\'], '', $item->item_type);
                return [
                    'type' => $type ?: 'Service',
                    'total' => (float)$item->total
                ];
            });

        $cogsBreakdown = DB::table('order_lines')
            ->join('orders', 'order_lines.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenant_id)
            ->where('orders.status', '!=', 'devis')
            ->whereMonth('orders.created_at', $month)
            ->whereYear('orders.created_at', $year)
            ->selectRaw('item_type, SUM(total_line_cost) as total')
            ->groupBy('item_type')
            ->get()
            ->map(function($item) {
                $type = str_replace(['App\Models\Stock', 'App\Models\\'], '', $item->item_type);
                return [
                    'type' => $type ?: 'Service',
                    'total' => (float)$item->total
                ];
            });

        $expenseBreakdown = DB::table('expenses')
            ->where('tenant_id', $tenant_id)
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->where('category', '!=', 'salaire')
            ->selectRaw('category, SUM(amount) as total')
            ->groupBy('category')
            ->get()
            ->map(function($item) {
                return [
                    'category' => $item->category,
                    'total' => (float)$item->total
                ];
            })->toArray();

        $monthlyWages = DB::table('employee_attendances')
                          ->where('tenant_id', $tenant_id)
                          ->whereMonth('date', $month)
                          ->whereYear('date', $year)
                          ->sum('wage_earned');

        $expenseBreakdown[] = [
            'category' => 'Salaire (Employés)',
            'total' => (float)$monthlyWages
        ];

        $topClients = DB::table('orders')
            ->join('clients', 'orders.client_id', '=', 'clients.id')
            ->where('orders.tenant_id', $tenant_id)
            ->whereMonth('orders.created_at', $month)
            ->whereYear('orders.created_at', $year)
            ->where('orders.status', '!=', 'devis')
            ->selectRaw('clients.name, SUM(orders.total_sell_price) as total_spent')
            ->groupBy('clients.id', 'clients.name')
            ->orderByDesc('total_spent')
            ->take(3)
            ->get();

        $dailyRevenue = DB::table('orders')
            ->where('tenant_id', $tenant_id)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('status', '!=', 'devis')
            ->selectRaw('DATE(created_at) as date, SUM(total_sell_price) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $recentOrders = DB::table('orders')
            ->leftJoin('clients', 'orders.client_id', '=', 'clients.id')
            ->where('orders.tenant_id', $tenant_id)
            ->where('orders.status', '!=', 'devis')
            ->select('orders.id', 'clients.name as client', 'orders.total_sell_price as amount', 'orders.created_at')
            ->latest('orders.created_at')
            ->take(5)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'client' => $order->client ?? 'Passager',
                    'amount' => (float)$order->amount,
                    'time' => \Carbon\Carbon::parse($order->created_at)->diffForHumans()
                ];
            });

        return response()->json(array_merge($coreStats, [
            'month' => (int)$month,
            'year' => (int)$year,
            'top_clients' => $topClients,
            'daily_revenue' => $dailyRevenue,
            'recent_orders' => $recentOrders,
            'expense_breakdown' => $expenseBreakdown,
            'revenue_breakdown' => $revenueBreakdown,
            'cogs_breakdown' => $cogsBreakdown
        ]));
    }
}
