# Sprint 2: Concurrency, Idempotency & Business Logic - Review

## 1. Services (Refactoring & Concurrency)

### app/Services/StockService.php
- **Nady**: Created to handle all stock operations with `lockForUpdate()`.
```php
public function deductPanel($id, $qty) {
    $panel = StockPanel::whereId($id)->lockForUpdate()->firstOrFail();
    // ... decrement quantity ...
}
```

### app/Services/CheckoutService.php
- **Nady**: Centralized checkout logic. Handles `panel`, `canto`, `consumable`, and `service` types.
- **Fix**: Injected `StockService` to handle pessimistic locking inside a `DB::transaction()`.

## 2. Controllers (Security & Idempotency)

### app/Http/Controllers/OrderController.php
- **Refactor**: `store()` method now uses `CheckoutService`.
- **Fix (Step 2)**: `storeReturn()` now scopes `OrderLine` to the current order:
```php
$orderLine = $order->lines()->where('id', $lineData['order_line_id'])->firstOrFail();
```
- **Fix (Step 4)**: Added `Idempotency-Key` header check to prevent duplicate refunds.

## 3. Database Changes

### database/migrations/2026_05_11_021324_add_idempotency_key_to_order_returns_table.php
- Added `idempotency_key` column to `order_returns` table.
- Added unique index to prevent duplicate entries at the database level.

## 4. Consumable Support
- Added full support for `consumable` item types in `CheckoutService`, ensuring they are also locked during deduction to prevent stock corruption.
