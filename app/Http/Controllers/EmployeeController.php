<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
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
