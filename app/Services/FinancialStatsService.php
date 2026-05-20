<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialStatsService
{
    public function getMonthlyStats($month, $year, $tenantId)
    {
        $dateObj = Carbon::createFromDate($year, $month, 1);

        // 1. Revenue (Net of Devis)
        $revenue = DB::table('orders')
                        ->where('tenant_id', $tenantId)
                        ->whereMonth('created_at', $month)
                        ->whereYear('created_at', $year)
                        ->where('status', '!=', 'devis')
                        ->sum('total_sell_price');

        // 2. COGS (Cost of Goods Sold)
        $cogs = DB::table('order_lines')
            ->join('orders', 'order_lines.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', $tenantId)
            ->where('orders.status', '!=', 'devis')
            ->whereMonth('orders.created_at', $month)
            ->whereYear('orders.created_at', $year)
            ->sum('total_line_cost');

        // 3. OPEX (Operating Expenses)
        $otherExpenses = DB::table('expenses')
                                ->where('tenant_id', $tenantId)
                                ->whereMonth('expense_date', $month)
                                ->whereYear('expense_date', $year)
                                ->where('category', '!=', 'salaire')
                                ->sum('amount');
        
        $monthlyWages = DB::table('employee_attendances')
                          ->where('tenant_id', $tenantId)
                          ->whereMonth('date', $month)
                          ->whereYear('date', $year)
                          ->sum('wage_earned');

        $opex = (float)$otherExpenses + (float)$monthlyWages;

        // 4. Cash Flow (Collected vs Credit)
        $totalCollected = (float)DB::table('orders')
                                ->where('tenant_id', $tenantId)
                                ->whereMonth('created_at', $month)
                                ->whereYear('created_at', $year)
                                ->where('status', '!=', 'devis')
                                ->sum('amount_paid');

        $unpaidRevenue = (float)$revenue - $totalCollected;

        $orderCount = DB::table('orders')
                        ->where('tenant_id', $tenantId)
                        ->whereMonth('created_at', $month)
                        ->whereYear('created_at', $year)
                        ->where('status', '!=', 'devis')
                        ->count();

        $averageOrderValue = $orderCount > 0 ? $revenue / $orderCount : 0;

        // 5. Purchases (Achats)
        $purchases = DB::table('purchases')
                        ->where('tenant_id', $tenantId)
                        ->whereMonth('created_at', $month)
                        ->whereYear('created_at', $year);
        
        $totalPurchases = (float)$purchases->sum('total_amount');
        $purchasesPaid = (float)$purchases->sum('amount_paid');

        // 6. Net Cash Flow (Trésorerie Réelle)
        // Cash In = totalCollected
        // Cash Out = otherExpenses + monthlyWages + purchasesPaid
        $cashOut = (float)$otherExpenses + (float)$monthlyWages + (float)$purchasesPaid;
        $netCashFlow = (float)$totalCollected - $cashOut;

        $grossMargin = (float)$revenue - (float)$cogs;
        $netProfit = $grossMargin - $opex;
        $marginPercentage = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        return [
            'month_name' => $dateObj->translatedFormat('F Y'),
            'revenue' => (float)$revenue,
            'order_count' => $orderCount,
            'avg_order_value' => round($averageOrderValue, 2),
            'cogs' => (float)$cogs,
            'gross_margin' => (float)$grossMargin,
            'opex' => (float)$opex,
            'net_profit' => (float)$netProfit,
            'margin_percentage' => round($marginPercentage, 1),
            'cash_collected' => (float)$totalCollected,
            'unpaid_revenue' => (float)$unpaidRevenue,
            'total_purchases' => (float)$totalPurchases,
            'purchases_paid' => (float)$purchasesPaid,
            'net_cash_flow' => (float)$netCashFlow,
        ];
    }
}
