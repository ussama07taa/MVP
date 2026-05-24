<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{OrderController, DashboardController, StockController, ExpenseController, PurchaseController, FinancialReportController, SettingsController, ClientController, InvoiceController, EmployeeController, WorkshopQueueController, WorkshopStatsController, BackupController};
use App\Models\{User, Client, Order, StockPanel, StockCanto, Service, Payment, Tenant, Consumable, Employee, Supplier, Purchase};
use Illuminate\Support\Facades\{Auth, DB, Cache};

use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\UserController;

Route::middleware(['auth', 'identify.tenant', 'throttle:100,1'])->group(function () {
    // ... existing routes ...
    Route::post('/export/{type}', [ExportController::class, 'export']);
    Route::get('/user', function (Request $request) { return $request->user(); });

    // Workshop Queue — accessible to ALL authenticated users (admin + cashier + worker)
    Route::get('/workshop/queue',                            [WorkshopQueueController::class, 'mobileIndex']);
    Route::post('/workshop/queue/{id}/hide',                 [WorkshopQueueController::class, 'hideFromWorkshop']);
    Route::post('/workshop/services/{serviceId}/toggle',     [WorkshopQueueController::class, 'toggleService']);
    Route::post('/orders/checkout', [OrderController::class, 'store']);
    Route::get('/clients', [ClientController::class, 'index']);
    Route::post('/clients', [ClientController::class, 'store']);
    Route::get('/clients/{id}/history', [ClientController::class, 'history']);
    Route::post('/orders/{id}/pay', [OrderController::class, 'pay']);
    Route::post('/clients/{id}/pay', [ClientController::class, 'pay']);
    Route::delete('/clients/{id}', [ClientController::class, 'destroy']);
    Route::put('/clients/{id}', [ClientController::class, 'update']);
    
    Route::get('/panels', [StockController::class, 'posPanels']);
    Route::get('/services', function() { return Service::select('id', 'name', 'unit_price', 'calculation_type')->get(); });
    Route::get('/cantos', [StockController::class, 'posCantos']);
    Route::get('/consumables', function() { return Consumable::select('id', 'name', 'unit', 'quantity_in_stock', 'average_cost_price', 'base_price_sell')->where('quantity_in_stock', '>', 0)->get(); });
    Route::get('/products/{id}/batches', [StockController::class, 'getProductBatches']);
    Route::post('/stock/adjust', [StockController::class, 'adjustStock']);
    Route::middleware('admin')->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'index']);

        // Workshop Queue — Admin management
        Route::get('/admin/workshop-queue',                    [WorkshopQueueController::class, 'index']);
        Route::post('/admin/workshop-queue',                   [WorkshopQueueController::class, 'store']);
        Route::post('/admin/workshop-queue/{id}/deliver',      [WorkshopQueueController::class, 'deliver']);
        Route::post('/admin/workshop-queue/{id}/undeliver',    [WorkshopQueueController::class, 'undeliver']);
        Route::delete('/admin/workshop-queue/{id}',            [WorkshopQueueController::class, 'destroy']);
        Route::get('/admin/statistics/financial', [FinancialReportController::class, 'index']);
        Route::get('/admin/statistics/workshop', [WorkshopStatsController::class, 'index']);

        // Backup management
        Route::get('/admin/backups', [BackupController::class, 'index']);
        Route::post('/admin/backups/run-db', [BackupController::class, 'runDbOnly']);
        Route::post('/admin/backups/run-full', [BackupController::class, 'runFull']);
        Route::get('/admin/backups/download', [BackupController::class, 'download']);
        Route::delete('/admin/backups', [BackupController::class, 'destroy']);
        Route::post('/admin/backups/clean', [BackupController::class, 'clean']);
        Route::get('/admin/stat', [FinancialReportController::class, 'index']); // Alias for the frontend
        Route::get('/admin/activity-logs', [ActivityLogController::class, 'index']);
        Route::apiResource('/admin/users', UserController::class);
        Route::get('/admin/clients', function() { return Client::withoutGlobalScopes()->select('id', 'name', 'phone', 'total_credit')->get(); });
        Route::get('/admin/expenses', [ExpenseController::class, 'index']);
        Route::post('/admin/expenses', [ExpenseController::class, 'store']);
        Route::put('/admin/expenses/{expense}', [ExpenseController::class, 'update']);
        Route::delete('/admin/expenses/{expense}', [ExpenseController::class, 'destroy']);
        
        Route::get('/admin/settings', [SettingsController::class, 'index']);
        Route::post('/admin/settings', [SettingsController::class, 'update']);
        
        Route::get('/admin/cantos', [StockController::class, 'cantos']);
        Route::put('/admin/cantos/{id}', [StockController::class, 'updateCanto']);
        Route::delete('/admin/cantos/{id}', function($id) {
            StockCanto::findOrFail($id)->delete();
            return response()->json(['success' => true]);
        });

        Route::get('/admin/panels', [StockController::class, 'panels']);
        Route::get('/admin/panels/{id}', [StockController::class, 'showPanel']);
        Route::put('/admin/panels/{id}', [StockController::class, 'updatePanel']);
        Route::delete('/admin/panels/{id}', function($id) {
            StockPanel::findOrFail($id)->delete();
            return response()->json(['success' => true]);
        });
        Route::post('/admin/stock/import-initial/{type?}', [StockController::class, 'importInitialStock']);
        Route::get('/admin/stock/import-template', [ExportController::class, 'downloadTemplate']);
        // Alias matching the admin UI which posts to /api/admin/inventory/adjust
        Route::post('/admin/inventory/adjust', [StockController::class, 'adjustStock']);

        Route::get('/admin/services', function() { return Service::latest()->get(); });
        Route::post('/admin/services', function(Request $request) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'calculation_type' => 'required|string',
                'unit_price' => 'required|numeric|min:0'
            ]);
            $tenantId = auth()->user()->tenant_id;
            return Service::create(array_merge($validated, ['tenant_id' => $tenantId]));
        });
        Route::put('/admin/services/{id}', function(Request $request, $id) {
            $service = Service::findOrFail($id);
            $service->update($request->only(['name', 'calculation_type', 'unit_price']));
            return $service;
        });
        Route::delete('/admin/services/{id}', function($id) {
            Service::findOrFail($id)->delete();
            return response()->json(['success' => true]);
        });
        Route::get('/admin/orders', [OrderController::class, 'index']);
        Route::post('/admin/orders/{id}/return', [OrderController::class, 'storeReturn']);
        Route::get('/admin/suppliers', [PurchaseController::class, 'suppliers']);
        Route::post('/admin/suppliers', function (Request $request) {
            $validated = $request->validate(['name' => 'required|string|max:255', 'phone' => 'nullable|string|max:50']);
            $tenantId = auth()->user()->tenant_id;
            return Supplier::create(['tenant_id' => $tenantId, 'name' => $validated['name'], 'phone' => $validated['phone'] ?? null, 'total_debt' => 0]);
        });
        Route::get('/admin/suppliers/{id}/history', [PurchaseController::class, 'supplierHistory']);
        Route::post('/admin/suppliers/{id}/pay', [PurchaseController::class, 'paySupplier']);
        Route::post('/admin/purchases', [PurchaseController::class, 'store']);
        Route::get('/admin/purchases', [PurchaseController::class, 'index']);
        Route::get('/admin/purchases/history', [PurchaseController::class, 'index']);
        Route::post('/admin/purchases/{id}/return', [PurchaseController::class, 'processReturn']);
        Route::get('/admin/employees', function(Request $request) {
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

                // Use sum of wage_earned for unpaid attendances (more accurate after our fix)
                $emp->unpaid_wages  = (float)$emp->attendances()->where('is_paid', false)->sum('wage_earned');

                // Net = max(0, unpaid wages - advances)
                $emp->net_to_pay    = round(max(0, $emp->unpaid_wages - (float)$emp->total_advances), 2);

                // Use unpaid_wages as the displayed "Salaire Gagné" (reflects real recorded wages)
                $emp->earned_salary = $emp->unpaid_wages > 0 ? $emp->unpaid_wages : $emp->earned_salary;
            }
            return $emps;
        });

        Route::post('/admin/employees', function(Request $request) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'role' => 'nullable|string|max:255',
                'phone' => 'nullable|string|max:50',
                'daily_salary' => 'required|numeric|min:0',
            ]);
            $tenantId = auth()->user()->tenant_id;
            return Employee::create(array_merge($validated, ['tenant_id' => $tenantId]));
        });
        Route::put('/admin/employees/{id}', [EmployeeController::class, 'update']);
        Route::delete('/admin/employees/{id}', [EmployeeController::class, 'destroy']);
        
        Route::post('/admin/employees/{id}/advance', function(Request $request, $id) {
            $request->validate(['amount' => 'required|numeric|min:0.1']);
            $tenantId = auth()->user()->tenant_id;
            
            DB::transaction(function() use ($request, $id, $tenantId) {
                $employee = Employee::where('tenant_id', $tenantId)->findOrFail($id);
                
                \App\Models\EmployeeAdvance::create([
                    'tenant_id' => $tenantId,
                    'employee_id' => $employee->id,
                    'date' => now()->toDateString(),
                    'amount' => $request->amount,
                    'is_deducted' => false
                ]);
                
                $employee->increment('total_advances', $request->amount);
            });

            return response()->json(['message' => 'Avance enregistrée avec succès']);
        });
        
        Route::post('/admin/employees/{id}/pay', function(Request $request, $id) {
            $tenantId = auth()->user()->tenant_id;
            
            DB::transaction(function() use ($id, $tenantId) {
                $employee = Employee::where('tenant_id', $tenantId)->findOrFail($id);
                
                \App\Models\EmployeeAttendance::where('tenant_id', $tenantId)
                    ->where('employee_id', $employee->id)
                    ->where('is_paid', false)
                    ->update(['is_paid' => true]);
                    
                $employee->update(['total_advances' => 0]);
                
                \App\Models\EmployeeAdvance::where('tenant_id', $tenantId)
                    ->where('employee_id', $employee->id)
                    ->where('is_deducted', false)
                    ->update(['is_deducted' => true]);
            });

            return response()->json(['message' => 'Employé payé avec succès']);
        });
        
        // Attendances Routes
        Route::get('/admin/attendances/{date}', function($date) {
            $tenantId = auth()->user()->tenant_id;
            return \App\Models\EmployeeAttendance::where('tenant_id', $tenantId)->whereDate('date', $date)->get();
        });
        
        Route::post('/admin/attendances', function(Request $request) {
            $tenantId = auth()->user()->tenant_id;
            $date = $request->input('date');
            $attendances = $request->input('attendances');
            
            DB::transaction(function() use ($tenantId, $date, $attendances) {
                foreach ($attendances as $att) {
                    // Fetch employee to get daily_salary
                    $employee = Employee::where('tenant_id', $tenantId)
                        ->find($att['employee_id']);

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

                    \App\Models\EmployeeAttendance::updateOrCreate(
                        ['tenant_id' => $tenantId, 'employee_id' => $att['employee_id'], 'date' => $date],
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
        });

        // Delete a single attendance record (for a given employee + date)
        Route::delete('/admin/attendances/{employee_id}/{date}', function($employee_id, $date) {
            $tenantId = auth()->user()->tenant_id;
            $deleted = \App\Models\EmployeeAttendance::where('tenant_id', $tenantId)
                ->where('employee_id', $employee_id)
                ->whereDate('date', $date)
                ->delete();
            return response()->json(['message' => $deleted ? 'Pointage supprimé' : 'Enregistrement non trouvé']);
        });
        
        // Payroll Routes
        Route::get('/admin/payroll/weekly', [\App\Http\Controllers\EmployeePayrollController::class, 'index']);
        Route::post('/admin/payroll/close', [\App\Http\Controllers\EmployeePayrollController::class, 'close']);
        Route::get('/admin/employees/{id}/history', [\App\Http\Controllers\EmployeePayrollController::class, 'employeeHistory']);

        // Invoice & Quote Routes
        Route::get('/admin/invoices', [InvoiceController::class, 'index']);
        Route::get('/admin/invoices/next-number', [InvoiceController::class, 'nextNumber']);
        Route::get('/admin/invoices/{id}', [InvoiceController::class, 'show']);
        Route::post('/admin/invoices', [InvoiceController::class, 'store']);
        Route::put('/admin/invoices/{id}', [InvoiceController::class, 'update']);
        Route::patch('/admin/invoices/{id}/status', [InvoiceController::class, 'updateStatus']);
        Route::post('/admin/invoices/{id}/convert', [InvoiceController::class, 'convertToInvoice']);
        Route::post('/admin/invoices/{id}/duplicate', [InvoiceController::class, 'duplicate']);
        Route::get('/admin/invoices-summary', [InvoiceController::class, 'summary']);
        Route::delete('/admin/invoices/{id}', [InvoiceController::class, 'destroy']);
    });
});
