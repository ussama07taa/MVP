<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user)
    {
        return true; // Scoped via Global Scope usually
    }

    public function view(User $user, Order $order)
    {
        return $user->tenant_id === $order->tenant_id && $user->hasPermissionTo('manage_orders');
    }

    public function create(User $user)
    {
        return $user->hasPermissionTo('manage_orders');
    }

    public function update(User $user, Order $order)
    {
        return $user->tenant_id === $order->tenant_id && $user->hasPermissionTo('manage_orders');
    }

    public function delete(User $user, Order $order)
    {
        return $user->tenant_id === $order->tenant_id && $user->hasPermissionTo('manage_orders');
    }
}
