<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeAdvance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Calculate weekly payroll for all active employees in a given tenant.
     */
    public function calculateWeeklyPayroll($tenantId, $startDate, $endDate)
    {
        $employees = Employee::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['attendances' => function($query) use ($startDate, $endDate) {
                $query->whereBetween('date', [$startDate, $endDate]);
            }, 'advances' => function($query) {
                $query->where('is_deducted', false);
            }])
            ->get();

        $payroll = [];
        $grandTotal = 0;

        foreach ($employees as $employee) {
            $attendances = $employee->attendances;
            
            // Calculate days worked based on status
            $presentDays = (float)$attendances->where('status', 'present')->count();
            $halfDays = (float)$attendances->where('status', 'half_day')->count();
            $daysWorked = $presentDays + ($halfDays * 0.5);
            
            $baseWages = (float) $attendances->sum('wage_earned');
            $overtimeHours = (float) $attendances->sum('overtime_hours');
            $overtimeWages = (float) $attendances->sum('overtime_wage');
            
            // Fetch adjustments
            $adjustments = $employee->advances;
            $bonusesSum = (float)$adjustments->where('type', 'bonus')->sum('amount');
            $advancesSum = (float)$adjustments->where('type', 'advance')->sum('amount');
            $sanctionsSum = (float)$adjustments->where('type', 'sanction')->sum('amount');
            
            $grossPay = $baseWages + $overtimeWages + $bonusesSum;
            $totalDeductions = $advancesSum + $sanctionsSum;
            $netPay = max(0, $grossPay - $totalDeductions);

            $payroll[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'daily_salary' => (float)$employee->daily_salary,
                'days_worked' => $daysWorked,
                'present_days' => $presentDays,
                'half_days' => $halfDays,
                'overtime_hours' => $overtimeHours,
                'base_wages' => $baseWages,
                'overtime_wages' => $overtimeWages,
                'bonuses' => $bonusesSum,
                'advances' => $advancesSum,
                'sanctions' => $sanctionsSum,
                'gross_pay' => $grossPay,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay
            ];
            
            $grandTotal += $netPay;
        }

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'details' => $payroll,
            'grand_total' => $grandTotal
        ];
    }

    /**
     * Finalize the payroll for a period:
     * 1. Create PaySlip records.
     * 2. Mark advances as deducted.
     */
    public function finalizeWeeklyPayroll($tenantId, $payrollData)
    {
        return DB::transaction(function() use ($tenantId, $payrollData) {
            $startDate = $payrollData['period']['start'];
            $endDate = $payrollData['period']['end'];

            foreach ($payrollData['details'] as $entry) {
                // 1. Create PaySlip Record (Professional Snapshot)
                \App\Models\PaySlip::create([
                    'tenant_id' => $tenantId,
                    'employee_id' => $entry['employee_id'],
                    'days_worked' => $entry['days_worked'],
                    'overtime_hours_total' => $entry['overtime_hours'],
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'base_wages_total' => $entry['base_wages'],
                    'overtime_wages_total' => $entry['overtime_wages'],
                    'bonuses_total' => $entry['bonuses'],
                    'advances_total' => $entry['advances'],
                    'sanctions_total' => $entry['sanctions'],
                    'net_paid' => $entry['net_pay'],
                    'status' => 'paid',
                    'payment_date' => now()->toDateString(),
                ]);

                // 2. Mark advances as deducted for this specific employee
                \App\Models\EmployeeAdvance::where('tenant_id', $tenantId)
                    ->where('employee_id', $entry['employee_id'])
                    ->where('is_deducted', false)
                    ->update(['is_deducted' => true]);

                // 3. Mark attendances as paid
                \App\Models\EmployeeAttendance::where('tenant_id', $tenantId)
                    ->where('employee_id', $entry['employee_id'])
                    ->whereBetween('date', [$startDate, $endDate])
                    ->update(['is_paid' => true]);
            }

            return true;
        });
    }

    /**
     * Close the payroll for a period: mark advances as deducted.
     * (Deprecated in favor of finalizeWeeklyPayroll)
     */
    public function closePayroll($tenantId, $employeeIds)
    {
        return DB::transaction(function() use ($tenantId, $employeeIds) {
            return EmployeeAdvance::where('tenant_id', $tenantId)
                ->whereIn('employee_id', $employeeIds)
                ->where('is_deducted', false)
                ->update(['is_deducted' => true]);
        });
    }
}
