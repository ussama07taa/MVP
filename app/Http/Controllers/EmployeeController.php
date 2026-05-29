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
     * Record an advance for an employee.
     */
    public function advance(Request $request, $id)
    {
        $request->validate(['amount' => 'required|numeric|min:0.1']);
        $tenantId = auth()->user()->tenant_id;
        
        DB::transaction(function() use ($request, $id, $tenantId) {
            $employee = Employee::where('tenant_id', $tenantId)->findOrFail($id);
            
            EmployeeAdvance::create([
                'tenant_id' => $tenantId,
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
                'amount' => $request->amount,
                'is_deducted' => false
            ]);
            
            $employee->increment('total_advances', $request->amount);
        });

        return response()->json(['message' => 'Avance enregistrée avec succès']);
    }

    /**
     * Process payroll/payment for an employee.
     */
    public function pay(Request $request, $id)
    {
        $tenantId = auth()->user()->tenant_id;
        
        DB::transaction(function() use ($id, $tenantId) {
            $employee = Employee::where('tenant_id', $tenantId)->findOrFail($id);
            
            EmployeeAttendance::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->where('is_paid', false)
                ->update(['is_paid' => true]);
                
            $employee->update(['total_advances' => 0]);
            
            EmployeeAdvance::where('tenant_id', $tenantId)
                ->where('employee_id', $employee->id)
                ->where('is_deducted', false)
                ->update(['is_deducted' => true]);
        });

        return response()->json(['message' => 'Employé payé avec succès']);
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
