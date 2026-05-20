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
        Schema::create('stock_panels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('type')->comment('e.g. MDF, LATI, CHUTE');
            $table->integer('size_x')->comment('Length in mm');
            $table->integer('size_y')->comment('Width in mm');
            $table->decimal('thickness', 4, 1)->comment('e.g. 18.0');
            $table->string('color_code')->nullable();
            $table->string('finish_type')->nullable()->comment('e.g. Mat, Brillant, Soft Touch');
            $table->string('provider_catalog')->nullable();
            $table->integer('quantity')->default(0);
            $table->decimal('cost_price', 8, 2)->comment('For FIFO or Average Cost calculation');
            $table->decimal('base_price_sell', 8, 2);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['tenant_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_panels');
    }
};
