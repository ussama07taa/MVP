<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Order, Client, StockCanto, OrderReturn, Invoice, Payment, Expense, StockPanel, Supplier, SupplierPayment};
use App\Services\FinancialReportService;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct(private FinancialReportService $reports)
    {
    }

    public function index(Request $request)
    {
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        $stats = $this->reports->getPeriodStats($startDate, Carbon::now());

        $growthPercent = $this->getGrowthPercent($stats['revenue']);
        $globalStats = $this->getGlobalAtelierStats();
        $stockAlerts = $this->getStockAlerts();
        $chequeAlerts = $this->getChequeAlerts();
        $recentActivity = $this->getRecentActivityFeed();

        return \Inertia\Inertia::render('DashboardApp', [
            'stats' => [
                'revenue_today' => $stats['revenue'],
                'services_revenue_today' => $stats['services_revenue'],
                'materials_revenue_today' => $stats['materials_revenue'],
                'profit_today' => $stats['net_profit'],
                'gross_profit' => $stats['gross_profit'],
                'total_expenses' => $stats['total_expenses'],
                'gross_purchases' => $stats['gross_purchases'],
                'supplier_returns' => $stats['supplier_returns'],
                'customer_returns' => $stats['customer_returns'],
                'margin_percent' => $stats['margin_percent'],
                'revenue_growth' => round($growthPercent, 1),
                'total_credit_market' => round($globalStats['total_unpaid_credit'], 2),
                'total_supplier_debt' => round($globalStats['total_supplier_debt'], 2),
                'total_orders_month' => $stats['orders_count'] + $stats['invoices_count'],
                'total_clients' => $globalStats['total_clients'],
                'clients_with_credit' => $globalStats['clients_with_credit'],
            ],
            'alerts' => array_merge($stockAlerts, $chequeAlerts, [
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

    private function getGrowthPercent(float $currentRevenue): float
    {
        $start = Carbon::now()->subMonth()->startOfMonth();
        $end = Carbon::now()->subMonth()->endOfMonth();
        $prevStats = $this->reports->getPeriodStats($start, $end);
        $prevRevenue = $prevStats['revenue'];

        if ($prevRevenue > 0) {
            return (($currentRevenue - $prevRevenue) / $prevRevenue) * 100;
        }

        return $currentRevenue > 0 ? 100 : 0;
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
            'low_stock_count' => count($cantoAlerts) + count($panelAlerts),
        ];
    }

    private function getChequeAlerts(): array
    {
        $upcomingCheques = SupplierPayment::withoutGlobalScopes()
            ->with(['supplier:id,name'])
            ->where('payment_method', 'check')
            ->where('status', 'pending')
            ->whereNotNull('cash_date')
            ->where('cash_date', '<=', Carbon::now()->addDays(3)->toDateString())
            ->get(['id', 'supplier_id', 'amount', 'cash_date'])
            ->map(function ($cheque) {
                return [
                    'id' => $cheque->id,
                    'supplier_name' => $cheque->supplier?->name ?? 'Inconnu',
                    'amount' => round($cheque->amount, 2),
                    'cash_date' => $cheque->cash_date,
                    'days_remaining' => Carbon::parse($cheque->cash_date)->diffInDays(Carbon::now(), false) * -1
                ];
            });

        return [
            'upcoming_cheques' => $upcomingCheques,
            'upcoming_cheques_count' => $upcomingCheques->count()
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
