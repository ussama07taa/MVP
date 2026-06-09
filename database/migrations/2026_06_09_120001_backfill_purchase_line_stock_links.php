<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $panelLines = DB::table('purchase_lines')
            ->whereIn('category', ['mdf', 'panel'])
            ->whereNull('stock_item_id')
            ->get(['id', 'purchase_id']);

        foreach ($panelLines as $line) {
            $panel = DB::table('stock_panels')
                ->where('purchase_id', $line->purchase_id)
                ->orderBy('id')
                ->first(['id']);

            if ($panel) {
                DB::table('purchase_lines')->where('id', $line->id)->update([
                    'stock_item_id' => $panel->id,
                    'stock_item_type' => 'StockPanel',
                ]);
            }
        }

        $cantoLines = DB::table('purchase_lines')
            ->where('category', 'canto')
            ->whereNull('stock_item_id')
            ->get(['id', 'purchase_id']);

        foreach ($cantoLines as $line) {
            $canto = DB::table('stock_cantos')
                ->where('purchase_id', $line->purchase_id)
                ->orderBy('id')
                ->first(['id']);

            if ($canto) {
                DB::table('purchase_lines')->where('id', $line->id)->update([
                    'stock_item_id' => $canto->id,
                    'stock_item_type' => 'StockCanto',
                ]);
            }
        }
    }

    public function down(): void
    {
        // Non-destructive backfill — no rollback needed
    }
};
