<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{OrderController, DashboardController, StockController, ExpenseController, PurchaseController, FinancialReportController, SettingsController, ClientController, InvoiceController, EmployeeController, WorkshopQueueController, WorkshopStatsController, BackupController, ReportController, ServiceController, AttendanceController};
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
    Route::get('/orders/{id}/pdf', [OrderController::class, 'downloadPdf']);
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
        Route::delete('/admin/cantos/{id}', [StockController::class, 'destroyCanto']);

        Route::get('/admin/panels', [StockController::class, 'panels']);
        Route::get('/admin/panels/{id}', [StockController::class, 'showPanel']);
        Route::put('/admin/panels/{id}', [StockController::class, 'updatePanel']);
        Route::delete('/admin/panels/{id}', [StockController::class, 'destroyPanel']);
        Route::post('/admin/stock/import-initial/{type?}', [StockController::class, 'importInitialStock']);
        Route::get('/admin/stock/import-template', [ExportController::class, 'downloadTemplate']);
        // Alias matching the admin UI which posts to /api/admin/inventory/adjust
        Route::post('/admin/inventory/adjust', [StockController::class, 'adjustStock']);

        Route::get('/admin/services', [ServiceController::class, 'index']);
        Route::post('/admin/services', [ServiceController::class, 'store']);
        Route::put('/admin/services/{id}', [ServiceController::class, 'update']);
        Route::delete('/admin/services/{id}', [ServiceController::class, 'destroy']);
        Route::get('/admin/orders', [OrderController::class, 'index']);
        Route::post('/admin/orders/{id}/return', [OrderController::class, 'storeReturn']);
        Route::get('/admin/suppliers', [PurchaseController::class, 'suppliers']);
        Route::post('/admin/suppliers', [PurchaseController::class, 'storeSupplier']);
        Route::get('/admin/suppliers/{id}/history', [PurchaseController::class, 'supplierHistory']);
        Route::post('/admin/suppliers/{id}/pay', [PurchaseController::class, 'paySupplier']);
        Route::post('/admin/purchases', [PurchaseController::class, 'store']);
        Route::get('/admin/purchases', [PurchaseController::class, 'index']);
        Route::get('/admin/purchases/history', [PurchaseController::class, 'index']);
        Route::post('/admin/purchases/{id}/return', [PurchaseController::class, 'processReturn']);
        Route::get('/admin/employees', [EmployeeController::class, 'index']);
        Route::post('/admin/employees', [EmployeeController::class, 'store']);
        Route::put('/admin/employees/{id}', [EmployeeController::class, 'update']);
        Route::delete('/admin/employees/{id}', [EmployeeController::class, 'destroy']);
        Route::post('/admin/employees/{id}/advance', [EmployeeController::class, 'advance']);
        Route::post('/admin/employees/{id}/pay', [EmployeeController::class, 'pay']);
        
        // Attendances Routes
        Route::get('/admin/attendances/{date}', [AttendanceController::class, 'index']);
        Route::post('/admin/attendances', [AttendanceController::class, 'store']);
        Route::delete('/admin/attendances/{employee_id}/{date}', [AttendanceController::class, 'destroy']);
        
        // Payroll Routes
        Route::get('/admin/payroll/weekly', [\App\Http\Controllers\EmployeePayrollController::class, 'index']);
        Route::post('/admin/payroll/close', [\App\Http\Controllers\EmployeePayrollController::class, 'close']);
        Route::get('/admin/employees/{id}/history', [\App\Http\Controllers\EmployeePayrollController::class, 'employeeHistory']);

        // Report Routes
        Route::get('/admin/reports/generate', [ReportController::class, 'generate']);
        Route::get('/admin/reports/preview', [ReportController::class, 'preview']);

        // Invoice & Quote Routes
        Route::get('/admin/invoices', [InvoiceController::class, 'index']);
        Route::get('/admin/invoices/next-number', [InvoiceController::class, 'nextNumber']);
        Route::get('/admin/invoices/stock-items', [InvoiceController::class, 'stockItems']);
        Route::get('/admin/invoices/{id}', [InvoiceController::class, 'show']);
        Route::post('/admin/invoices', [InvoiceController::class, 'store']);
        Route::put('/admin/invoices/{id}', [InvoiceController::class, 'update']);
        Route::patch('/admin/invoices/{id}/status', [InvoiceController::class, 'updateStatus']);
        Route::post('/admin/invoices/{id}/convert', [InvoiceController::class, 'convertToInvoice']);
        Route::post('/admin/invoices/{id}/validate', [InvoiceController::class, 'validateInvoice']);
        Route::post('/admin/invoices/{id}/pay', [InvoiceController::class, 'payInvoice']);
        Route::post('/admin/invoices/{id}/duplicate', [InvoiceController::class, 'duplicate']);
        Route::get('/admin/invoices/{id}/pdf', [InvoiceController::class, 'downloadPdf']);
        Route::get('/admin/invoices-summary', [InvoiceController::class, 'summary']);
        Route::delete('/admin/invoices/{id}', [InvoiceController::class, 'destroy']);
    });
});
