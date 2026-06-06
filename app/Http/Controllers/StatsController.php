<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Purchase, PurchaseReturn, Supplier, Order, OrderLine, Client, StockCanto, Service, OrderReturn};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function getStats(Request $request)
    {
        $year = $request->query('year', Carbon::now()->year);
        $month = $request->query('month', Carbon::now()->month);
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        // 1. Calculate Total Gross Purchases for the month
        $grossPurchases = Purchase::withoutGlobalScopes()->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        // 2. Calculate Total Returns (Avoirs) for the month
        $totalReturns = PurchaseReturn::withoutGlobalScopes()->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('refund_amount');

        // 3. True Cost of Goods (Achats Nets)
        $netPurchases = $grossPurchases - $totalReturns;

        // 4. Supplier Debt (Dynamic sum of actual balances)
        $supplierDebt = Supplier::withoutGlobalScopes()->sum('total_debt');

        // 5. Client Revenue and Profit — Net Revenue = Gross - Returns
        $ordersThisMonth = Order::withoutGlobalScopes()->whereBetween('created_at', [$startOfMonth, $endOfMonth])->get();
        $grossRevenue = $ordersThisMonth->sum('total_sell_price');
        $customerReturns = OrderReturn::withoutGlobalScopes()
            ->whereIn('order_id', $ordersThisMonth->pluck('id'))
            ->sum('total_refunded');
        $revenueThisMonth = $grossRevenue - $customerReturns;
        $costThisMonth = $ordersThisMonth->sum('total_cost_price');
        $grossProfit = $revenueThisMonth - $costThisMonth;

        $salaryExpenses = \App\Models\Expense::withoutGlobalScopes()->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');
        
        $netProfit = $grossProfit - $salaryExpenses - $netPurchases;

        return response()->json([
            'stats' => [
                'revenue' => round($revenueThisMonth, 2),
                'gross_purchases' => round($grossPurchases, 2),
                'total_returns' => round($totalReturns, 2),
                'net_purchases' => round($netPurchases, 2),
                'total_purchases' => round($netPurchases, 2), // Alias for UI compatibility
                'supplier_debt' => round($supplierDebt, 2),
                'customer_returns' => round($customerReturns, 2),
                'net_profit' => round($netProfit, 2),
                'margin_percent' => $revenueThisMonth > 0 ? round(($netProfit / $revenueThisMonth) * 100, 1) : 0
            ]
        ]);
    }
}
