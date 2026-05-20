<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Client, Order, OrderLine, StockPanel, Service, Expense, Employee, EmployeeAttendance, Tenant};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DemoFinancialSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = 1;
        $now = Carbon::now();
        $month = 5; // May
        $year = 2026;

        // 1. Create Clients
        $clients = [
            ['name' => 'Ahmed Amine', 'phone' => '0611223344', 'total_credit' => 0],
            ['name' => 'Sara Immobilier', 'phone' => '0655667788', 'total_credit' => 0],
            ['name' => 'Café LUX', 'phone' => '0699001122', 'total_credit' => 0],
        ];
        foreach ($clients as $c) {
            Client::create(array_merge($c, ['tenant_id' => $tenantId]));
        }
        $allClients = Client::where('tenant_id', $tenantId)->get();

        // 2. Create Stock & Services
        $panel = StockPanel::create([
            'tenant_id' => $tenantId,
            'type' => 'MDF Oak 18mm',
            'color_code' => 'OAK-01',
            'finish_type' => 'Matt',
            'size_x' => 2800,
            'size_y' => 2100,
            'thickness' => 18,
            'quantity' => 100,
            'cost_price' => 450,
            'base_price_sell' => 650,
        ]);

        $service = Service::create([
            'tenant_id' => $tenantId,
            'name' => 'Decoupe & Chant',
            'calculation_type' => 'per_sheet',
            'unit_price' => 150,
        ]);

        // 3. Create Orders (Mbi3at)
        // Order 1: Full Cash
        $o1 = Order::create([
            'tenant_id' => $tenantId,
            'client_id' => $allClients[0]->id,
            'user_id' => 1,
            'status' => 'delivered',
            'total_sell_price' => 3500,
            'total_cost_price' => 2200,
            'amount_paid' => 3500,
            'created_at' => Carbon::create($year, $month, 5, 10, 0, 0),
        ]);
        OrderLine::create([
            'tenant_id' => $tenantId, 'order_id' => $o1->id, 'item_id' => $panel->id, 'item_type' => StockPanel::class,
            'quantity' => 4, 'unit_sell_price' => 650, 'unit_buy_price' => 450, 'total_line_sell' => 2600, 'total_line_cost' => 1800
        ]);
        OrderLine::create([
            'tenant_id' => $tenantId, 'order_id' => $o1->id, 'item_id' => $service->id, 'item_type' => Service::class,
            'quantity' => 6, 'unit_sell_price' => 150, 'unit_buy_price' => 66, 'total_line_sell' => 900, 'total_line_cost' => 400
        ]);

        // Order 2: Credit
        $o2 = Order::create([
            'tenant_id' => $tenantId,
            'client_id' => $allClients[1]->id,
            'user_id' => 1,
            'status' => 'delivered',
            'total_sell_price' => 8000,
            'total_cost_price' => 5000,
            'amount_paid' => 3000,
            'created_at' => Carbon::create($year, $month, 10, 14, 30, 0),
        ]);
        $allClients[1]->increment('total_credit', 5000);

        // 4. Create Expenses (Charges)
        Expense::create([
            'tenant_id' => $tenantId,
            'title' => 'Loyer Atelier',
            'category' => '🏠 Loyer (K-ra)',
            'amount' => 4500,
            'expense_date' => Carbon::create($year, $month, 1),
        ]);
        Expense::create([
            'tenant_id' => $tenantId,
            'title' => 'Facture Electricité',
            'category' => '⚡ Électricité & Eau',
            'amount' => 850,
            'expense_date' => Carbon::create($year, $month, 15),
        ]);

        // 5. Employees & Salaries
        $emp = Employee::create([
            'tenant_id' => $tenantId,
            'name' => 'Hassan (M3llem)',
            'daily_salary' => 250,
            'is_active' => true,
        ]);
        
        for ($i = 1; $i <= 20; $i++) {
            EmployeeAttendance::create([
                'tenant_id' => $tenantId,
                'employee_id' => $emp->id,
                'date' => Carbon::create($year, $month, $i),
                'status' => 'present',
                'wage_earned' => 250,
                'is_paid' => false
            ]);
        }

        // 6. Purchases & Supplier Payments
        $supplier = \App\Models\Supplier::create([
            'tenant_id' => $tenantId,
            'name' => 'Bois du Nord',
            'phone' => '0522334455',
            'total_debt' => 0
        ]);

        $pur1 = \App\Models\Purchase::create([
            'tenant_id' => $tenantId,
            'supplier_id' => $supplier->id,
            'reference_invoice' => 'PUR-2026-001',
            'total_amount' => 12000,
            'amount_paid' => 4000,
            'created_at' => Carbon::create($year, $month, 3),
        ]);
        $supplier->increment('total_debt', 8000);
    }
}
