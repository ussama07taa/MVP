<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Expense;
use Carbon\Carbon;

class ProcessRecurringExpenses extends Command
{
    protected $signature = 'expenses:process-recurring';
    protected $description = 'Clone recurring expenses from last month into the current month';

    public function handle()
    {
        $now = Carbon::now();
        $startOfLastMonth = (clone $now)->subMonth()->startOfMonth();
        $endOfLastMonth = (clone $now)->subMonth()->endOfMonth();
        $startOfThisMonth = (clone $now)->startOfMonth();

        // Get all recurring expenses from last month
        $recurring = Expense::withoutGlobalScopes()
            ->where('is_recurring', true)
            ->whereBetween('expense_date', [$startOfLastMonth, $endOfLastMonth])
            ->get();

        $created = 0;

        foreach ($recurring as $expense) {
            // Check if already cloned this month (avoid duplicates)
            $exists = Expense::withoutGlobalScopes()
                ->where('title', $expense->title)
                ->where('category', $expense->category)
                ->where('amount', $expense->amount)
                ->where('is_recurring', true)
                ->whereBetween('expense_date', [$startOfThisMonth, $now->endOfMonth()])
                ->exists();

            if (!$exists) {
                Expense::create([
                    'title'        => $expense->title,
                    'category'     => $expense->category,
                    'amount'       => $expense->amount,
                    'expense_date' => $startOfThisMonth->format('Y-m-d'),
                    'notes'        => $expense->notes,
                    'is_recurring' => true,
                    'tenant_id'    => $expense->tenant_id ?? 1,
                ]);
                $created++;
            }
        }

        $this->info("✅ {$created} recurring expense(s) cloned for {$now->format('F Y')}.");
        return Command::SUCCESS;
    }
}
