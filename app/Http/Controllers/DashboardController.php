<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Order, OrderLine, Client, StockCanto, Service, OrderReturn, Invoice, InvoiceItem, Payment, Expense, Purchase, PurchaseReturn, StockPanel, Supplier};
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);

        $finStats = $this->getRevenueAndProfitStats($startDate);
        $growthPercent = $this->getGrowthPercent($finStats['revenue']);
        $netStats = $this->getNetProfitStats($startDate, $finStats['gross_profit'], $finStats['revenue']);
        $globalStats = $this->getGlobalAtelierStats();
        $stockAlerts = $this->getStockAlerts();
        $recentActivity = $this->getRecentActivityFeed();

        return \Inertia\Inertia::render('DashboardApp', [
            'stats' => [
                'revenue_today' => round($finStats['revenue'], 2),
                'services_revenue_today' => round($finStats['services_revenue'], 2),
                'materials_revenue_today' => round($finStats['materials_revenue'], 2),
                'profit_today' => round($netStats['net_profit'], 2),
                'gross_profit' => round($finStats['gross_profit'], 2),
                'total_expenses' => round($netStats['total_expenses'], 2),
                'gross_purchases' => round($netStats['gross_purchases'], 2),
                'supplier_returns' => round($netStats['supplier_returns'], 2),
                'customer_returns' => round($netStats['customer_returns'], 2),
                'margin_percent' => round($netStats['net_margin_percent'], 1),
                'revenue_growth' => round($growthPercent, 1),
                'total_credit_market' => round($globalStats['total_unpaid_credit'], 2),
                'total_supplier_debt' => round($globalStats['total_supplier_debt'], 2),
                'total_orders_month' => $finStats['orders_count'] + $finStats['invoices_count'],
                'total_clients' => $globalStats['total_clients'],
                'clients_with_credit' => $globalStats['clients_with_credit'],
            ],
            'alerts' => array_merge($stockAlerts, [
                'recent_activity' => $recentActivity
            ])
        ]);
    }

    private function getStartDate(string $period): Carbon
    {
        return match ($period) {
            'day' => Carbon::now()->startOfDay(),
            'week' => Carbon::now()->startOfWeek(),
            default => Carbon::now()->startOfMonth(),
        };
    }

    private function getRevenueAndProfitStats(Carbon $startDate): array
    {
        $orders = Order::withoutGlobalScopes()->where('created_at', '>=', $startDate)->get();
        $invoices = Invoice::withoutGlobalScopes()
            ->where('type', 'invoice')
            ->whereNotNull('validated_at')
            ->where('validated_at', '>=', $startDate)
            ->get();

        $revenue = $orders->sum('total_sell_price') + $invoices->sum('total');

        $posCost = $orders->sum('total_cost_price');
        $invoiceCost = InvoiceItem::whereIn('invoice_id', $invoices->pluck('id'))
            ->selectRaw('SUM(unit_cost * quantity) as total_cost')
            ->value('total_cost') ?? 0;
        $totalCost = $posCost + $invoiceCost;

        $grossProfit = $revenue - $totalCost;

        $servicesRevenue = 0;
        if ($orders->isNotEmpty()) {
            $servicesRevenue = OrderLine::withoutGlobalScopes()
                ->whereIn('order_id', $orders->pluck('id'))
                ->where('item_type', Service::class)
                ->sum('total_line_sell');
        }
        $servicesRevenue += InvoiceItem::whereIn('invoice_id', $invoices->pluck('id'))
            ->where('category', 'service')
            ->sum('total');

        return [
            'revenue' => $revenue,
            'gross_profit' => $grossProfit,
            'services_revenue' => $servicesRevenue,
            'materials_revenue' => $revenue - $servicesRevenue,
            'orders_count' => $orders->count(),
            'invoices_count' => $invoices->count(),
        ];
    }

    private function getGrowthPercent(float $currentRevenue): float
    {
        $start = Carbon::now()->subMonth()->startOfMonth();
        $end = Carbon::now()->subMonth()->endOfMonth();

        $prevPos = Order::withoutGlobalScopes()->whereBetween('created_at', [$start, $end])->sum('total_sell_price');
        $prevInv = Invoice::withoutGlobalScopes()
            ->where('type', 'invoice')
            ->whereNotNull('validated_at')
            ->whereBetween('validated_at', [$start, $end])
            ->sum('total');

        $prevRevenue = $prevPos + $prevInv;

        if ($prevRevenue > 0) {
            return (($currentRevenue - $prevRevenue) / $prevRevenue) * 100;
        }
        return $currentRevenue > 0 ? 100 : 0;
    }

    private function getNetProfitStats(Carbon $startDate, float $grossProfit, float $revenue): array
    {
        $expenses = Expense::withoutGlobalScopes()->where('expense_date', '>=', $startDate)->sum('amount');
        $grossPurchases = Purchase::withoutGlobalScopes()->where('created_at', '>=', $startDate)->sum('total_amount');
        $supplierReturns = PurchaseReturn::withoutGlobalScopes()->where('created_at', '>=', $startDate)->sum('refund_amount');
        $customerReturns = OrderReturn::withoutGlobalScopes()->where('created_at', '>=', $startDate)->sum('total_refunded');

        $netProfit = $grossProfit - $expenses - $customerReturns;
        $netMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        return [
            'net_profit' => $netProfit,
            'total_expenses' => $expenses,
            'gross_purchases' => $grossPurchases,
            'supplier_returns' => $supplierReturns,
            'customer_returns' => $customerReturns,
            'net_margin_percent' => $netMargin,
        ];
    }

    private function getGlobalAtelierStats(): array
    {
        return [
            'total_unpaid_credit' => Client::withoutGlobalScopes()->sum('total_credit'),
            'total_supplier_debt' => Supplier::withoutGlobalScopes()->sum('total_debt'),
            'total_clients' => Client::withoutGlobalScopes()->count(),
            'clients_with_credit' => Client::withoutGlobalScopes()->where('total_credit', '>', 0.05)->count(),
        ];
    }

    private function getStockAlerts(): array
    {
        $cantoAlerts = StockCanto::withoutGlobalScopes()->whereRaw('total_length_remaining <= alert_threshold')
            ->get(['color_code', 'finish_type', 'total_length_remaining', 'alert_threshold']);

        $panelAlerts = [];
        try {
            $panelAlerts = StockPanel::withoutGlobalScopes()->whereRaw('quantity <= alert_threshold')
                ->get(['type', 'color_code', 'finish_type', 'quantity', 'alert_threshold']);
        } catch (\Exception $e) {
            $panelAlerts = StockPanel::withoutGlobalScopes()->where('quantity', '<=', 2)
                ->get(['type', 'color_code', 'finish_type', 'quantity']);
        }

        return [
            'low_canto_stock' => $cantoAlerts,
            'low_panel_stock' => $panelAlerts,
        ];
    }

    private function getRecentActivityFeed(): array
    {
        $recentOrders = Order::withoutGlobalScopes()->with('client')->latest()->take(5)->get()->map(fn($o) => [
            'id' => 'ord-' . $o->id,
            'type' => 'order',
            'title' => 'Vente #' . $o->id,
            'subtitle' => $o->client?->name ?? 'Client',
            'amount' => round($o->total_sell_price, 2),
            'time' => $o->created_at->diffForHumans(),
            'raw_date' => $o->created_at
        ]);

        $recentPayments = Payment::withoutGlobalScopes()->with('client')->latest()->take(5)->get()->map(fn($p) => [
            'id' => 'pay-' . $p->id,
            'type' => 'payment',
            'title' => 'Paiement Reçu',
            'subtitle' => $p->client?->name ?? 'Client Inconnu',
            'amount' => round($p->amount, 2),
            'time' => $p->created_at->diffForHumans(),
            'raw_date' => $p->created_at
        ]);

        $recentInvoices = Invoice::withoutGlobalScopes()->where('type', 'invoice')->whereNotNull('validated_at')->with('client')->latest()->take(5)->get()->map(fn($i) => [
            'id' => 'inv-' . $i->id,
            'type' => 'invoice',
            'title' => 'Facture ' . $i->invoice_number,
            'subtitle' => $i->client?->name ?? 'Client',
            'amount' => round($i->total, 2),
            'time' => $i->validated_at->diffForHumans(),
            'raw_date' => $i->validated_at
        ]);

        return $recentOrders->concat($recentPayments)->concat($recentInvoices)
            ->sortByDesc('raw_date')
            ->take(5)
            ->values()
            ->toArray();
    }
}
