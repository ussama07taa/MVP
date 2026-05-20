<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_panels', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_panels', 'purchase_id')) {
                $table->foreignId('purchase_id')->nullable()->after('tenant_id')->constrained('purchases');
            }
        });
        Schema::table('stock_cantos', function (Blueprint $table) {
            if (!Schema::hasColumn('stock_cantos', 'purchase_id')) {
                $table->foreignId('purchase_id')->nullable()->after('tenant_id')->constrained('purchases');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_panels', function (Blueprint $table) {
            $table->dropForeign(['purchase_id']);
            $table->dropColumn('purchase_id');
        });
        Schema::table('stock_cantos', function (Blueprint $table) {
            $table->dropForeign(['purchase_id']);
            $table->dropColumn('purchase_id');
        });
    }
};
