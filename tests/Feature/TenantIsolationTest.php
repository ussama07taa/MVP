<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\{Tenant, User, Client, Order, OrderLine, StockPanel};

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A user must not see clients that belong to another tenant.
     */
    public function test_tenant_scope_hides_other_tenants_clients(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        Client::factory()->create(['tenant_id' => $tenantA->id, 'name' => 'Client A']);
        Client::factory()->create(['tenant_id' => $tenantB->id, 'name' => 'Client B']);

        $this->actingAs($userA);

        $clients = Client::all();
        $this->assertCount(1, $clients, 'User A should only see clients from tenant A');
        $this->assertEquals('Client A', $clients->first()->name);
    }

    /**
     * A user must not be able to create an order referencing another tenant's client.
     */
    public function test_user_cannot_create_order_for_other_tenants_client(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $clientB = Client::factory()->create(['tenant_id' => $tenantB->id]);
        $panelA = StockPanel::create([
            'tenant_id' => $tenantA->id,
            'type' => 'MDF',
            'size_x' => 2800,
            'size_y' => 1220,
            'thickness' => 18.0,
            'quantity' => 10,
            'cost_price' => 200,
            'base_price_sell' => 300
        ]);

        $response = $this->actingAs($userA)->postJson('/api/orders/checkout', [
            'client_id' => $clientB->id, // Foreign tenant's client
            'amount_paid' => 0,
            'items' => [[
                'id' => $panelA->id,
                'type' => 'panel',
                'quantity' => 1,
                'unit_price' => 300,
                'name' => 'MDF Test'
            ]]
        ]);

        // exists rule scoped by tenant_id must reject this.
        $response->assertStatus(422);
    }

    /**
     * A user must not be able to create an order with another tenant's stock panel.
     * Even though the panel exists globally, the TenantScope on StockPanel ensures
     * it cannot be loaded by the wrong tenant in the checkout service.
     */
    public function test_user_cannot_use_other_tenants_stock_panel(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
        $clientA = Client::factory()->create(['tenant_id' => $tenantA->id]);

        $panelB = StockPanel::create([
            'tenant_id' => $tenantB->id, // Belongs to OTHER tenant
            'type' => 'MDF',
            'size_x' => 2800,
            'size_y' => 1220,
            'thickness' => 18.0,
            'quantity' => 10,
            'cost_price' => 200,
            'base_price_sell' => 300
        ]);

        $response = $this->actingAs($userA)->postJson('/api/orders/checkout', [
            'client_id' => $clientA->id,
            'amount_paid' => 0,
            'items' => [[
                'id' => $panelB->id,
                'type' => 'panel',
                'quantity' => 1,
                'unit_price' => 300,
                'name' => 'MDF B'
            ]]
        ]);

        // CheckoutService's StockService::deductPanel must fail because StockPanel
        // is scoped by tenant — finding the panel inside tenant A returns nothing.
        $response->assertStatus(400);
    }
}
