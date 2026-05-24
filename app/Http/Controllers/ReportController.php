<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\{Client, StockPanel, StockCanto, Order, Expense};
use App\Services\FinancialStatsService;

class ReportController extends Controller
{
    protected FinancialStatsService $financialStats;

    public function __construct(FinancialStatsService $financialStats)
    {
        $this->financialStats = $financialStats;
    }

    public function generate(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2020,2099',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $tenantId = auth()->user()->tenant_id;

        $dateObj = Carbon::createFromDate($year, $month, 1);
        $monthName = $dateObj->translatedFormat('F Y');

        // 1. Financial Stats (reuse existing service)
        $financial = $this->financialStats->getMonthlyStats($month, $year, $tenantId);

        // 2. Top Clients
        $topClients = DB::table('orders')
            ->select('client_id', DB::raw('SUM(total_sell_price) as total_revenue'), DB::raw('COUNT(*) as order_count'))
            ->where('tenant_id', $tenantId)
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('status', '!=', 'devis')
            ->groupBy('client_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                $client = Client::withoutGlobalScopes()->find($row->client_id);
                return [
                    'name' => $client->name ?? 'Inconnu',
                    'total_revenue' => (float) $row->total_revenue,
                    'order_count' => $row->order_count,
                ];
            });

        // 3. Top Services
        $topServices = DB::table('workshop_queue_services')
            ->join('workshop_queues', 'workshop_queue_services.workshop_queue_id', '=', 'workshop_queues.id')
            ->select('workshop_queue_services.service_name', DB::raw('COUNT(*) as count'), DB::raw('SUM(workshop_queue_services.total_price) as total_revenue'))
            ->where('workshop_queues.tenant_id', $tenantId)
            ->whereMonth('workshop_queues.created_at', $month)
            ->whereYear('workshop_queues.created_at', $year)
            ->groupBy('workshop_queue_services.service_name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // 4. Stock Status
        $stockPanels = StockPanel::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->select('type', 'color_name', 'size_x', 'size_y', 'quantity', 'cost_price')
            ->orderBy('quantity')
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return [
                    'name' => "{$p->type} {$p->color_name} ({$p->size_x}x{$p->size_y})",
                    'quantity' => $p->quantity,
                    'value' => $p->quantity * $p->cost_price,
                    'status' => $p->quantity <= 2 ? 'critical' : ($p->quantity <= 5 ? 'low' : 'ok'),
                ];
            });

        $stockCantos = StockCanto::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->select('color_name', 'color_code', 'total_length_remaining', 'cost_price_per_m')
            ->orderBy('total_length_remaining')
            ->limit(10)
            ->get()
            ->map(function ($c) {
                return [
                    'name' => "Bandchant {$c->color_name} ({$c->color_code})",
                    'quantity' => $c->total_length_remaining,
                    'unit' => 'm',
                    'value' => $c->total_length_remaining * $c->cost_price_per_m,
                    'status' => $c->total_length_remaining <= 5 ? 'critical' : ($c->total_length_remaining <= 20 ? 'low' : 'ok'),
                ];
            });

        // 5. Expense breakdown by category
        $expensesByCategory = DB::table('expenses')
            ->select('category', DB::raw('SUM(amount) as total'))
            ->where('tenant_id', $tenantId)
            ->whereMonth('expense_date', $month)
            ->whereYear('expense_date', $year)
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();

        // 6. Worker Performance
        $workerPerformance = DB::table('workshop_queue_services')
            ->join('workshop_queues', 'workshop_queue_services.workshop_queue_id', '=', 'workshop_queues.id')
            ->select('workshop_queue_services.done_by', DB::raw('COUNT(*) as services_done'))
            ->where('workshop_queues.tenant_id', $tenantId)
            ->whereMonth('workshop_queues.created_at', $month)
            ->whereYear('workshop_queues.created_at', $year)
            ->where('workshop_queue_services.status', 'done')
            ->whereNotNull('workshop_queue_services.done_by')
            ->groupBy('workshop_queue_services.done_by')
            ->orderByDesc('services_done')
            ->limit(10)
            ->get();

        // 7. Company settings for header
        $settings = DB::table('settings')->where('tenant_id', $tenantId)->pluck('value', 'key');

        $data = [
            'monthName' => $monthName,
            'month' => $month,
            'year' => $year,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'financial' => $financial,
            'topClients' => $topClients,
            'topServices' => $topServices,
            'stockPanels' => $stockPanels,
            'stockCantos' => $stockCantos,
            'expensesByCategory' => $expensesByCategory,
            'workerPerformance' => $workerPerformance,
            'settings' => $settings,
        ];

        $pdf = Pdf::loadView('reports.monthly', $data);
        $pdf->setPaper('a4', 'portrait');

        $filename = "rapport-mensuel-{$year}-{$month}.pdf";

        return $pdf->download($filename);
    }

    public function preview(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|between:2020,2099',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $tenantId = auth()->user()->tenant_id;

        $financial = $this->financialStats->getMonthlyStats($month, $year, $tenantId);

        $dateObj = Carbon::createFromDate($year, $month, 1);

        return response()->json([
            'month_name' => $dateObj->translatedFormat('F Y'),
            'financial' => $financial,
        ]);
    }
}
