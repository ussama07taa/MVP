<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Order, OrderLine, Client, StockCanto, Service, OrderReturn};
use Carbon\Carbon;

class DashboardController extends Controller {
    public function index(Request $request) {
        $period = $request->get('period', 'month');

        // Determine Start Date based on period
        $startDate = match ($period) {
            'day'   => Carbon::now()->startOfDay(),
            'week'  => Carbon::now()->startOfWeek(),
            default => Carbon::now()->startOfMonth(),
        };

        // This Period's Stats
        $ordersThisPeriod = Order::withoutGlobalScopes()->where('created_at', '>=', $startDate)->get();
        $invoicesThisPeriod = \App\Models\Invoice::withoutGlobalScopes()->where('type', 'invoice')->whereNotNull('validated_at')->where('validated_at', '>=', $startDate)->get();
        
        $ordersThisPeriodIds = $ordersThisPeriod->pluck('id');
        
        // Revenue from POS + Invoices
        $posRevenue = $ordersThisPeriod->sum('total_sell_price');
        $invoiceRevenue = $invoicesThisPeriod->sum('total');
        $revenueThisPeriod = $posRevenue + $invoiceRevenue;

        // Cost (COGS)
        $posCost = $ordersThisPeriod->sum('total_cost_price');
        $invoiceCost = \App\Models\InvoiceItem::whereIn('invoice_id', $invoicesThisPeriod->pluck('id'))->selectRaw('SUM(unit_cost * quantity) as total_cost')->value('total_cost') ?? 0;
        $costThisPeriod = $posCost + $invoiceCost;

        $profitThisPeriod = $revenueThisPeriod - $costThisPeriod;

        $servicesRevenueThisPeriod = 0;
        if($ordersThisPeriodIds->count() > 0) {
            $servicesRevenueThisPeriod = OrderLine::withoutGlobalScopes()->whereIn('order_id', $ordersThisPeriodIds)
                                              ->where('item_type', Service::class)
                                              ->sum('total_line_sell');
        }
        // Add service revenue from invoices (category = service)
        $invoiceServicesRevenue = \App\Models\InvoiceItem::whereIn('invoice_id', $invoicesThisPeriod->pluck('id'))
                                              ->where('category', 'service')
                                              ->sum('total');
        $servicesRevenueThisPeriod += $invoiceServicesRevenue;

        $materialsRevenueThisPeriod = $revenueThisPeriod - $servicesRevenueThisPeriod;

        $marginPercent = $revenueThisPeriod > 0 ? ($profitThisPeriod / $revenueThisPeriod) * 100 : 0;

        // Last Month's Stats
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();
        $revenueLastMonthPos = Order::withoutGlobalScopes()->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->sum('total_sell_price');
        $revenueLastMonthInv = \App\Models\Invoice::withoutGlobalScopes()->where('type', 'invoice')->whereNotNull('validated_at')->whereBetween('validated_at', [$startOfLastMonth, $endOfLastMonth])->sum('total');
        $revenueLastMonth = $revenueLastMonthPos + $revenueLastMonthInv;
        
        $growthPercent = 0;
        if ($revenueLastMonth > 0) {
            $growthPercent = (($revenueThisPeriod - $revenueLastMonth) / $revenueLastMonth) * 100;
        } else if ($revenueThisPeriod > 0) {
            $growthPercent = 100;
        }

        // Global Atelier Stats
        $totalUnpaidCredit = Client::withoutGlobalScopes()->sum('total_credit');

        // === OPERATING EXPENSES (Ce mois) ===
        // 1. All operating expenses (salaries, rent, utilities, etc.)
        $totalExpensesThisPeriod = \App\Models\Expense::withoutGlobalScopes()->where('expense_date', '>=', $startDate)
            ->sum('amount');
        
        // 2. Gross Purchases (for display only — NOT subtracted from profit, since
        //    material costs are already accounted for in COGS via total_cost_price)
        $grossPurchasesThisPeriod = \App\Models\Purchase::withoutGlobalScopes()->where('created_at', '>=', $startDate)->sum('total_amount');

        // 3. Purchase Returns / Supplier Refunds (for display)
        $supplierReturnsThisPeriod = \App\Models\PurchaseReturn::withoutGlobalScopes()->where('created_at', '>=', $startDate)->sum('refund_amount');

        // 4. Customer Returns (Order Refunds)
        $customerReturnsThisPeriod = OrderReturn::withoutGlobalScopes()->where('created_at', '>=', $startDate)->sum('total_refunded');

        // Net Profit = Gross Profit - Operating Expenses - Customer Returns
        // NOTE: Purchases are NOT subtracted here because material costs are already
        // included in total_cost_price (COGS) when each order is created.
        $netProfitThisPeriod = $profitThisPeriod - $totalExpensesThisPeriod - $customerReturnsThisPeriod;
        $netMarginPercent = $revenueThisPeriod > 0 ? ($netProfitThisPeriod / $revenueThisPeriod) * 100 : 0;

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

        $recentInvoices = \App\Models\Invoice::withoutGlobalScopes()->where('type', 'invoice')->whereNotNull('validated_at')->with('client')->latest()->take(5)->get()->map(function($i) {
            return [
                'id' => 'inv-' . $i->id, 'type' => 'invoice', 'title' => 'Facture ' . $i->invoice_number, 'subtitle' => $i->client?->name ?? 'Client', 'amount' => round($i->total, 2), 'time' => $i->validated_at->diffForHumans(), 'raw_date' => $i->validated_at
            ];
        });
 
        $recentActivity = $recentOrders->concat($recentPayments)->concat($recentInvoices)->sortByDesc('raw_date')->take(5)->values();

        return \Inertia\Inertia::render('DashboardApp', [
            'stats' => [
                'revenue_today'           => round($revenueThisPeriod, 2),
                'services_revenue_today'  => round($servicesRevenueThisPeriod, 2),
                'materials_revenue_today' => round($materialsRevenueThisPeriod, 2),
                'profit_today'            => round($netProfitThisPeriod, 2), 
                'gross_profit'            => round($profitThisPeriod, 2),
                'total_expenses'          => round($totalExpensesThisPeriod, 2),
                'gross_purchases'         => round($grossPurchasesThisPeriod, 2),
                'supplier_returns'        => round($supplierReturnsThisPeriod, 2),
                'customer_returns'        => round($customerReturnsThisPeriod, 2),
                'margin_percent'          => round($netMarginPercent, 1),
                'revenue_growth'          => round($growthPercent, 1),
                'total_credit_market'     => round($totalUnpaidCredit, 2),
                'total_supplier_debt'     => round($totalSupplierDebt, 2),
                'total_orders_month'      => $ordersThisPeriod->count() + $invoicesThisPeriod->count(),
                'total_clients'           => Client::withoutGlobalScopes()->count(),
                'clients_with_credit'     => Client::withoutGlobalScopes()->where('total_credit', '>', 0.05)->count(),
            ],
            'alerts' => [
                'low_canto_stock' => $cantoAlerts,
                'low_panel_stock' => $panelAlerts,
                'recent_activity' => $recentActivity
            ]
        ]);
    }
}
