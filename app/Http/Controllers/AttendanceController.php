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
        $attendances = $request->input('attendances');
        
        DB::transaction(function() use ($date, $attendances) {
            foreach ($attendances as $att) {
                // Fetch employee to get daily_salary
                $employee = Employee::find($att['employee_id']);

                if (!$employee) continue;

                // Calculate wage earned based on status
                $dailySalary = (float) $employee->daily_salary;
                $wageEarned = match($att['status']) {
                    'present'  => $dailySalary,
                    'half_day' => $dailySalary / 2,
                    default    => 0.0,  // absent
                };

                $overtimeHours = (float) ($att['overtime_hours'] ?? 0);
                $overtimeWage = ($dailySalary / 8) * $overtimeHours;

                EmployeeAttendance::updateOrCreate(
                    ['tenant_id' => 1, 'employee_id' => $att['employee_id'], 'date' => $date],
                    [
                        'status' => $att['status'], 
                        'wage_earned' => $wageEarned,
                        'overtime_hours' => $overtimeHours,
                        'overtime_wage' => $overtimeWage,
                        'notes' => $att['notes'] ?? null
                    ]
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
