<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller {
    public function index() {
        $expenses = Expense::withoutGlobalScopes()->orderBy('expense_date', 'desc')->get();
        
        $startOfMonth = Carbon::now()->startOfMonth();
        $startOfLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $thisMonth = Expense::withoutGlobalScopes()->where('expense_date', '>=', $startOfMonth);
        $lastMonth = Expense::withoutGlobalScopes()->whereBetween('expense_date', [$startOfLastMonth, $endOfLastMonth]);

        $totalThisMonth = (clone $thisMonth)->sum('amount');
        $totalLastMonth = (clone $lastMonth)->sum('amount');

        $fixedCategories = ['Charge Fixe (Mensuel)', 'Loyer', 'Salaire', '🏠 Loyer (K-ra)', '👥 Salaires (Kheddama)'];
        $totalFixed = (clone $thisMonth)->whereIn('category', $fixedCategories)
            ->orWhere(function($query) use ($startOfMonth) {
                $query->withoutGlobalScopes()->where('expense_date', '>=', $startOfMonth)
                      ->where('category', 'LIKE', '%fixe%');
            })->sum('amount');

        $totalVariable = $totalThisMonth - $totalFixed;

        $trend = 0;
        if ($totalLastMonth > 0) {
            $trend = (($totalThisMonth - $totalLastMonth) / $totalLastMonth) * 100;
        }

        return response()->json([
            'expenses' => $expenses,
            'stats' => [
                'total_this_month' => $totalThisMonth,
                'total_fixed' => $totalFixed,
                'total_variable' => $totalVariable,
                'trend' => round($trend, 1),
                'total_last_month' => $totalLastMonth
            ]
        ]);
    }
    
    public function store(Request $request) {
        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
            'is_recurring' => 'nullable',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'
        ]);

        $data = $request->only(['title', 'category', 'amount', 'expense_date', 'notes']);
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['is_recurring'] = $request->is_recurring == '1' || $request->is_recurring === true;

        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('expenses_attachments', 'public');
            $data['attachment'] = Storage::url($path);
        }

        $expense = Expense::create($data);
        
        return response()->json(['message' => 'Dépense enregistrée', 'expense' => $expense]);
    }
    
    public function destroy($id) {
        $expense = Expense::withoutGlobalScopes()->findOrFail($id);
        
        // Delete attachment if exists
        if ($expense->attachment) {
            $oldPath = str_replace('/storage/', '', $expense->attachment);
            Storage::disk('public')->delete($oldPath);
        }

        $expense->delete();
        return response()->json(['message' => 'Supprimé']);
    }
}
