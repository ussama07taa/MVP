<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Tenant for MVP
        $tenant = Tenant::firstOrCreate(
            ['id' => 1],
            ['name' => 'Taaouati Design', 'domain' => 'taaouati.com']
        );

        // 2. Default Patron / Admin
        User::firstOrCreate(
            ['email' => 'admin@taaouati.com'],
            [
                'name' => 'Oussama (Patron)',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'tenant_id' => $tenant->id
            ]
        );
    }
}
