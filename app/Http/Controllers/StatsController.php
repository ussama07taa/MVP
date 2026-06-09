<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\FinancialReportService;
use Carbon\Carbon;

class StatsController extends Controller
{
    public function __construct(private FinancialReportService $reports)
    {
    }

    public function getStats(Request $request)
    {
        $year = $request->query('year', Carbon::now()->year);
        $month = $request->query('month', Carbon::now()->month);
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $stats = $this->reports->getPeriodStats($start, $end);

        return response()->json([
            'stats' => [
                'revenue' => $stats['revenue'],
                'gross_purchases' => $stats['gross_purchases'],
                'total_returns' => $stats['supplier_returns'],
                'net_purchases' => $stats['net_purchases'],
                'total_purchases' => $stats['net_purchases'],
                'supplier_debt' => $stats['supplier_debt'],
                'customer_returns' => $stats['customer_returns'],
                'net_profit' => $stats['net_profit'],
                'margin_percent' => $stats['margin_percent'],
            ],
        ]);
    }
}
