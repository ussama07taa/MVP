<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('item_type')->nullable()->after('sort_order')->comment('stock_panel, stock_canto, service, or null for free items');
            $table->unsignedBigInteger('item_id')->nullable()->after('item_type');
            $table->decimal('unit_cost', 10, 2)->default(0)->after('unit_price')->comment('Cost price for margin calculation');
        });

        // Add validated_at and stock_deducted to invoices table
        Schema::table('invoices', function (Blueprint $table) {
            $table->timestamp('validated_at')->nullable()->after('notes')->comment('When invoice was validated (stock deducted, debt added)');
            $table->boolean('stock_deducted')->default(false)->after('validated_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'item_id', 'unit_cost']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['validated_at', 'stock_deducted']);
        });
    }
};
