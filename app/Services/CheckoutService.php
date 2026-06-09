<?php

namespace App\Services;

use App\Models\{Order, Client, Service, WorkshopQueue, WorkshopQueueService};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CheckoutService
{
    protected $stock;
    protected $ledger;
    protected $pricing;

    public function __construct(StockService $stock, ClientLedgerService $ledger, PricingService $pricing)
    {
        $this->stock = $stock;
        $this->ledger = $ledger;
        $this->pricing = $pricing;
    }

    public function execute(array $data)
    {
        return DB::transaction(function() use ($data) {
            $tenantId = auth()->user()->tenant_id;
            $userId = auth()->id();

            // 1. PHASE 1: STOCK VALIDATION & LOCKING
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

            $totalSell = round($totalSell, 2);
            $totalCost = round($totalCost, 2);
            $amountPaid = round((float) $data['amount_paid'], 2);

            if ($amountPaid > $totalSell + 0.01) {
                throw new \Exception("Le montant payé ({$amountPaid} DH) dépasse le total ({$totalSell} DH).");
            }

            // 2. PHASE 2: PERSISTENCE (Order and Lines)
            $order = Order::create([
                'tenant_id' => $tenantId,
                'client_id' => $data['client_id'],
                'user_id' => $userId,
                'amount_paid' => $amountPaid,
                'total_sell_price' => $totalSell,
                'total_cost_price' => $totalCost,
                'status' => 'Pending_Workshop'
            ]);

            foreach ($processedItems as $pItem) {
                $this->createLine($order, $pItem);
            }

            // 3. PHASE 3: FINANCIALS
            $this->ledger->adjustCreditForOrder($data['client_id'], $totalSell);

            if ($amountPaid > 0) {
                $this->ledger->recordPayment($data['client_id'], $amountPaid, 'avance', $order->id);
            }

            // 4. WORKSHOP QUEUE
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

    /**
     * Append items to an existing order.
     */
    public function appendItems(Order $order, array $items)
    {
        return DB::transaction(function() use ($order, $items) {
            $tenantId = $order->tenant_id;
            
            $processedItems = [];
            $totalSell = 0;
            $totalCost = 0;

            foreach ($items as $item) {
                $processedArray = $this->lockAndPrepareItems($item);
                foreach ($processedArray as $processed) {
                    $processedItems[] = $processed;
                    $totalSell += $processed['line_sell'];
                    $totalCost += $processed['line_cost'];
                }
            }

            // 1. Create Lines
            foreach ($processedItems as $pItem) {
                $this->createLine($order, $pItem);
            }

            // 2. Update Order Totals
            $order->increment('total_sell_price', $totalSell);
            $order->increment('total_cost_price', $totalCost);

            // 3. Financials
            $this->ledger->adjustCreditForOrder($order->client_id, $totalSell);

            // 4. Update Workshop Queue
            $this->appendToWorkshop($order, $processedItems);

            // 5. Cleanup
            Cache::forget("tenant.{$tenantId}.panels");
            Cache::forget("tenant.{$tenantId}.cantos");

            return $order;
        });
    }

    protected function appendToWorkshop(Order $order, array $processedItems): void
    {
        $serviceLines = collect($processedItems)->filter(function($item) {
            return $item['type'] === Service::class || 
                   (is_string($item['type']) && str_contains($item['type'], 'Service'));
        });

        if ($serviceLines->isEmpty()) return;

        // Find existing non-delivered queue entry for this order
        $queue = WorkshopQueue::where('order_id', $order->id)
            ->where('status', '!=', 'delivered')
            ->first();

        if (!$queue) {
            // Case where order didn't have workshop yet, or it was delivered
            // We pass an empty data array since we don't have the original request context here
            $this->createWorkshopEntry($order, $processedItems, $order->tenant_id, []);
            return;
        }

        foreach ($serviceLines as $line) {
            WorkshopQueueService::create([
                'queue_id'       => $queue->id,
                'label'          => $line['label'] ?? 'Service',
                'material_type'  => $line['width_mm'] ? "{$line['width_mm']}mm" : null,
                'material_color' => $line['thickness_mm'] ? "{$line['thickness_mm']}mm" : null,
                'quantity'       => $line['quantity'],
                'unit'           => 'u',
                'is_done'        => false,
            ]);
        }
        
        // Reset status to 'waiting' if it was already processed, so it appears again in the board
        if ($queue->status === 'done' || $queue->status === 'in_progress') {
            $queue->update([
                'status' => 'waiting', 
                'done_at' => null,
                'notes' => trim($queue->notes . " | Artiklat jdad zado!")
            ]);
        }
    }

    protected function lockAndPrepareItems(array $item)
    {
        $lines = [];
        $serverUnitPrice = $this->pricing->resolveCheckoutUnitSell($item);
        $line_sell = round($item['quantity'] * $serverUnitPrice, 2);
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
                $has_splitting = !empty($item['with_canto_service']);

                    if ($has_splitting) {
                        // Line 1: Canto Material (Fourniture)
                        $base_price = (float) $canto->base_price_sell_per_m;
                        $collage_price = $this->pricing->resolveCantoCollagePrice();
                        $lines[] = [
                            'type' => $item_type,
                            'id' => $item_id,
                            'label' => 'Fourniture: ' . ($item['base_name'] ?? $item['name'] ?? 'Bandchant'),
                            'quantity' => $item['quantity'],
                            'unit_price' => $base_price,
                            'unit_buy' => $unit_buy,
                            'line_sell' => round($item['quantity'] * $base_price, 2),
                            'line_cost' => $line_cost,
                            'width_mm' => $item['width_mm'] ?? null,
                            'thickness_mm' => $item['thickness_mm'] ?? null,
                        ];

                        // Line 2: Collage de Chant (Façonnage)
                        $service = \App\Models\Service::where('name', 'like', '%collage%')
                            ->orWhere('name', 'like', '%chant%')
                            ->orWhere('name', 'like', '%coupe%')
                            ->first();
                        $service_id = $service ? $service->id : null;
                        $lines[] = [
                            'type' => \App\Models\Service::class,
                            'id' => $service_id,
                            'label' => 'Collage Chant: ' . ($item['base_name'] ?? $item['name'] ?? 'Bandchant'),
                            'quantity' => $item['quantity'],
                            'unit_price' => $collage_price,
                            'unit_buy' => 0,
                            'line_sell' => round($item['quantity'] * $collage_price, 2),
                            'line_cost' => 0,
                            'width_mm' => $item['width_mm'] ?? null,
                            'thickness_mm' => $item['thickness_mm'] ?? null,
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
            'unit_price' => $serverUnitPrice,
            'unit_buy' => $unit_buy,
            'line_sell' => $line_sell,
            'line_cost' => $line_cost,
            'width_mm' => $item['width_mm'] ?? null,
            'thickness_mm' => $item['thickness_mm'] ?? null,
        ];

        return $lines;
    }

    protected function createLine(Order $order, array $pItem)
    {
        return $order->lines()->create([
            'tenant_id' => $order->tenant_id,
            'item_type' => $pItem['type'],
            'item_id' => $pItem['id'],
            'label' => $pItem['label'],
            'quantity' => $pItem['quantity'],
            'unit_sell_price' => $pItem['unit_price'],
            'unit_buy_price' => $pItem['unit_buy'],
            'total_line_sell' => $pItem['line_sell'],
            'total_line_cost' => $pItem['line_cost'],
            'width_mm' => $pItem['width_mm'] ?? null,
            'thickness_mm' => $pItem['thickness_mm'] ?? null,
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

        $tefsilPath = null;
        if (isset($data['tefsil_file']) && $data['tefsil_file'] instanceof \Illuminate\Http\UploadedFile) {
            $tefsilPath = $data['tefsil_file']->store('workshop/tefsils', 'public');
        }

        $queue = WorkshopQueue::create([
            'tenant_id'    => $tenantId,
            'order_id'     => $order->id,
            'queue_number' => WorkshopQueue::generateNumber($tenantId),
            'client_name'  => $client->name ?? 'Client',
            'client_phone' => $client->phone ?? null,
            'notes'        => trim(($data['workshop_notes'] ?? '') . ' | Facture #' . $order->id),
            'status'       => 'waiting',
            'tefsil_path'  => $tefsilPath,
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
