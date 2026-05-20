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
            $presentDays = $attendances->where('status', 'present')->count();
            $halfDays = $attendances->where('status', 'half_day')->count();
            $daysWorked = $presentDays + ($halfDays * 0.5);
            
            $baseWages = (float) $attendances->sum('wage_earned');
            $overtimeWages = (float) $attendances->sum('overtime_wage');
            $grossPay = $baseWages + $overtimeWages;
            
            // Sum all non-deducted advances
            $advancesSum = (float)$employee->advances->sum('amount');
            
            $netPay = max(0, $grossPay - $advancesSum);

            $payroll[] = [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'daily_salary' => (float)$employee->daily_salary,
                'days_worked' => $daysWorked,
                'present_days' => $presentDays,
                'half_days' => $halfDays,
                'base_wages' => $baseWages,
                'overtime_wages' => $overtimeWages,
                'gross_pay' => $grossPay,
                'advances' => $advancesSum,
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
                    'period_start' => $startDate,
                    'period_end' => $endDate,
                    'base_wages_total' => $entry['base_wages'],
                    'overtime_wages_total' => $entry['overtime_wages'],
                    'advances_total' => $entry['advances'],
                    'net_paid' => $entry['net_pay'],
                    'status' => 'paid',
                    'payment_date' => now()->toDateString(),
                ]);

                // 2. Mark advances as deducted for this specific employee
                EmployeeAdvance::where('tenant_id', $tenantId)
                    ->where('employee_id', $entry['employee_id'])
                    ->where('is_deducted', false)
                    ->update(['is_deducted' => true]);
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
