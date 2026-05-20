<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Purchase;

class PurchasePolicy
{
    public function view(User $user, Purchase $purchase)
    {
        return $user->tenant_id === $purchase->tenant_id && $user->hasPermissionTo('manage_inventory');
    }

    public function create(User $user)
    {
        return $user->hasPermissionTo('manage_inventory');
    }

    public function update(User $user, Purchase $purchase)
    {
        return $user->tenant_id === $purchase->tenant_id && $user->hasPermissionTo('manage_inventory');
    }

    public function delete(User $user, Purchase $purchase)
    {
        return $user->tenant_id === $purchase->tenant_id && $user->hasPermissionTo('manage_inventory');
    }
}
