<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetOperationsCommand extends Command
{
    protected $signature = 'system:reset-operations
                            {--force : Exécuter sans confirmation}';

    protected $description = 'Remet à zéro toutes les opérations (ventes, achats, stock, atelier…) en gardant clients et fournisseurs';

    /** Tables supprimées — ordre respecté si FK actives */
    private array $operationalTables = [
        'order_return_lines',
        'order_returns',
        'workshop_queue_services',
        'workshop_queues',
        'invoice_items',
        'payments',
        'order_lines',
        'orders',
        'invoices',
        'purchase_returns',
        'purchase_lines',
        'purchase_items',
        'supplier_payments',
        'purchases',
        'inventory_adjustments',
        'stock_panels',
        'stock_cantos',
        'consumables',
        'expenses',
        'pay_slips',
        'payroll_histories',
        'employee_advances',
        'employee_attendances',
        'employees',
        'notifications',
        'activity_log',
        'jobs',
        'failed_jobs',
    ];

    public function handle(): int
    {
        $this->warn('⚠️  ATTENTION : Cette action est IRRÉVERSIBLE.');
        $this->line('');
        $this->line('  ✓ CONSERVÉ  : clients, fournisseurs, utilisateurs, paramètres, services');
        $this->line('  ✗ SUPPRIMÉ  : commandes, factures, paiements, achats, stock, atelier, RH, stats');
        $this->line('  ↺ REMIS À 0 : dette clients (total_credit), dette fournisseurs (total_debt)');
        $this->line('');

        if (!$this->option('force') && !$this->confirm('Continuer la remise à zéro ?', false)) {
            $this->info('Annulé.');
            return self::SUCCESS;
        }

        $deleted = [];

        DB::transaction(function () use (&$deleted) {
            Schema::disableForeignKeyConstraints();

            foreach ($this->operationalTables as $table) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $count = DB::table($table)->count();
                DB::table($table)->truncate();
                $deleted[$table] = $count;
            }

            Schema::enableForeignKeyConstraints();

            $clientsReset = Client::withTrashed()->update(['total_credit' => 0]);
            $suppliersReset = Supplier::withTrashed()->update(['total_debt' => 0]);
        });

        $this->info('✅ Système remis à zéro avec succès.');
        $this->line('');

        $this->table(
            ['Table', 'Lignes supprimées'],
            collect($deleted)->map(fn ($count, $table) => [$table, $count])->values()->toArray()
        );

        $this->line('');
        $this->info('Clients conservés : ' . Client::withTrashed()->count());
        $this->info('Fournisseurs conservés : ' . Supplier::withTrashed()->count());
        $this->info('Dettes clients / fournisseurs : 0 DH');

        return self::SUCCESS;
    }
}
