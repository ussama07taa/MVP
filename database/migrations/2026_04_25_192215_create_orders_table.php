<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('user_id')->constrained('users')->comment('Cashier');
            $table->string('status')->default('draft')->comment('Uses Spatie Model States');
            $table->decimal('total_sell_price', 10, 2)->default(0);
            $table->decimal('total_cost_price', 10, 2)->default(0)->comment('For Net Profit');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('qr_code_hash')->unique()->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'status', 'client_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
