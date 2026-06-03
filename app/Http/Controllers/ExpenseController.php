<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller {
    public function index(Request $request) {
        $targetMonth = $request->query('month'); // YYYY-MM
        
        $now = $targetMonth ? Carbon::parse($targetMonth)->endOfMonth() : Carbon::now();
        $startOfMonth = (clone $now)->startOfMonth();
        $endOfMonth = (clone $now)->endOfMonth();
        $startOfLastMonth = (clone $startOfMonth)->subMonth()->startOfMonth();
        $endOfLastMonth = (clone $startOfMonth)->subMonth()->endOfMonth();

        // All expenses for the table
        $expenses = Expense::withoutGlobalScopes()->orderBy('expense_date', 'desc')->get();

        // Stats queries - SCRICT BOUNDARIES
        $thisMonth = Expense::withoutGlobalScopes()->whereBetween('expense_date', [$startOfMonth, $endOfMonth]);
        $lastMonth = Expense::withoutGlobalScopes()->whereBetween('expense_date', [$startOfLastMonth, $endOfLastMonth]);

        $totalThisMonth = (clone $thisMonth)->sum('amount');
        $totalLastMonth = (clone $lastMonth)->sum('amount');

        // REVENUE CALCULATION (Invoices & POS Orders)
        $revenueInvoices = Invoice::withoutGlobalScopes()
            ->where('type', 'invoice')
            ->whereNotNull('validated_at')
            ->whereBetween('validated_at', [$startOfMonth, $endOfMonth])
            ->sum('total');
            
        $revenuePOS = Order::withoutGlobalScopes()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_sell_price');
            
        $totalRevenue = $revenueInvoices + $revenuePOS;
        $netProfit = $totalRevenue - $totalThisMonth;

        // FIXED CHARGE LOGIC (Grouped)
        $fixedCategories = ['Charge Fixe (Mensuel)', 'Loyer', 'Salaire', '🏠 Loyer (K-ra)', '👥 Salaires (Kheddama)'];
        $totalFixed = (clone $thisMonth)->where(function($query) use ($fixedCategories) {
                $query->whereIn('category', $fixedCategories)
                      ->orWhere('category', 'LIKE', '%fixe%');
            })->sum('amount');

        $totalVariable = $totalThisMonth - $totalFixed;

        $trend = 0;
        if ($totalLastMonth > 0) {
            $trend = (($totalThisMonth - $totalLastMonth) / $totalLastMonth) * 100;
        }

        // Analytical History (Last 12 Months)
        $history = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = (clone $now)->subMonths($i);
            $history[] = [
                'month' => $m->format('M'),
                'label' => $m->translatedFormat('F Y'),
                'total' => Expense::withoutGlobalScopes()
                    ->whereMonth('expense_date', $m->month)
                    ->whereYear('expense_date', $m->year)
                    ->sum('amount')
            ];
        }

        // Yearly Total (Current Year as of target date)
        $totalYear = Expense::withoutGlobalScopes()
            ->whereYear('expense_date', $now->year)
            ->where('expense_date', '<=', $endOfMonth)
            ->sum('amount');

        // Category Breakdown (This Month)
        $categories = DB::table('expenses')
            ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->select('category', DB::raw('sum(amount) as total'))
            ->groupBy('category')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'expenses' => $expenses,
            'stats' => [
                'total_this_month' => $totalThisMonth,
                'total_fixed' => $totalFixed,
                'total_variable' => $totalVariable,
                'total_year' => $totalYear,
                'trend' => round($trend, 1),
                'total_last_month' => $totalLastMonth,
                'total_revenue' => $totalRevenue,
                'net_profit' => $netProfit
            ],
            'history' => $history,
            'categories' => $categories
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
        $data['tenant_id'] = 1;
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
