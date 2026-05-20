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
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
        });
        Schema::table('stock_cantos', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_panels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
        Schema::table('stock_cantos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
