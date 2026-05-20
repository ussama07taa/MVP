<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Client;
use App\Models\StockPanel;
use Illuminate\Support\Facades\Event;
use App\Events\OrderCreated;

class OrderCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_order_and_event_is_dispatched()
    {
        Event::fake();
        
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $client = Client::factory()->create(['tenant_id' => $tenant->id]);
        
        $panel = StockPanel::create([
            'tenant_id' => $tenant->id,
            'type' => 'MDF',
            'size_x' => 2800,
            'size_y' => 1220,
            'thickness' => 18.0,
            'quantity' => 10,
            'cost_price' => 200,
            'base_price_sell' => 300
        ]);

        $response = $this->actingAs($user)->postJson('/api/orders/checkout', [
            'client_id' => $client->id,
            'amount_paid' => 500,
            'items' => [
                [
                    'id' => $panel->id,
                    'type' => 'panel',
                    'quantity' => 2,
                    'unit_price' => 300,
                    'name' => 'MDF Test'
                ]
            ]
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('orders', [
            'total_sell_price' => 600,
            'tenant_id' => $tenant->id,
            'client_id' => $client->id
        ]);
        
        Event::assertDispatched(OrderCreated::class);
    }
}
