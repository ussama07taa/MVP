<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->morphs('item'); // item_id, item_type (StockPanel, StockCanto, Consumable)
            $table->foreignId('purchase_line_id')->nullable()->constrained('purchase_lines')->onDelete('set null');
            $table->integer('quantity_adjusted'); // Negative for loss/shrinkage, Positive for found stock
            $table->string('reason'); // 'Cassé', 'Vol', 'Erreur de comptage', etc.
            $table->foreignId('user_id')->constrained(); // Who authorized the adjustment
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
