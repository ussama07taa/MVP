<?php

namespace App\Services;

use App\Models\{
    Order, OrderLine, Invoice, InvoiceItem, OrderReturn, OrderReturnLine,
    Expense, Payment, Purchase, SupplierPayment, Supplier, PurchaseReturn
};
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for P&L and treasury metrics.
 */
class FinancialReportService
{
    public function getPeriodStats(Carbon $start, Carbon $end): array
    {
        $orderIds = Order::withoutGlobalScopes()
            ->whereBetween('created_at', [$start, $end])
            ->where('status', '!=', 'devis')
            ->pluck('id');

        $posGross = (float) Order::withoutGlobalScopes()
            ->whereIn('id', $orderIds)
            ->sum('total_sell_price');

        $invoiceIds = Invoice::withoutGlobalScopes()
            ->where('type', 'invoice')
            ->whereNotNull('validated_at')
            ->whereBetween('validated_at', [$start, $end])
            ->pluck('id');

        $invoiceGross = (float) Invoice::withoutGlobalScopes()
            ->whereIn('id', $invoiceIds)
            ->sum('total');

        $customerReturns = (float) OrderReturn::withoutGlobalScopes()
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_refunded');

        $grossRevenue = $posGross + $invoiceGross;
        $netRevenue = $grossRevenue - $customerReturns;

        $posCogs = $orderIds->isEmpty() ? 0.0 : (float) OrderLine::withoutGlobalScopes()
            ->whereIn('order_id', $orderIds)
            ->sum('total_line_cost');

        $invoiceCogs = $invoiceIds->isEmpty() ? 0.0 : (float) (InvoiceItem::whereIn('invoice_id', $invoiceIds)
            ->selectRaw('SUM(unit_cost * quantity) as total_cost')
            ->value('total_cost') ?? 0);

        $returnCogs = (float) (OrderReturnLine::query()
            ->join('order_returns', 'order_return_lines.order_return_id', '=', 'order_returns.id')
            ->join('order_lines', 'order_return_lines.order_line_id', '=', 'order_lines.id')
            ->whereBetween('order_returns.created_at', [$start, $end])
            ->selectRaw('SUM(order_return_lines.quantity_returned * order_lines.unit_buy_price) as total')
            ->value('total') ?? 0);

        $cogs = max(0, $posCogs + $invoiceCogs - $returnCogs);
        $grossProfit = $netRevenue - $cogs;

        $otherExpenses = (float) Expense::withoutGlobalScopes()
            ->whereBetween('expense_date', [$start, $end])
            ->where('category', '!=', 'salaire')
            ->sum('amount');

        $monthlyWages = (float) DB::table('employee_attendances')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->sum('wage_earned');

        $opex = $otherExpenses + $monthlyWages;
        $netProfit = $grossProfit - $opex;
        $marginPercent = $netRevenue > 0 ? ($netProfit / $netRevenue) * 100 : 0;

        $cashCollected = (float) Payment::withoutGlobalScopes()
            ->whereBetween('created_at', [$start, $end])
            ->where('type', '!=', 'retour')
            ->sum('amount');

        $supplierPayments = SupplierPayment::withoutGlobalScopes()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('payment_method, SUM(amount) as total')
            ->groupBy('payment_method')
            ->get();

        $purchasesPaidCash = (float) $supplierPayments->where('payment_method', 'cash')->sum('total');
        $purchasesPaidBank = (float) $supplierPayments->whereIn('payment_method', ['check', 'transfer'])->sum('total');
        $purchasesPaid = $purchasesPaidCash + $purchasesPaidBank;

        $grossPurchases = (float) Purchase::withoutGlobalScopes()
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_amount');

        $supplierReturns = (float) PurchaseReturn::withoutGlobalScopes()
            ->whereBetween('created_at', [$start, $end])
            ->sum('refund_amount');

        $netCashFlow = $cashCollected - $otherExpenses - $monthlyWages - $purchasesPaidCash;

        $servicesRevenue = 0.0;
        if ($orderIds->isNotEmpty()) {
            $servicesRevenue += (float) OrderLine::withoutGlobalScopes()
                ->whereIn('order_id', $orderIds)
                ->where('item_type', \App\Models\Service::class)
                ->sum('total_line_sell');
        }
        if ($invoiceIds->isNotEmpty()) {
            $servicesRevenue += (float) InvoiceItem::whereIn('invoice_id', $invoiceIds)
                ->where('category', 'service')
                ->sum('total');
        }

        $orderCount = $orderIds->count();

        return [
            'gross_revenue' => round($grossRevenue, 2),
            'revenue' => round($netRevenue, 2),
            'customer_returns' => round($customerReturns, 2),
            'cogs' => round($cogs, 2),
            'gross_margin' => round($grossProfit, 2),
            'gross_profit' => round($grossProfit, 2),
            'opex' => round($opex, 2),
            'total_expenses' => round($opex, 2),
            'net_profit' => round($netProfit, 2),
            'margin_percent' => round($marginPercent, 1),
            'margin_percentage' => round($marginPercent, 1),
            'net_margin_percent' => round($marginPercent, 1),
            'cash_collected' => round($cashCollected, 2),
            'unpaid_revenue' => round(max(0, $netRevenue - $cashCollected), 2),
            'net_cash_flow' => round($netCashFlow, 2),
            'total_purchases' => round($grossPurchases, 2),
            'gross_purchases' => round($grossPurchases, 2),
            'supplier_returns' => round($supplierReturns, 2),
            'net_purchases' => round($grossPurchases - $supplierReturns, 2),
            'purchases_paid' => round($purchasesPaid, 2),
            'purchases_paid_cash' => round($purchasesPaidCash, 2),
            'purchases_paid_bank' => round($purchasesPaidBank, 2),
            'services_revenue' => round($servicesRevenue, 2),
            'materials_revenue' => round($netRevenue - $servicesRevenue, 2),
            'order_count' => $orderCount,
            'orders_count' => $orderCount,
            'invoices_count' => $invoiceIds->count(),
            'supplier_debt' => round((float) Supplier::withoutGlobalScopes()->sum('total_debt'), 2),
        ];
    }

    public function getMonthlyStats(int $month, int $year): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = (clone $start)->endOfMonth();
        $stats = $this->getPeriodStats($start, $end);

        $stats['month_name'] = $start->translatedFormat('F Y');
        $stats['month'] = $month;
        $stats['year'] = $year;
        $stats['avg_order_value'] = $stats['order_count'] > 0
            ? round($stats['gross_revenue'] / $stats['order_count'], 2)
            : 0;

        return $stats;
    }
}
