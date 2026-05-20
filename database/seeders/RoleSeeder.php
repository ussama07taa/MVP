<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Tenant;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions (global, shared across all tenants)
        $permissions = [
            'manage_orders',
            'view_reports',
            'manage_inventory',
            'manage_employees',
            'manage_expenses',
            'manage_settings'
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create Roles scoped to EACH Tenant (multi-tenant aware)
        $tenants = Tenant::all();
        if ($tenants->isEmpty()) {
            $this->command->warn('RoleSeeder: No tenants found, skipping role seeding.');
            return;
        }

        foreach ($tenants as $tenant) {
            $tenantId = $tenant->id;

            $adminRole = Role::firstOrCreate(
                ['name' => 'admin', 'guard_name' => 'web', 'tenant_id' => $tenantId]
            );
            $adminRole->syncPermissions($permissions);

            $cashierRole = Role::firstOrCreate(
                ['name' => 'cashier', 'guard_name' => 'web', 'tenant_id' => $tenantId]
            );
            $cashierRole->syncPermissions(['manage_orders', 'manage_inventory']);
        }
    }
}
