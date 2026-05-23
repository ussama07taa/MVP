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
                $component = collect(explode('/', $any))
                    ->map(fn($part) => ucfirst(\Illuminate\Support\Str::camel($part)))
                    ->last();

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
                    'atelier'           => 'WorkshopMobileExecution',
                ];

                if (isset($mappings[$any])) {
                    $component = $mappings[$any];
                }
            }

            return Inertia::render($component);
        })->where('any', '.*');
    });
});
