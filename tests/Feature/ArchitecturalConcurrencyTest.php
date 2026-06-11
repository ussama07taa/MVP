<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\Client;
use App\Models\Employee;
use App\Http\Controllers\DashboardController;
use App\Services\FinancialReportService;
use Illuminate\Http\Request;

class ArchitecturalConcurrencyTest extends TestCase
{
    // Use RefreshDatabase to rollback changes automatically
    use RefreshDatabase;

    /**
     * TEST 1: Dashboard Cache Verification
     * Asserts that generating dashboard statistics only touches the database once 
     * and relies perfectly on Cache for subsequent hits, saving thousands of queries.
     */
    public function test_it_eliminates_dashboard_n_plus_one_by_caching_global_stats()
    {
        // 1. Arrange
        // Create synthetic clients to sum against
        Client::withoutEvents(function () {
            Client::create(['name' => 'Test 1', 'tenant_id' => 1, 'total_credit' => 100]);
            Client::create(['name' => 'Test 2', 'tenant_id' => 1, 'total_credit' => 200]);
        });
        
        Cache::flush();
        DB::enableQueryLog();

        $controller = new DashboardController(app(FinancialReportService::class));
        $request = Request::create('/dashboard', 'GET', ['period' => 'month']);
        
        // 2. Act 1 - First call (Cache Miss)
        $controller->index($request);
        $queriesFirstCall = collect(DB::getQueryLog())
            ->filter(fn($q) => str_contains(strtolower($q['query']), 'sum') && str_contains(strtolower($q['query']), 'total_credit'))
            ->count();
        
        // 3. Act 2 - Second call (Cache Hit)
        DB::flushQueryLog();
        $controller->index($request); // Should pull directly from Cache
        $queriesSecondCall = collect(DB::getQueryLog())
            ->filter(fn($q) => str_contains(strtolower($q['query']), 'sum') && str_contains(strtolower($q['query']), 'total_credit'))
            ->count();

        // 4. Assert
        $this->assertEquals(1, $queriesFirstCall, 'Failed: Global aggregate DB sum did not execute on initial load.');
        $this->assertEquals(0, $queriesSecondCall, 'Failed: Cache bypass detected. The massive DB query executed AGAIN instead of pulling from Memory/Redis.');
    }

    /**
     * TEST 2: Attendance Query Count Verification (N+1 Elimination)
     * Asserts that pushing 50 employee attendance objects executes exactly 2 structural queries 
     * (One block-select, One bulk-upsert) instead of 100 individual looped queries.
     */
    public function test_it_verifies_attendance_upsert_executes_in_two_batch_queries()
    {
        // 1. Arrange
        $attendances = [];
        for ($i = 1; $i <= 50; $i++) {
            $employee = Employee::create(['name' => "Worker {$i}", 'tenant_id' => 1, 'daily_salary' => 150]);
            $attendances[] = [
                'employee_id' => $employee->id,
                'status' => 'present',
                'overtime_hours' => 2,
                'notes' => 'Test'
            ];
        }

        DB::flushQueryLog();

        // 2. Act
        // Mock the Controller directly to avoid API middleware interference
        $controller = new \App\Http\Controllers\AttendanceController();
        $request = Request::create('/api/attendances', 'POST', [
            'date' => now()->format('Y-m-d'),
            'attendances' => $attendances
        ]);
        
        $controller->store($request);
        $queryLog = DB::getQueryLog();
        
        // 3. Evaluate SQL Execution Profile
        $selectQueries = collect($queryLog)->filter(fn($q) => str_contains(strtolower($q['query']), 'select') && str_contains(strtolower($q['query']), 'for update'))->count();
        $insertQueries = collect($queryLog)->filter(fn($q) => str_contains(strtolower($q['query']), 'insert'))->count();

        // 4. Assert
        $this->assertEquals(1, $selectQueries, 'Failed: N+1 Trap Detected! Employees were not batched queried via `whereIn`.');
        $this->assertEquals(1, $insertQueries, 'Failed: N+1 Trap Detected! Attendances were fired via loop instead of a unified bulk `upsert`.');
    }

    /**
     * TEST 3: Ledger Concurrency & Locking Simulation
     * Verifies that the distributeGlobalPayment applies strict sequential row locking
     * during execution to prevent parallel multi-window state overwrites.
     */
    public function test_ledger_secures_write_operations_using_row_level_locks()
    {
        // To natively test lockForUpdate in PHPUnit without multithreading extensions:
        // We assert that the queries structurally command MySQL innodb exact row locks `FOR UPDATE`.
        
        // 1. Arrange
        $client = Client::create(['name' => 'Locked Client', 'tenant_id' => 1, 'total_credit' => 1000]);
        DB::flushQueryLog();

        // 2. Act
        $service = app(\App\Services\ClientLedgerService::class);
        $service->distributeGlobalPayment($client->id, 500, 'solde', 'Test Simulation Lock', 'cash');

        // 3. Extract generated queries 
        $queryLog = DB::getQueryLog();
        
        // 4. Assert isolation locks
        $clientLockQuery = collect($queryLog)->filter(fn($q) => 
            str_contains(strtolower($q['query']), 'select') && 
            str_contains(strtolower($q['query']), 'clients') && 
            str_contains(strtolower($q['query']), 'for update')
        )->isNotEmpty();

        $orderLockQuery = collect($queryLog)->filter(fn($q) => 
            str_contains(strtolower($q['query']), 'select') && 
            str_contains(strtolower($q['query']), 'orders') && 
            str_contains(strtolower($q['query']), 'for update')
        )->isNotEmpty();

        $this->assertTrue($clientLockQuery, 'Failed: Distribution ledger did not lock the Client table state ($client->lockForUpdate()).');
        $this->assertTrue($orderLockQuery, 'Failed: Distribution ledger did not lock the target Orders block. Race Condition vulnerability exists!');
    }
}
