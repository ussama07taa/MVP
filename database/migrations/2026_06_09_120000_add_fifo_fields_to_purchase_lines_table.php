<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_item_id')->nullable()->after('purchase_id');
            $table->string('stock_item_type', 50)->nullable()->after('stock_item_id');
            $table->decimal('quantity_remaining', 10, 2)->nullable()->after('quantity');
        });

        DB::table('purchase_lines')->whereNull('quantity_remaining')->update([
            'quantity_remaining' => DB::raw('quantity'),
        ]);
    }

    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            $table->dropColumn(['stock_item_id', 'stock_item_type', 'quantity_remaining']);
        });
    }
};
