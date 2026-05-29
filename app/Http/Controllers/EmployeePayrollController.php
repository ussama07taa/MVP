<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PayrollService;
use App\Models\EmployeeAdvance;
use App\Models\Employee;
use Carbon\Carbon;

class EmployeePayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // Default to current week (Monday to Saturday)
        $now = Carbon::now();
        $startDate = $request->query('start_date', $now->startOfWeek()->format('Y-m-d'));
        $endDate = $request->query('end_date', $now->endOfWeek()->format('Y-m-d'));

        $payroll = $this->payrollService->calculateWeeklyPayroll($tenantId, $startDate, $endDate);

        return response()->json($payroll);
    }

    public function close(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

        $tenantId = auth()->user()->tenant_id;
        
        // Recalculate to ensure data integrity during closure
        $payroll = $this->payrollService->calculateWeeklyPayroll($tenantId, $request->start_date, $request->end_date);
        
        $this->payrollService->finalizeWeeklyPayroll($tenantId, $payroll);

        return response()->json(['message' => 'La paie a été clôturée avec succès. Les historiques ont été enregistrés.']);
    }

    public function employeeHistory(Request $request, $employeeId)
    {
        $tenantId = auth()->user()->tenant_id;
        
        // ---- 1. Payroll History (after payroll closure) ----
        $query = \App\Models\PaySlip::where('tenant_id', $tenantId)->where('employee_id', $employeeId);
        if ($request->has('year') && $request->year) { $query->whereYear('period_end', $request->year); }
        if ($request->has('month') && $request->month) { $query->whereMonth('period_end', $request->month); }
        
        $history = $query->orderBy('period_end', 'desc')->get()->map(function($slip) {
            return [
                'id' => $slip->id,
                'start_date' => $slip->period_start,
                'end_date' => $slip->period_end,
                'days_worked' => (float)$slip->days_worked,
                'overtime_hours' => (float)$slip->overtime_hours_total,
                'gross_amount' => (float)$slip->base_wages_total + (float)$slip->overtime_wages_total + (float)$slip->bonuses_total,
                'advances_deducted' => (float)$slip->advances_total,
                'sanctions_deducted' => (float)$slip->sanctions_total,
                'net_paid' => (float)$slip->net_paid,
                'is_advance_only' => false
            ];
        });

        // ---- 2. Pending Adjustments (Advances, Bonuses, Sanctions - not yet deducted) ----
        $advancesQuery = EmployeeAdvance::where('tenant_id', $tenantId)
            ->where('employee_id', $employeeId)
            ->where('is_deducted', false);

        if ($request->has('year') && $request->year) { $advancesQuery->whereYear('date', $request->year); }
        if ($request->has('month') && $request->month) { $advancesQuery->whereMonth('date', $request->month); }

        $pendingAdjustments = $advancesQuery->orderBy('date', 'desc')->get()->map(function($adv) {
            return [
                'id'                => 'adj_' . $adv->id,
                'start_date'        => $adv->date,
                'end_date'          => $adv->date,
                'type'              => $adv->type, // advance, bonus, sanction
                'days_worked'       => 0,
                'gross_amount'      => $adv->type === 'bonus' ? (float)$adv->amount : 0,
                'advances_deducted' => $adv->type === 'advance' ? (float)$adv->amount : 0,
                'sanctions_deducted' => $adv->type === 'sanction' ? (float)$adv->amount : 0,
                'amount'            => (float)$adv->amount,
                'net_paid'          => 0,
                'is_advance_only'   => true, 
            ];
        });

        // Merge payroll history with pending adjustments
        $allHistory = $history->toArray();
        foreach ($pendingAdjustments as $adj) {
            $allHistory[] = $adj;
        }

        // Sort by date descending
        usort($allHistory, fn($a, $b) => strcmp($b['end_date'], $a['end_date']));

        // ---- 3. Stats ----
        $employee = Employee::where('tenant_id', $tenantId)->find($employeeId);
        $totalAdjBonus = $pendingAdjustments->where('type', 'bonus')->sum('amount');
        $totalAdjDeduct = $pendingAdjustments->where('type', 'advance')->sum('amount') + $pendingAdjustments->where('type', 'sanction')->sum('amount');
        
        $stats = [
            'total_net'          => (float)$history->sum('net_paid'),
            'total_advances'     => (float)$history->sum('advances_deducted') + (float)$history->sum('sanctions_deducted') + $totalAdjDeduct,
            'total_days_worked'  => (float)$history->sum('days_worked'),
            'total_overtime'     => (float)$history->sum('overtime_hours'),
            'count'              => $history->count(),
            'employee_name'      => $employee ? $employee->name : '',
        ];

        return response()->json([
            'history' => array_values($allHistory),
            'stats'   => $stats
        ]);
    }
}
