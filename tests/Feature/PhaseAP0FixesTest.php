<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Purchase;
use App\Models\PurchaseLine;
use App\Models\StockPanel;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for Phase A P0 fixes (pre-client delivery).
 */
class PhaseAP0FixesTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        $tenant = Tenant::factory()->create();
        return User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'admin',
        ]);
    }

    public function test_invoice_payment_via_orders_route_is_rejected(): void
    {
        $admin = $this->adminUser();
        $client = Client::factory()->create(['tenant_id' => $admin->tenant_id]);

        $invoice = Invoice::create([
            'tenant_id' => $admin->tenant_id,
            'client_id' => $client->id,
            'user_id' => $admin->id,
            'invoice_number' => 'FAC-TEST-001',
            'type' => 'invoice',
            'status' => 'sent',
            'issue_date' => now()->toDateString(),
            'subtotal' => 1000,
            'total' => 1000,
            'amount_paid' => 0,
            'validated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->postJson("/api/admin/orders/{$invoice->id}/pay", [
            'amount' => 500,
            'source' => 'invoice',
        ]);

        $response->assertStatus(422);
        $this->assertEquals(0, (float) $invoice->fresh()->amount_paid);
    }

    public function test_invoice_payment_route_enforces_cap_and_validation(): void
    {
        $admin = $this->adminUser();
        $client = Client::factory()->create(['tenant_id' => $admin->tenant_id]);

        $invoice = Invoice::create([
            'tenant_id' => $admin->tenant_id,
            'client_id' => $client->id,
            'user_id' => $admin->id,
            'invoice_number' => 'FAC-TEST-002',
            'type' => 'invoice',
            'status' => 'sent',
            'issue_date' => now()->toDateString(),
            'subtotal' => 1000,
            'total' => 1000,
            'amount_paid' => 800,
            'validated_at' => now(),
        ]);

        $overpay = $this->actingAs($admin)->postJson("/api/admin/invoices/{$invoice->id}/pay", [
            'amount' => 500,
        ]);
        $overpay->assertStatus(422);

        $ok = $this->actingAs($admin)->postJson("/api/admin/invoices/{$invoice->id}/pay", [
            'amount' => 200,
        ]);
        $ok->assertStatus(200);

        $invoice->refresh();
        $this->assertEquals(1000, (float) $invoice->amount_paid);
        $this->assertEquals('paid', $invoice->status);
    }

    public function test_unvalidated_invoice_cannot_be_paid(): void
    {
        $admin = $this->adminUser();
        $client = Client::factory()->create(['tenant_id' => $admin->tenant_id]);

        $invoice = Invoice::create([
            'tenant_id' => $admin->tenant_id,
            'client_id' => $client->id,
            'user_id' => $admin->id,
            'invoice_number' => 'FAC-TEST-003',
            'type' => 'invoice',
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'subtotal' => 500,
            'total' => 500,
            'amount_paid' => 0,
            'validated_at' => null,
        ]);

        $response = $this->actingAs($admin)->postJson("/api/admin/invoices/{$invoice->id}/pay", [
            'amount' => 100,
        ]);

        $response->assertStatus(422);
    }

    public function test_order_return_caps_amount_paid_to_net_total(): void
    {
        $admin = $this->adminUser();
        $client = Client::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'total_credit' => 0,
        ]);

        $panel = StockPanel::create([
            'tenant_id' => $admin->tenant_id,
            'type' => 'MDF',
            'size_x' => 2800,
            'size_y' => 1220,
            'thickness' => 18,
            'quantity' => 10,
            'cost_price' => 100,
            'base_price_sell' => 300,
        ]);

        $order = Order::create([
            'tenant_id' => $admin->tenant_id,
            'client_id' => $client->id,
            'user_id' => $admin->id,
            'status' => 'completed',
            'total_sell_price' => 1000,
            'total_cost_price' => 300,
            'amount_paid' => 1000,
        ]);

        $line = OrderLine::create([
            'tenant_id' => $admin->tenant_id,
            'order_id' => $order->id,
            'item_type' => StockPanel::class,
            'item_id' => $panel->id,
            'label' => 'MDF Test',
            'quantity' => 2,
            'unit_sell_price' => 500,
            'total_line_sell' => 1000,
            'total_line_cost' => 300,
        ]);

        $response = $this->actingAs($admin)->postJson("/api/admin/orders/{$order->id}/return", [
            'reason' => 'Test retour',
            'return_lines' => [
                ['order_line_id' => $line->id, 'quantity' => 1],
            ],
        ]);

        $response->assertStatus(200);
        $order->refresh();
        $this->assertEquals(500, (float) $order->amount_paid);
    }

    public function test_purchase_return_on_cash_purchase_does_not_reduce_supplier_debt(): void
    {
        $admin = $this->adminUser();
        $supplier = Supplier::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Fournisseur Cash',
            'total_debt' => 0,
        ]);

        $purchase = Purchase::create([
            'tenant_id' => $admin->tenant_id,
            'supplier_id' => $supplier->id,
            'total_amount' => 1000,
            'amount_paid' => 1000,
        ]);

        $panel = StockPanel::create([
            'tenant_id' => $admin->tenant_id,
            'type' => 'MDF',
            'size_x' => 2800,
            'size_y' => 1220,
            'thickness' => 18,
            'quantity' => 5,
            'cost_price' => 200,
            'base_price_sell' => 300,
            'purchase_id' => $purchase->id,
            'supplier_id' => $supplier->id,
        ]);

        $line = PurchaseLine::create([
            'tenant_id' => $admin->tenant_id,
            'purchase_id' => $purchase->id,
            'category' => 'mdf',
            'product_name_snapshot' => 'MDF Blanc',
            'quantity' => 5,
            'quantity_remaining' => 5,
            'unit_cost' => 200,
            'total_line_cost' => 1000,
            'stock_item_id' => $panel->id,
            'stock_item_type' => 'StockPanel',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/admin/purchases/{$purchase->id}/return", [
            'purchase_line_id' => $line->id,
            'returned_quantity' => 2,
            'reason' => 'Test',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, (float) $supplier->fresh()->total_debt);
        $this->assertEquals(3, (float) $panel->fresh()->quantity);
    }

    public function test_purchase_return_on_credit_purchase_reduces_debt(): void
    {
        $admin = $this->adminUser();
        $supplier = Supplier::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'Fournisseur Crédit',
            'total_debt' => 600,
        ]);

        $purchase = Purchase::create([
            'tenant_id' => $admin->tenant_id,
            'supplier_id' => $supplier->id,
            'total_amount' => 1000,
            'amount_paid' => 400,
        ]);

        $panel = StockPanel::create([
            'tenant_id' => $admin->tenant_id,
            'type' => 'MDF',
            'size_x' => 2800,
            'size_y' => 1220,
            'thickness' => 18,
            'quantity' => 5,
            'cost_price' => 200,
            'base_price_sell' => 300,
            'purchase_id' => $purchase->id,
            'supplier_id' => $supplier->id,
        ]);

        $line = PurchaseLine::create([
            'tenant_id' => $admin->tenant_id,
            'purchase_id' => $purchase->id,
            'category' => 'mdf',
            'product_name_snapshot' => 'MDF Blanc',
            'quantity' => 5,
            'quantity_remaining' => 5,
            'unit_cost' => 200,
            'total_line_cost' => 1000,
            'stock_item_id' => $panel->id,
            'stock_item_type' => 'StockPanel',
        ]);

        $response = $this->actingAs($admin)->postJson("/api/admin/purchases/{$purchase->id}/return", [
            'purchase_line_id' => $line->id,
            'returned_quantity' => 1,
            'reason' => 'Test',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(400, (float) $supplier->fresh()->total_debt);
    }

    public function test_stock_adjustment_decrements_fifo_quantity_remaining(): void
    {
        $admin = $this->adminUser();
        $supplier = Supplier::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'FIFO Supplier',
            'total_debt' => 0,
        ]);

        $panel = StockPanel::create([
            'tenant_id' => $admin->tenant_id,
            'type' => 'MDF',
            'size_x' => 2800,
            'size_y' => 1220,
            'thickness' => 18,
            'quantity' => 8,
            'cost_price' => 200,
            'base_price_sell' => 300,
            'supplier_id' => $supplier->id,
        ]);

        $purchaseOld = Purchase::create([
            'tenant_id' => $admin->tenant_id,
            'supplier_id' => $supplier->id,
            'total_amount' => 600,
            'amount_paid' => 600,
            'created_at' => now()->subDays(2),
        ]);
        $batchOld = PurchaseLine::create([
            'tenant_id' => $admin->tenant_id,
            'purchase_id' => $purchaseOld->id,
            'category' => 'mdf',
            'product_name_snapshot' => 'Lot ancien',
            'quantity' => 3,
            'quantity_remaining' => 3,
            'unit_cost' => 200,
            'total_line_cost' => 600,
            'stock_item_id' => $panel->id,
            'stock_item_type' => 'StockPanel',
        ]);

        $purchaseNew = Purchase::create([
            'tenant_id' => $admin->tenant_id,
            'supplier_id' => $supplier->id,
            'total_amount' => 1000,
            'amount_paid' => 1000,
            'created_at' => now()->subDay(),
        ]);
        $batchNew = PurchaseLine::create([
            'tenant_id' => $admin->tenant_id,
            'purchase_id' => $purchaseNew->id,
            'category' => 'mdf',
            'product_name_snapshot' => 'Lot récent',
            'quantity' => 5,
            'quantity_remaining' => 5,
            'unit_cost' => 200,
            'total_line_cost' => 1000,
            'stock_item_id' => $panel->id,
            'stock_item_type' => 'StockPanel',
        ]);

        $response = $this->actingAs($admin)->postJson('/api/admin/inventory/adjust', [
            'item_id' => $panel->id,
            'item_type' => 'StockPanel',
            'quantity' => 4,
            'reason' => 'chute',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(0, (float) $batchOld->fresh()->quantity_remaining);
        $this->assertEquals(4, (float) $batchNew->fresh()->quantity_remaining);
    }

    public function test_sale_return_restores_fifo_batches(): void
    {
        $admin = $this->adminUser();
        $client = Client::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'total_credit' => 0,
        ]);
        $supplier = Supplier::create([
            'tenant_id' => $admin->tenant_id,
            'name' => 'FIFO Supplier',
            'total_debt' => 0,
        ]);

        $panel = StockPanel::create([
            'tenant_id' => $admin->tenant_id,
            'type' => 'MDF',
            'size_x' => 2800,
            'size_y' => 1220,
            'thickness' => 18,
            'quantity' => 5,
            'cost_price' => 200,
            'base_price_sell' => 300,
            'supplier_id' => $supplier->id,
        ]);

        $purchase = Purchase::create([
            'tenant_id' => $admin->tenant_id,
            'supplier_id' => $supplier->id,
            'total_amount' => 1000,
            'amount_paid' => 1000,
        ]);
        $batch = PurchaseLine::create([
            'tenant_id' => $admin->tenant_id,
            'purchase_id' => $purchase->id,
            'category' => 'mdf',
            'product_name_snapshot' => 'Lot unique',
            'quantity' => 5,
            'quantity_remaining' => 5,
            'unit_cost' => 200,
            'total_line_cost' => 1000,
            'stock_item_id' => $panel->id,
            'stock_item_type' => 'StockPanel',
        ]);

        app(StockService::class)->deductPanel($panel->id, 3);
        $this->assertEquals(2, (float) $batch->fresh()->quantity_remaining);

        $order = Order::create([
            'tenant_id' => $admin->tenant_id,
            'client_id' => $client->id,
            'user_id' => $admin->id,
            'status' => 'completed',
            'total_sell_price' => 900,
            'total_cost_price' => 600,
            'amount_paid' => 900,
        ]);

        $line = OrderLine::create([
            'tenant_id' => $admin->tenant_id,
            'order_id' => $order->id,
            'item_type' => StockPanel::class,
            'item_id' => $panel->id,
            'label' => 'MDF Test',
            'quantity' => 3,
            'unit_sell_price' => 300,
            'total_line_sell' => 900,
            'total_line_cost' => 600,
        ]);

        $response = $this->actingAs($admin)->postJson("/api/admin/orders/{$order->id}/return", [
            'reason' => 'FIFO restore',
            'return_lines' => [
                ['order_line_id' => $line->id, 'quantity' => 2],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertEquals(4, (float) $batch->fresh()->quantity_remaining);
    }
}
