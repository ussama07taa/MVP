<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/login', [AuthController::class, 'create'])->name('login');
Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/pos', [PosController::class, 'index'])->name('pos');
    Route::post('/orders/checkout', [OrderController::class, 'store'])->name('orders.checkout');

    Route::middleware(['admin'])->group(function () {
        Route::get('/admin/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Catch-all for other admin pages — maps URL segment to a Vue component name.
        Route::get('/admin/{any?}', function ($any = null) {
            $component = 'DashboardApp'; // fallback

            if ($any) {
                $parts = explode('/', $any);
                $firstPart = $parts[0];

                // Handle detail pages (e.g. /admin/employees/123)
                if (count($parts) > 1 && is_numeric($parts[1])) {
                    if ($firstPart === 'employees') {
                        return Inertia::render('EmployeeProfile', ['id' => $parts[1]]);
                    }
                }

                $mappings = [
                    'stock-mdf'         => 'StockMdfPage',
                    'stock-canto'       => 'StockCantoPage',
                    'achats'            => 'ProcurementPage',
                    'achats-historique' => 'PurchaseHistoryPage',
                    'fournisseurs'      => 'FournisseursPage',
                    'clients'           => 'ClientsPage',
                    'employees'         => 'EmployeesPage',
                    'attendance'        => 'AttendancePage',
                    'payroll'           => 'WeeklyPayroll',
                    'services'          => 'ServicesPage',
                    'charges'           => 'ExpensesPage',
                    'statistiques'      => 'FinancialStatsPage',
                    'settings'          => 'SettingsPage',
                    'system-logs'       => 'SystemLogsPage',
                    'users'             => 'UsersPage',
                    'invoices'          => 'InvoicesPage',
                    'orders'            => 'OrdersHistoryPage',
                    'workshop-queue'    => 'WorkshopAdminBoard',
                    'workshop-stats'    => 'WorkshopStatsPage',
                    'backups'           => 'BackupPage',
                    'reports'           => 'ReportsPage',
                    'atelier'           => 'WorkshopMobileExecution',
                ];

                if (isset($mappings[$any])) {
                    $component = $mappings[$any];
                } elseif (isset($mappings[$firstPart])) {
                    $component = $mappings[$firstPart];
                } else {
                    $component = collect($parts)
                        ->filter(fn($p) => !is_numeric($p))
                        ->map(fn($part) => ucfirst(\Illuminate\Support\Str::camel($part)))
                        ->last() ?: 'DashboardApp';
                }
            }

            return Inertia::render($component);
        })->where('any', '.*');
    });
});
