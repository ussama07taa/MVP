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
        Schema::create('pay_slips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained();
            $table->foreignId('employee_id')->constrained();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('base_wages_total', 12, 2)->default(0);
            $table->decimal('overtime_wages_total', 12, 2)->default(0);
            $table->decimal('bonuses_total', 12, 2)->default(0);
            $table->decimal('advances_total', 12, 2)->default(0);
            $table->decimal('net_paid', 12, 2)->default(0);
            $table->string('payment_method')->default('cash');
            $table->string('status')->default('paid'); // paid, draft
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pay_slips');
    }
};
