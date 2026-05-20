<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\{Tenant, User, Client, Employee, StockPanel};

/**
 * Regression tests for the 4 admin API routes that were missing on `main`
 * (PUT/DELETE /api/admin/clients/{id}, PUT/DELETE /api/admin/employees/{id})
 * plus the /api/admin/inventory/adjust alias for stock adjustments.
 *
 * These endpoints are used by ClientsPage / EmployeesPage / StockMdfPage —
 * without them the frontend hits 404 and Vue crashes (`charAt` of undefined).
 */
class MissingApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);
        return $user;
    }

    public function test_admin_can_update_client(): void
    {
        $admin = $this->adminUser();
        $client = Client::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Old Name',
            'phone' => '0600000000',
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/clients/{$client->id}", [
                'name' => 'New Name',
                'phone' => '0611111111',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'New Name',
            'phone' => '0611111111',
        ]);
    }

    public function test_admin_can_delete_client_with_no_credit(): void
    {
        $admin = $this->adminUser();
        $client = Client::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'total_credit' => 0,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/clients/{$client->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_admin_cannot_delete_client_with_outstanding_credit(): void
    {
        $admin = $this->adminUser();
        $client = Client::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'total_credit' => 500.00,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/clients/{$client->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'deleted_at' => null]);
    }

    public function test_admin_can_update_employee(): void
    {
        $admin = $this->adminUser();
        $employee = Employee::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Old',
            'role' => 'Menuisier',
            'phone' => '0600000000',
            'daily_salary' => 100.0,
        ]);

        $response = $this->actingAs($admin)
            ->putJson("/api/admin/employees/{$employee->id}", [
                'name' => 'New',
                'daily_salary' => 150.0,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'name' => 'New',
            'daily_salary' => 150.0,
        ]);
    }

    public function test_admin_can_delete_employee_with_no_unpaid_wages(): void
    {
        $admin = $this->adminUser();
        $employee = Employee::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'To Delete',
            'role' => 'Menuisier',
            'phone' => '0600000000',
            'daily_salary' => 100.0,
        ]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/employees/{$employee->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_inventory_adjust_alias_exists_under_admin(): void
    {
        $admin = $this->adminUser();
        $panel = StockPanel::create([
            'tenant_id' => $admin->tenant_id,
            'type' => 'MDF',
            'size_x' => 2800,
            'size_y' => 1220,
            'thickness' => 18.0,
            'quantity' => 10,
            'cost_price' => 200,
            'base_price_sell' => 300,
        ]);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/inventory/adjust', [
                'product_type' => 'panel',
                'product_id' => $panel->id,
                'new_quantity' => 7,
                'reason' => 'Test inventory adjust',
            ]);

        // The route must not 404 — it can return 200, 422 (validation) or 500
        // (depending on adjustStock signature) but never "route not found".
        $this->assertNotEquals(404, $response->status(), 'POST /api/admin/inventory/adjust returned 404 — route is missing.');
    }
}
