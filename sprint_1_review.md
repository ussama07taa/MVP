# Sprint 1: Security Hardening & Multi-Tenant Isolation - Review

## 1. Controllers (Removed Tenant::first() Fallback)

### app/Http/Controllers/OrderController.php
```php
// Old
$userId = auth()->id() ?? User::first()->id;
$tenantId = auth()->check() ? auth()->user()->tenant_id : Tenant::first()->id;

// New
$userId = auth()->id();
$tenantId = auth()->user()->tenant_id;
```

### app/Http/Controllers/PurchaseController.php
```php
// Old (Lines 16 & 263)
$tenantId = auth()->check() ? auth()->user()->tenant_id : \App\Models\Tenant::first()->id;

// New
$tenantId = auth()->user()->tenant_id;
```

### app/Http/Controllers/ExpenseController.php
```php
// Old (Lines 11 & 56)
$tenantId = auth()->check() ? auth()->user()->tenant_id : \App\Models\Tenant::first()->id;

// New
$tenantId = auth()->user()->tenant_id;
```

## 2. Validation Requests (Scoped exists rules)

### app/Http/Requests/StoreOrderRequest.php
```php
'client_id' => [
    'required',
    \Illuminate\Validation\Rule::exists('clients', 'id')->where('tenant_id', auth()->user()->tenant_id)
],
```

### app/Http/Requests/StoreStockPanelRequest.php / StoreStockCantoRequest.php / StorePurchaseRequest.php
```php
'supplier_id' => [
    'required', // or nullable
    \Illuminate\Validation\Rule::exists('suppliers', 'id')->where('tenant_id', auth()->user()->tenant_id)
],
```

## 3. Spatie Permissions (Isolation)

### app/Providers/EventServiceProvider.php
```php
public function boot(): void
{
    if (auth()->check()) {
        app(\Spatie\Permission\PermissionRegistrar::class)->cacheKey = 'spatie.permission.cache.tenant.' . auth()->user()->tenant_id;
    }
}
```

### database/migrations/2026_05_11_020319_add_tenant_id_to_roles_table.php
- Added `tenant_id` to `roles`.
- Updated unique index to `['name', 'guard_name', 'tenant_id']`.

### database/seeders/RoleSeeder.php
- Created to seed roles (admin, cashier) with `tenant_id`.

## 4. Policies (Ownership Enforcement)

### app/Policies/OrderPolicy.php / PurchasePolicy.php / ClientPolicy.php
Example:
```php
public function view(User $user, Order $order)
{
    return $user->tenant_id === $order->tenant_id;
}
```

### app/Providers/AuthServiceProvider.php
- Registered the policies for `Order`, `Purchase`, and `Client`.
