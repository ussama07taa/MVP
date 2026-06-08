<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Setting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Initial setup for a single custom client (1 atelier).
 * Safe to re-run: uses firstOrCreate, does not overwrite existing passwords.
 *
 * Usage: php artisan db:seed --class=ClientHandoverSeeder
 */
class ClientHandoverSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RoleSeeder::class);

        $tenant = Tenant::firstOrCreate(
            ['id' => 1],
            ['name' => 'TAAOUATI', 'domain' => null]
        );

        Setting::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'company_name' => 'TAAOUATI',
                'company_address' => 'TANGER JAMA3 ALRB3IN',
                'company_phone' => '+212 666-035411 / +212 610-182585',
                'company_email' => 'contact@taaouati.com',
                'company_ice' => '122-2333',
                'company_rc' => null,
                'invoice_footer_text' => 'Merci pour votre confiance !',
            ]
        );

        $users = [
            [
                'email' => 'admin@taaouati.com',
                'name' => 'Administrateur',
                'role' => 'admin',
                'password' => 'Admin2026!',
            ],
            [
                'email' => 'caisse@taaouati.com',
                'name' => 'Caissier',
                'role' => 'cashier',
                'password' => 'Caisse2026!',
            ],
            [
                'email' => 'atelier@taaouati.com',
                'name' => 'Ouvrier Atelier',
                'role' => 'worker',
                'password' => 'Atelier2026!',
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => Hash::make($data['password']),
                    'role' => $data['role'],
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                ]
            );

            if ($user->wasRecentlyCreated && method_exists($user, 'assignRole')) {
                $user->assignRole($data['role']);
            }
        }

        $services = [
            ['name' => 'Découpe MDF', 'calculation_type' => 'fixed', 'unit_price' => 70],
            ['name' => 'Collage Chant', 'calculation_type' => 'per_meter', 'unit_price' => 2],
            ['name' => 'Pose Bandchant', 'calculation_type' => 'per_meter', 'unit_price' => 8],
        ];

        foreach ($services as $service) {
            Service::firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'name' => $service['name'],
                ],
                [
                    'calculation_type' => $service['calculation_type'],
                    'unit_price' => $service['unit_price'],
                ]
            );
        }

        $this->command?->info('Client handover seed complete.');
        $this->command?->warn('Edit Paramètres (logo, ICE, RC) and enter stock before Go Live.');
        $this->command?->table(
            ['Rôle', 'Email', 'Mot de passe (si nouveau compte)'],
            [
                ['Admin', 'admin@taaouati.com', 'Admin2026! (ou existant)'],
                ['Caisse', 'caisse@taaouati.com', 'Caisse2026!'],
                ['Atelier', 'atelier@taaouati.com', 'Atelier2026!'],
            ]
        );
    }
}
