<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Tenant;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'total_sell_price' => $this->faker->randomFloat(2, 100, 5000),
            'total_cost_price' => $this->faker->randomFloat(2, 50, 2500),
            'amount_paid' => $this->faker->randomFloat(2, 0, 1000),
            'status' => 'Pending_Workshop',
        ];
    }
}
