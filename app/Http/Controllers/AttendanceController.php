<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the resource for a specific date.
     */
    public function index($date)
    {
        return EmployeeAttendance::whereDate('date', $date)
            ->get();
    }

    /**
     * Store a newly created resource in storage (batch).
     */
    public function store(Request $request)
    {
        $date = $request->input('date');
        $attendances = collect($request->input('attendances'));
        $employeeIds = $attendances->pluck('employee_id');

        DB::transaction(function() use ($date, $attendances, $employeeIds) {
            $employees = Employee::whereIn('id', $employeeIds)->lockForUpdate()->get()->keyBy('id');
            $upsertData = [];

            foreach ($attendances as $att) {
                $employee = $employees->get($att['employee_id']);
                if (!$employee) continue;

                $dailySalary = round((float) $employee->daily_salary, 2);
                
                // Allow database-driven taxonomy references instead of strict code matching when possible.
                // For now, mapping mathematically via ratio configuration variable logic.
                $wageRatio = match($att['status'] ?? 'absent') {
                    'present'  => 1.0,
                    'half_day' => 0.5,
                    'quarter_day' => 0.25,
                    default    => 0.0,
                };
                
                $wageEarned = round($dailySalary * $wageRatio, 2);

                $overtimeHours = (float) ($att['overtime_hours'] ?? 0);
                $overtimeWage = round(($dailySalary / 8) * $overtimeHours, 2);

                $upsertData[] = [
                    'tenant_id' => 1,
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'status' => $att['status'],
                    'wage_earned' => $wageEarned,
                    'overtime_hours' => $overtimeHours,
                    'overtime_wage' => $overtimeWage,
                    'notes' => $att['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (count($upsertData) > 0) {
                EmployeeAttendance::upsert(
                    $upsertData,
                    ['tenant_id', 'employee_id', 'date'],
                    ['status', 'wage_earned', 'overtime_hours', 'overtime_wage', 'notes', 'updated_at']
                );
            }
        });

        return response()->json(['message' => 'Pointage enregistré avec succès']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($employee_id, $date)
    {
        $deleted = EmployeeAttendance::where('employee_id', $employee_id)
            ->whereDate('date', $date)
            ->delete();

        return response()->json(['message' => $deleted ? 'Pointage supprimé' : 'Enregistrement non trouvé']);
    }
}
