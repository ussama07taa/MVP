<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\{StockExport, OrderExport, ClientExport, ExpenseExport, ProfitExport, StockTemplateExport};
use App\Services\FinancialStatsService;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    public function export(Request $request, $type, FinancialStatsService $statsService)
    {
        $tenantId = $request->user()->tenant_id;
        $fileName = "{$type}_export_" . now()->format('Y_m_d_His') . ".xlsx";

        $exportClass = match($type) {
            'stock' => new StockExport($tenantId),
            'orders' => new OrderExport($tenantId),
            'clients' => new ClientExport($tenantId),
            'expenses' => new ExpenseExport($tenantId),
            'profits' => (function() use ($request, $tenantId, $statsService) {
                $month = $request->query('month', now()->month);
                $year = $request->query('year', now()->year);
                $data = $statsService->getMonthlyStats($month, $year, $tenantId);
                return new ProfitExport($data, $data['month_name']);
            })(),
            default => abort(400, 'Invalid export type'),
        };

        return Excel::download($exportClass, $fileName);
    }

    public function downloadTemplate()
    {
        return Excel::download(new StockTemplateExport, 'modele_stock_initial.xlsx');
    }
}
