<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $monthStr = $request->input('month', now()->format('Y-m'));
        [$year, $month] = explode('-', $monthStr);

        $emps = Employee::latest()->get();

        foreach ($emps as $emp) {
            $pDays = $emp->attendances()->whereYear('date', $year)->whereMonth('date', $month)->where('status', 'present')->count();
            $hDays = $emp->attendances()->whereYear('date', $year)->whereMonth('date', $month)->where('status', 'half_day')->count();
            $worked = $pDays + ($hDays * 0.5);

            // Fields expected by EmployeesPage.vue
            $emp->present_days  = $pDays;
            $emp->half_days     = $hDays;
            $emp->worked_days   = $worked;
            $emp->earned_salary = round($worked * (float)$emp->daily_salary, 2);

            // Use sum of wage_earned for unpaid attendances
            $emp->unpaid_wages  = (float)$emp->attendances()->where('is_paid', false)->sum('wage_earned');

            // Net = max(0, unpaid wages - advances)
            $emp->net_to_pay    = round(max(0, $emp->unpaid_wages - (float)$emp->total_advances), 2);

            // Use unpaid_wages as the displayed "Salaire Gagné"
            $emp->earned_salary = $emp->unpaid_wages > 0 ? $emp->unpaid_wages : $emp->earned_salary;
        }

        return $emps;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());
        $tenantId = auth()->user()->tenant_id;

        return Employee::create(array_merge($validated, ['tenant_id' => $tenantId]));
    }

    /**
     * Record an adjustment (advance, bonus, sanction) for an employee.
     */
    public function adjustment(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'type'   => 'required|in:advance,bonus,sanction',
            'notes'  => 'nullable|string|max:500'
        ]);
        
        $tenantId = auth()->user()->tenant_id;
        
        DB::transaction(function() use ($request, $id, $tenantId) {
            $employee = Employee::where('tenant_id', $tenantId)->findOrFail($id);
            
            EmployeeAdvance::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
                'type' => $request->type,
                'amount' => $request->amount,
                'notes' => $request->notes,
                'is_deducted' => false
            ]);
            
            // For 'advance' and 'sanction', they act as debt (deductions)
            // For 'bonus', it acts as additional gain
            if ($request->type === 'bonus') {
                // Bonuses don't increment total_advances (debt); they are added during pay
            } else {
                $employee->increment('total_advances', $request->amount);
            }
        });

        return response()->json(['message' => 'Ajustement enregistré avec succès']);
    }

    /**
     * Process payroll/payment for an employee.
     */
    public function pay(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        
        return DB::transaction(function() use ($id, $tenantId) {
            $employee = Employee::where('tenant_id', $tenantId)->findOrFail($id);
            
            // 1. Gather stats from unpaid attendances
            $unpaidAttendances = EmployeeAttendance::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->where('is_paid', false)
                ->get();
            
            $presentDays = (float)$unpaidAttendances->where('status', 'present')->count();
            $halfDays = (float)$unpaidAttendances->where('status', 'half_day')->count();
            $daysWorked = $presentDays + ($halfDays * 0.5);
            $baseWages = (float)$unpaidAttendances->sum('wage_earned');
            $overtimeWages = (float)$unpaidAttendances->sum('overtime_wage');
            $overtimeHours = (float)$unpaidAttendances->sum('overtime_hours');
            
            // 2. Fetch pending adjustments (advances, bonuses, sanctions)
            $adjustments = EmployeeAdvance::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->where('is_deducted', false)
                ->get();
            
            $advancesSum = (float)$adjustments->where('type', 'advance')->sum('amount');
            $bonusesSum = (float)$adjustments->where('type', 'bonus')->sum('amount');
            $sanctionsSum = (float)$adjustments->where('type', 'sanction')->sum('amount');
            
            $grossPay = $baseWages + $overtimeWages + $bonusesSum;
            $totalDeductions = $advancesSum + $sanctionsSum;
            $netPay = max(0, $grossPay - $totalDeductions);

            if ($netPay <= 0 && $grossPay <= 0) {
                 return response()->json(['message' => 'Rien à payer (0 DH).'], 400);
            }

            // 3. Create formal PaySlip record for History/Profile page
            \App\Models\PaySlip::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employee->id,
                'period_start' => $unpaidAttendances->min('date') ?? now()->toDateString(),
                'period_end' => $unpaidAttendances->max('date') ?? now()->toDateString(),
                'days_worked' => $daysWorked,
                'overtime_hours_total' => $overtimeHours,
                'base_wages_total' => $baseWages,
                'overtime_wages_total' => $overtimeWages,
                'bonuses_total' => $bonusesSum,
                'advances_total' => $advancesSum,
                'sanctions_total' => $sanctionsSum,
                'net_paid' => $netPay,
                'status' => 'paid',
                'payment_date' => now()->toDateString(),
            ]);

            // 4. Mark everything as paid/deducted
            EmployeeAttendance::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->where('is_paid', false)
                ->update(['is_paid' => true]);
                
            $employee->update(['total_advances' => 0]);
            
            EmployeeAdvance::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->where('is_deducted', false)
                ->update(['is_deducted' => true]);

            // 5. Record as General Expense
            \App\Models\Expense::create([
                'tenant_id' => $tenantId,
                'category' => 'Salaire',
                'amount' => $netPay,
                'title' => "Paiement salaire : {$employee->name} (H.S: {$overtimeHours}h, Primes: {$bonusesSum}DH, Sanctions: {$sanctionsSum}DH)",
                'expense_date' => now()
            ]);

            return response()->json(['message' => 'Employé payé avec succès et historique mis à jour.']);
        });
    }

    /**
     * Validation rules used by store and update.
     *
     * @return array<string, string>
     */
    protected function rules(bool $forUpdate = false): array
    {
        return [
            'name'         => ($forUpdate ? 'sometimes|' : '') . 'required|string|max:255',
            'role'         => 'nullable|string|max:255',
            'phone'        => 'nullable|string|max:50',
            'daily_salary' => ($forUpdate ? 'sometimes|' : '') . 'required|numeric|min:0',
        ];
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate($this->rules(true));

        $employee = Employee::withoutGlobalScopes()->findOrFail($id);
        $employee->update($validated);

        return response()->json($employee);
    }

    public function destroy($id)
    {
        $employee = Employee::withoutGlobalScopes()->findOrFail($id);

        // Hard guard: refuse to destroy an employee that still has unpaid wages or undeducted advances.
        $unpaidWages = (float) $employee->attendances()->withoutGlobalScopes()->where('is_paid', false)->sum('wage_earned');
        $undeductedAdvances = (float) $employee->advances()->withoutGlobalScopes()->where('is_deducted', false)->sum('amount');

        if ($unpaidWages > 0 || $undeductedAdvances > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer un employé avec des salaires non payés ou des avances non déduites.',
            ], 422);
        }

        DB::transaction(function () use ($employee) {
            $employee->attendances()->withoutGlobalScopes()->delete();
            $employee->advances()->withoutGlobalScopes()->delete();
            $employee->delete();
        });

        return response()->json(['message' => 'Employé supprimé.']);
    }
}
