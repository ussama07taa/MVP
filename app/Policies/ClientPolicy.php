<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Client;

class ClientPolicy
{
    public function view(User $user, Client $client)
    {
        return $user->tenant_id === $client->tenant_id && $user->hasPermissionTo('manage_orders');
    }

    public function update(User $user, Client $client)
    {
        return $user->tenant_id === $client->tenant_id && $user->hasPermissionTo('manage_orders');
    }

    public function delete(User $user, Client $client)
    {
        return $user->tenant_id === $client->tenant_id && $user->hasRole('admin');
    }
}
