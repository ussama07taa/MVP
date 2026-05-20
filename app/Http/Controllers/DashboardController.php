<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Order, OrderLine, Client, StockCanto, Service, OrderReturn};
use Carbon\Carbon;

class DashboardController extends Controller {
    public function index(Request $request) {
        // This Month's Stats
        $startOfMonth = Carbon::now()->startOfMonth();
        $ordersThisMonth = Order::withoutGlobalScopes()->where('created_at', '>=', $startOfMonth)->get();
        $ordersThisMonthIds = $ordersThisMonth->pluck('id');
        
        $revenueThisMonth = $ordersThisMonth->sum('total_sell_price');
        $costThisMonth = $ordersThisMonth->sum('total_cost_price');
        $profitThisMonth = $revenueThisMonth - $costThisMonth;

        $servicesRevenueThisMonth = 0;
        if($ordersThisMonthIds->count() > 0) {
            $servicesRevenueThisMonth = OrderLine::withoutGlobalScopes()->whereIn('order_id', $ordersThisMonthIds)
                                              ->where('item_type', Service::class)
                                              ->sum('total_line_sell');
        }
        $materialsRevenueThisMonth = $revenueThisMonth - $servicesRevenueThisMonth;

        $marginPercent = $revenueThisMonth > 0 ? ($profitThisMonth / $revenueThisMonth) * 100 : 0;

        // Last Month's Stats
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
        $revenueLastMonth = Order::withoutGlobalScopes()->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('total_sell_price');
        
        $growthPercent = 0;
        if ($revenueLastMonth > 0) {
            $growthPercent = (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100;
        } else if ($revenueThisMonth > 0) {
            $growthPercent = 100;
        }

        // Global Atelier Stats
        $totalUnpaidCredit = Client::withoutGlobalScopes()->sum('total_credit');

        // === OPERATING EXPENSES (Ce mois) ===
        // 1. Salaries & OPEX
        $salaryExpensesThisMonth = \App\Models\Expense::withoutGlobalScopes()->where('expense_date', '>=', $startOfMonth)
            ->sum('amount');
        
        // 2. Consumables Purchased (Gross Purchases)
        $grossPurchasesThisMonth = \App\Models\Purchase::withoutGlobalScopes()->where('created_at', '>=', $startOfMonth)->sum('total_amount');

        // 3. Purchase Returns (Subtraction)
        $totalReturnsThisMonth = \App\Models\PurchaseReturn::withoutGlobalScopes()->where('created_at', '>=', $startOfMonth)->sum('refund_amount');

        // 4. Net Purchases (True COGS)
        $netPurchasesThisMonth = $grossPurchasesThisMonth - $totalReturnsThisMonth;

        // 5. Customer Returns (Order Refunds)
        $customerReturnsThisMonth = OrderReturn::withoutGlobalScopes()->where('created_at', '>=', $startOfMonth)->sum('total_refunded');

        // Net Profit = Gross Profit - Salaries - Net Purchases - Customer Returns
        $netProfitThisMonth = $profitThisMonth - $salaryExpensesThisMonth - $netPurchasesThisMonth - $customerReturnsThisMonth;
        $netMarginPercent = $revenueThisMonth > 0 ? ($netProfitThisMonth / $revenueThisMonth) * 100 : 0;

        // Global Atelier Stats
        $totalUnpaidCredit = Client::withoutGlobalScopes()->sum('total_credit');
        $totalSupplierDebt = \App\Models\Supplier::withoutGlobalScopes()->sum('total_debt');

        // Stock Alerts (Canto remaining length < threshold)
        $cantoAlerts = StockCanto::withoutGlobalScopes()->whereRaw('total_length_remaining <= alert_threshold')
            ->get(['color_code', 'finish_type', 'total_length_remaining', 'alert_threshold']);
        
        // Stock Alerts (Panels quantity < threshold)
        $panelAlerts = [];
        try {
            $panelAlerts = \App\Models\StockPanel::withoutGlobalScopes()->whereRaw('quantity <= alert_threshold')
                ->get(['type', 'color_code', 'finish_type', 'quantity', 'alert_threshold']);
        } catch (\Exception $e) {
            $panelAlerts = \App\Models\StockPanel::withoutGlobalScopes()->where('quantity', '<=', 2)
                ->get(['type', 'color_code', 'finish_type', 'quantity']);
        }

        // Recent Activity Feed (Latest Orders & Payments)
        $recentOrders = Order::withoutGlobalScopes()->with('client')->latest()->take(5)->get()->map(function($o) {
            return [
                'id' => 'ord-' . $o->id, 'type' => 'order', 'title' => 'Vente #' . $o->id, 'subtitle' => $o->client?->name ?? 'Client', 'amount' => round($o->total_sell_price, 2), 'time' => $o->created_at->diffForHumans(), 'raw_date' => $o->created_at
            ];
        });

        $recentPayments = \App\Models\Payment::withoutGlobalScopes()->with('client')->latest()->take(5)->get()->map(function($p) {
            return [
                'id' => 'pay-' . $p->id,
                'type' => 'payment',
                'title' => 'Paiement Reçu',
                'subtitle' => $p->client?->name ?? 'Client Inconnu',
                'amount' => round($p->amount, 2),
                'time' => $p->created_at->diffForHumans(),
                'raw_date' => $p->created_at
            ];
        });

        $recentActivity = $recentOrders->concat($recentPayments)->sortByDesc('raw_date')->take(5)->values();

        return \Inertia\Inertia::render('DashboardApp', [
            'stats' => [
                'revenue_today'           => round($revenueThisMonth, 2),
                'services_revenue_today'  => round($servicesRevenueThisMonth, 2),
                'materials_revenue_today' => round($materialsRevenueThisMonth, 2),
                'profit_today'            => round($netProfitThisMonth, 2), 
                'gross_profit'            => round($profitThisMonth, 2),
                'salary_expenses'         => round($salaryExpensesThisMonth, 2),
                'purchase_expenses'       => round($netPurchasesThisMonth, 2),
                'gross_purchases'         => round($grossPurchasesThisMonth, 2),
                'total_returns'           => round($totalReturnsThisMonth, 2), // Supplier
                'customer_returns'        => round($customerReturnsThisMonth, 2),
                'margin_percent'          => round($netMarginPercent, 1),
                'revenue_growth'          => round($growthPercent, 1),
                'total_credit_market'     => round($totalUnpaidCredit, 2),
                'total_supplier_debt'     => round($totalSupplierDebt, 2)
            ],
            'alerts' => [
                'low_canto_stock' => $cantoAlerts,
                'low_panel_stock' => $panelAlerts,
                'recent_activity' => $recentActivity
            ]
        ]);
    }
}
