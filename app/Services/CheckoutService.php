<?php

namespace App\Services;

use App\Models\{Order, Client, Service, WorkshopQueue, WorkshopQueueService};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CheckoutService
{
    protected $stock;
    protected $ledger;

    public function __construct(StockService $stock, ClientLedgerService $ledger)
    {
        $this->stock = $stock;
        $this->ledger = $ledger;
    }

    public function execute(array $data)
    {
        return DB::transaction(function() use ($data) {
            $tenantId = auth()->user()->tenant_id;
            $userId = auth()->id();

            // 1. PHASE 1: STOCK VALIDATION & LOCKING (Calculate Totals First)
            $processedItems = [];
            $totalSell = 0;
            $totalCost = 0;

            foreach ($data['items'] as $item) {
                $processedArray = $this->lockAndPrepareItems($item);
                foreach ($processedArray as $processed) {
                    $processedItems[] = $processed;
                    $totalSell += $processed['line_sell'];
                    $totalCost += $processed['line_cost'];
                }
            }

            // 2. PHASE 2: PERSISTENCE (Order and Lines)
            $order = Order::create([
                'tenant_id' => $tenantId,
                'client_id' => $data['client_id'],
                'user_id' => $userId,
                'amount_paid' => $data['amount_paid'],
                'total_sell_price' => $totalSell,
                'total_cost_price' => $totalCost,
                'status' => 'Pending_Workshop'
            ]);

            foreach ($processedItems as $pItem) {
                $this->createLine($order, $pItem);
            }

            // 3. PHASE 3: FINANCIALS (Using Ledger Service)
            // A. Add total debt to client
            $this->ledger->adjustCreditForOrder($data['client_id'], $totalSell);

            // B. If paid, record payment (which will deduct from credit)
            if ($data['amount_paid'] > 0) {
                $this->ledger->recordPayment(
                    $data['client_id'], 
                    $data['amount_paid'], 
                    'avance', 
                    $order->id
                );
            }

            // 4. WORKSHOP QUEUE (if requested and order has services)
            if (!empty($data['send_to_workshop'])) {
                $this->createWorkshopEntry($order, $processedItems, $tenantId, $data);
            }

            // 5. CLEANUP
            Cache::forget("tenant.{$tenantId}.panels");
            Cache::forget("tenant.{$tenantId}.cantos");

            event(new \App\Events\OrderCreated($order));

            return $order;
        });
    }

    protected function lockAndPrepareItems(array $item)
    {
        $lines = [];
        $line_sell = $item['quantity'] * $item['unit_price'];
        $line_cost = 0;
        $item_type = null;
        $item_id = null;
        $unit_buy = 0;

        switch ($item['type']) {
            case 'panel':
                $panel = $this->stock->deductPanel($item['id'], $item['quantity']);
                $line_cost = $item['quantity'] * $panel->cost_price;
                $item_type = \App\Models\StockPanel::class;
                $item_id = $panel->id;
                $unit_buy = $panel->cost_price;
                break;

            case 'canto':
                $canto = $this->stock->deductCanto($item['id'], $item['quantity']);
                $line_cost = $item['quantity'] * $canto->cost_price_per_m;
                $item_type = \App\Models\StockCanto::class;
                $item_id = $canto->id;
                $unit_buy = $canto->cost_price_per_m;

                // Dynamic Splitting for Material and Collage/Façonnage only
                $has_splitting = !empty($item['with_canto_service']) && !empty($item['custom_canto_service_price']);

                if ($has_splitting) {
                    // Line 1: Canto Material (Fourniture)
                    $base_price = $item['base_canto_price'] ?? $canto->base_price_sell_per_m;
                    $lines[] = [
                        'type' => $item_type,
                        'id' => $item_id,
                        'label' => 'Fourniture: ' . ($item['base_name'] ?? $item['name'] ?? 'Bandchant'),
                        'quantity' => $item['quantity'],
                        'unit_price' => $base_price,
                        'unit_buy' => $unit_buy,
                        'line_sell' => $item['quantity'] * $base_price,
                        'line_cost' => $line_cost
                    ];

                    // Line 2: Collage de Chant (Façonnage)
                    $service = Service::where('name', 'like', '%chant%')->orWhere('name', 'like', '%coupe%')->first();
                    $service_id = $service ? $service->id : null;
                    $lines[] = [
                        'type' => Service::class,
                        'id' => $service_id,
                        'label' => 'Collage Chant: ' . ($item['base_name'] ?? $item['name'] ?? 'Bandchant'),
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['custom_canto_service_price'],
                        'unit_buy' => 0,
                        'line_sell' => $item['quantity'] * $item['custom_canto_service_price'],
                        'line_cost' => 0
                    ];

                    return $lines; // Return immediately to skip the default append
                }
                break;

            case 'consumable':
                $consumable = $this->stock->deductConsumable($item['id'], $item['quantity']);
                $line_cost = $item['quantity'] * ($consumable->average_cost_price ?? 0);
                $item_type = \App\Models\Consumable::class;
                $item_id = $consumable->id;
                $unit_buy = $consumable->average_cost_price ?? 0;
                break;

            case 'service':
                $service = Service::findOrFail($item['id']);
                $item_type = Service::class;
                $item_id = $service->id;
                break;

            case 'custom_labor':
                $item_type = Service::class;
                break;
        }

        $lines[] = [
            'type' => $item_type,
            'id' => $item_id,
            'label' => $item['name'] ?? null,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'unit_buy' => $unit_buy,
            'line_sell' => $line_sell,
            'line_cost' => $line_cost
        ];

        return $lines;
    }

    protected function createLine(Order $order, array $pItem)
    {
        return $order->lines()->create([
            'item_type' => $pItem['type'],
            'item_id' => $pItem['id'],
            'label' => $pItem['label'],
            'quantity' => $pItem['quantity'],
            'unit_sell_price' => $pItem['unit_price'],
            'unit_buy_price' => $pItem['unit_buy'],
            'total_line_sell' => $pItem['line_sell'],
            'total_line_cost' => $pItem['line_cost']
        ]);
    }

    protected function createWorkshopEntry(Order $order, array $processedItems, int $tenantId, array $data): void
    {
        $serviceLines = collect($processedItems)->filter(function ($item) {
            return $item['type'] === Service::class;
        });

        if ($serviceLines->isEmpty()) {
            return;
        }

        $client = Client::find($order->client_id);

        $queue = WorkshopQueue::create([
            'tenant_id'    => $tenantId,
            'queue_number' => WorkshopQueue::generateNumber($tenantId),
            'client_name'  => $client->name ?? 'Client',
            'client_phone' => $client->phone ?? null,
            'notes'        => trim(($data['workshop_notes'] ?? '') . ' | Facture #' . $order->id),
            'status'       => 'waiting',
        ]);

        foreach ($serviceLines as $line) {
            WorkshopQueueService::create([
                'queue_id'       => $queue->id,
                'label'          => $line['label'] ?? 'Service',
                'material_type'  => null,
                'material_color' => null,
                'quantity'       => $line['quantity'],
                'unit'           => 'u',
                'is_done'        => false,
            ]);
        }
    }
}
