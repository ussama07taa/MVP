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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders');
            $table->foreignId('client_id')->constrained('clients');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['avance', 'solde', 'avoir', 'remboursement']);
            $table->enum('payment_method', ['cash', 'card', 'check', 'transfer']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
